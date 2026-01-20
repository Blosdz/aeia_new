<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fund;
use App\Models\Payment;
use App\Models\Plans;
use App\Models\Subscription;
use App\Services\PaymentService;
use App\Services\ProjectClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected ProjectClosureService $closureService;

    public function __construct(PaymentService $paymentService, ProjectClosureService $closureService)
    {
        $this->paymentService = $paymentService;
        $this->closureService = $closureService;
    }

    /**
     * Lista de pagos con filtros
     */
    public function index(Request $request)
    {
        $query = Payment::with('payerProfile', 'clientAccount');

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%$search%")
                    ->orWhereHas('payerProfile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%");
                    });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
            'summary' => $this->paymentService->getPendingPaymentsSummary(),
        ]);
    }

    /**
     * Detalle del pago
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'payerProfile',
            'clientAccount',
            'subscription.planType',
            'subscription.investmentEarnings.fund',
            'paymentAllocations.fund'
        ]);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment,
            'planTypes' => Plans::all(),
        ]);
    }

    /**
     * Validar pago y actualizar estado a completed
     */
    public function validate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'notes' => 'nullable|string',
        ]);

        try {
            // Actualizar el estado del pago a completed
            $payment->update([
                'status' => 'completed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'validated_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ])
            ]);

            return redirect()
                ->route('admin.payments.show', $payment->id)
                ->with('success', 'Pago validado exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payments.show', $payment->id)
                ->withErrors(['error' => 'Error al validar: ' . $e->getMessage()]);
        }
    }

    /**
     * Rechazar pago
     */
    public function reject(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $this->paymentService->rejectPayment($payment, $validated['reason']);

        return redirect()
            ->route('admin.payments.show', $payment->id)
            ->with('success', 'Pago rechazado');
    }

    /**
     * Crear nuevo fondo con pagos seleccionados
     */
    public function createFund(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:investment,coverage',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
        ]);

        try {
            // Crear fondo con estructura completa (fund_histories, investment_earnings, etc.)
            $fund = $this->paymentService->createFund(
                $validated['category'],
                $validated['name'],
                $validated['payment_ids'],
                $validated['description'] ?? null
            );

            \Log::info('Fondo creado exitosamente', [
                'fund_id' => $fund->id,
                'fund_name' => $fund->name,
                'total_amount' => $fund->initial_amount,
                'participant_count' => count($validated['payment_ids']),
            ]);

            return redirect()
                ->route('admin.payments.dashboard')
                ->with('success', "Fondo '{$fund->name}' creado exitosamente con {$fund->investmentEarnings()->count()} participantes");
        } catch (\Exception $e) {
            \Log::error('Error al crear fondo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('admin.payments.create_fund_form')
                ->withErrors(['error' => 'Error al crear fondo: ' . $e->getMessage()]);
        }
    }

    /**
     * Asignar pagos a fondo
     */
    public function allocateToFund(Request $request)
    {
        $validated = $request->validate([
            'fund_id' => 'required|exists:funds,id',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
        ]);

        try {
            $fund = Fund::find($validated['fund_id']);
            $this->paymentService->allocatePaymentsToFund($fund, $validated['payment_ids']);

            return redirect()
                ->route('admin.payments.dashboard')
                ->with('success', 'Pagos asignados al fondo');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payments.dashboard')
                ->withErrors(['error' => 'Error al asignar: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualizar valor del fondo
     */
    public function updateFundValue(Request $request)
    {
        $validated = $request->validate([
            'fund_id' => 'required|exists:funds,id',
            'new_amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        try {
            $fund = Fund::find($validated['fund_id']);
            $adminProfileId = Auth::user()->profile->id ?? null;
            
            $this->paymentService->updateFundValue(
                $fund,
                floatval($validated['new_amount']),
                $validated['reason'] ?? null,
                $adminProfileId
            );

            return redirect()
                ->route('admin.payments.dashboard')
                ->with('success', 'Valor del fondo actualizado');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payments.dashboard')
                ->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Dashboard de resumen
     */
    public function dashboard()
    {
        // Pagos completados sin asignar a fondos
        $completedPaymentsWithoutFund = Payment::where('status', 'completed')
            ->with(['payerProfile', 'subscription.planType'])
            ->whereDoesntHave('paymentAllocations')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => floatval($payment->amount),
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at,
                    'payer_profile' => [
                        'first_name' => $payment->payerProfile->first_name,
                        'last_name' => $payment->payerProfile->last_name,
                    ],
                    'subscription' => $payment->subscription ? [
                        'plan_type' => $payment->subscription->planType ? [
                            'name' => $payment->subscription->planType->name,
                            'category' => $payment->subscription->planType->category,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Payments/Dashboard', [
            'pendingSummary' => $this->paymentService->getPendingPaymentsSummary(),
            'subscriptionsSummary' => $this->paymentService->getActiveSubscriptionsSummary(),
            'fundsSummary' => $this->paymentService->getFundsSummary(),
            'completedPaymentsWithoutFund' => $completedPaymentsWithoutFund,
        ]);
    }

    /**
     * Mostrar formulario para crear fondo
     */
    public function createFundForm()
    {
        // Obtener pagos completados que NO tienen allocations (no están asignados a ningún fondo)
        $availablePayments = Payment::where('status', 'completed')
            ->with(['payerProfile', 'clientAccount', 'subscription.planType'])
            ->whereDoesntHave('paymentAllocations')  // No están asignados a un fondo
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => floatval($payment->amount),
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at,
                    'payer_profile' => [
                        'first_name' => $payment->payerProfile->first_name,
                        'last_name' => $payment->payerProfile->last_name,
                        'dni' => $payment->payerProfile->dni,
                    ],
                    'subscription' => $payment->subscription ? [
                        'id' => $payment->subscription->id,
                        'unique_code' => $payment->subscription->unique_code,
                        'plan_type' => $payment->subscription->planType ? [
                            'name' => $payment->subscription->planType->name,
                            'category' => $payment->subscription->planType->category,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Payments/CreateFund', [
            'pendingPayments' => $availablePayments,
        ]);
    }

    /**
     * Mostrar formulario para actualizar valores de fondos
     */
    public function updateFundValueForm()
    {
        $funds = Fund::where('status', 'open')
            ->with('history')
            ->withCount('investmentEarnings as total_participants')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($fund) {
                return [
                    'id' => $fund->id,
                    'name' => $fund->name,
                    'category' => $fund->category,
                    'initial_amount' => floatval($fund->initial_amount),
                    'current_amount' => floatval($fund->current_amount),
                    'status' => $fund->status,
                    'created_at' => $fund->created_at,
                    'total_participants' => $fund->total_participants,
                    'history' => $fund->history->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'fund_id' => $h->fund_id,
                            'previous_amount' => floatval($h->previous_amount ?? 0),
                            'new_amount' => floatval($h->new_amount ?? 0),
                            'change_amount' => floatval($h->new_amount ?? 0) - floatval($h->previous_amount ?? 0),
                            'change_percent' => floatval($h->fluctuation_percent ?? 0),
                            'reason' => $h->reason,
                            'metadata' => $h->metadata,
                            'created_at' => $h->recorded_at ?? $h->created_at,
                        ];
                    })->sortByDesc('created_at')->values()->toArray(),
                ];
            });

        return Inertia::render('Admin/Payments/UpdateFundValue', [
            'funds' => $funds->values()->toArray(),
        ]);
    }

    /**
     * Cerrar fondo y distribuir ganancias
     * POST /admin/payments/fund/{fund}/close
     */
    public function closeFund(Request $request, Fund $fund)
    {
        try {
            \Log::info('📤 Iniciando cierre de fondo', [
                'fund_id' => $fund->id,
                'fund_name' => $fund->name,
                'request_data' => $request->all(),
            ]);

            $validated = $request->validate([
                'fund_yield' => 'nullable|numeric|min:0|max:100',
            ]);

            \Log::info('✅ Validación completada', [
                'validated_data' => $validated,
            ]);

            // Convertir porcentaje a decimal (ej: 20 → 0.20)
            $fundYield = isset($validated['fund_yield']) 
                ? $validated['fund_yield'] / 100 
                : 0.20; // Default 20%

            \Log::info('🔢 Rendimiento del fondo calculado', [
                'fund_yield_decimal' => $fundYield,
                'fund_yield_percent' => $validated['fund_yield'] ?? 20,
            ]);

            // Cerrar el fondo
            $closure = $this->closureService->closeProjectPeriod($fund, [
                'fund_yield' => $fundYield,
            ]);

            \Log::info('🎉 Fondo cerrado exitosamente', [
                'closure_id' => $closure->id,
                'total_clients' => $closure->total_clients,
                'company_total' => $closure->company_total,
                'referrals_total' => $closure->referrals_total,
                'clients_earnings' => $closure->clients_earnings,
            ]);

            // Retornar redirección Inertia (useForm().post() lo espera)
            return redirect()
                ->route('admin.payments.dashboard')
                ->with('success', "Fondo {$fund->name} cerrado exitosamente. {$closure->total_clients} clientes procesados.");
        } catch (\Exception $e) {
            \Log::error('❌ Error al cerrar fondo', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->route('admin.payments.dashboard')
                ->withErrors(['error' => 'Error al cerrar fondo: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener rewards del fondo (para mostrar en tabla)
     * GET /admin/payments/fund/{fund}/rewards
     */
    public function getFundRewards(Fund $fund)
    {
        try {
            // Obtener rewards detallados
            $rewards = $this->closureService->getFundRewardsWithDetails($fund);

            // Obtener resumen de distribución
            $distribution = $this->closureService->getFundDistributionSummary($fund);

            return response()->json([
                'success' => true,
                'rewards' => $rewards,
                'distribution' => $distribution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener rewards: ' . $e->getMessage(),
            ], 500);
        }
    }
}

