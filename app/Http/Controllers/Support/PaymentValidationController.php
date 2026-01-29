<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plans;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentValidationController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Lista de pagos pendientes de validación
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403, 'No tienes permisos de soporte');
        }
        
        $search = $request->query('search', '');
        $statusFilter = $request->query('status', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');
        
        $query = Payment::with(['payerProfile.user', 'clientAccount']);
        
        // Filtros
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('payerProfile', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%");
                  })
                  ->orWhereHas('payerProfile.user', function ($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        $payments = $query->orderByRaw("status = 'pending' DESC, created_at DESC")
            ->paginate(20)
            ->withQueryString()
            ->through(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'status_label' => $this->getStatusLabel($payment->status),
                    'payer_name' => ($payment->payerProfile ? $payment->payerProfile->first_name . ' ' . $payment->payerProfile->last_name : 'N/A'),
                    'payer_email' => $payment->payerProfile?->user?->email ?? 'N/A',
                    'payer_dni' => $payment->payerProfile?->dni ?? 'N/A',
                    'bank_name' => $payment->clientAccount?->bank_name ?? 'N/A',
                    'holder_name' => $payment->clientAccount?->holder_name ?? 'N/A',
                    'is_refunded' => $payment->is_refunded,
                    'has_subscription' => Subscription::where('payment_id', $payment->id)->exists(),
                    'metadata' => $payment->metadata,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            });
        
        // Resumen de estadísticas
        $summary = [
            'pending_count' => Payment::where('status', 'pending')->count(),
            'pending_amount' => Payment::where('status', 'pending')->sum('amount'),
            'completed_today' => Payment::where('status', 'completed')->whereDate('updated_at', today())->count(),
            'failed_today' => Payment::where('status', 'failed')->whereDate('updated_at', today())->count(),
        ];
        
        return Inertia::render('support/PaymentValidation/Index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statusOptions' => [
                'pending' => 'Pendiente',
                'completed' => 'Completado',
                'failed' => 'Fallido',
                'refunded' => 'Reembolsado',
            ],
        ]);
    }
    
    /**
     * Ver detalles del pago para validación
     */
    public function show(Payment $payment)
    {
        $user = request()->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $payment->load([
            'payerProfile.user',
            'clientAccount',
            'subscription.planType',
            'subscription.investmentEarnings.fund',
            'paymentAllocations.fund'
        ]);
        
        $paymentData = [
            'id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'status_label' => $this->getStatusLabel($payment->status),
            'is_refunded' => $payment->is_refunded,
            'metadata' => $payment->metadata,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
        
        $payerData = null;
        if ($payment->payerProfile) {
            $payerData = [
                'id' => $payment->payerProfile->id,
                'user_id' => $payment->payerProfile->user_id,
                'user_name' => $payment->payerProfile->user->name,
                'user_email' => $payment->payerProfile->user->email,
                'first_name' => $payment->payerProfile->first_name,
                'last_name' => $payment->payerProfile->last_name,
                'type_document' => $payment->payerProfile->type_document,
                'dni' => $payment->payerProfile->dni,
                'phone' => $payment->payerProfile->phone,
                'country' => $payment->payerProfile->country,
                'verified' => $payment->payerProfile->verified,
            ];
        }
        
        $accountData = null;
        if ($payment->clientAccount) {
            $accountData = [
                'id' => $payment->clientAccount->id,
                'bank_name' => $payment->clientAccount->bank_name,
                'card_type' => $payment->clientAccount->card_type,
                'holder_name' => $payment->clientAccount->holder_name,
                'last4' => $payment->clientAccount->last4,
                'exp_month' => $payment->clientAccount->exp_month,
                'exp_year' => $payment->clientAccount->exp_year,
            ];
        }
        
        $subscriptionData = null;
        if ($payment->subscription) {
            $subscriptionData = [
                'id' => $payment->subscription->id,
                'unique_code' => $payment->subscription->unique_code,
                'plan_type' => $payment->subscription->planType ? [
                    'id' => $payment->subscription->planType->id,
                    'name' => $payment->subscription->planType->name,
                    'category' => $payment->subscription->planType->category,
                    'amount_min' => $payment->subscription->planType->amount_min,
                    'amount_max' => $payment->subscription->planType->amount_max,
                ] : null,
                'started_at' => $payment->subscription->started_at,
                'expires_at' => $payment->subscription->expires_at,
            ];
        }
        
        return Inertia::render('support/PaymentValidation/Show', [
            'payment' => $paymentData,
            'payer' => $payerData,
            'account' => $accountData,
            'subscription' => $subscriptionData,
            'plan_types' => Plans::all(),
        ]);
    }
    
    /**
     * Aprobar pago
     */
    public function approve(Request $request, Payment $payment)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            // Actualizar el estado del pago
            $payment->update([
                'status' => 'completed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'validated_at' => now(),
                    'validated_by' => $user->id,
                    'validation_notes' => $validated['notes'] ?? null,
                    'plan_type_id' => $validated['plan_type_id'],
                ])
            ]);
            
            // Crear suscripción si no existe
            if (!$payment->subscription) {
                $this->paymentService->createSubscriptionFromPayment($payment, $validated['plan_type_id']);
            }
            
            Log::info('Payment approved by support', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'support_user_id' => $user->id,
                'plan_type_id' => $validated['plan_type_id'],
                'notes' => $validated['notes'] ?? null,
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Pago aprobado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al aprobar el pago: ' . $e->getMessage());
        }
    }
    
    /**
     * Rechazar pago
     */
    public function reject(Request $request, Payment $payment)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_reason' => $validated['reason'],
                ])
            ]);
            
            Log::info('Payment rejected by support', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'support_user_id' => $user->id,
                'reason' => $validated['reason'],
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Pago rechazado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al rechazar el pago.');
        }
    }
    
    /**
     * Procesar reembolso
     */
    public function refund(Request $request, Payment $payment)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'refund_amount' => 'nullable|numeric|min:0|max:' . $payment->amount,
        ]);
        
        DB::beginTransaction();
        try {
            $refundAmount = $validated['refund_amount'] ?? $payment->amount;
            
            $payment->update([
                'status' => 'refunded',
                'is_refunded' => true,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'refunded_at' => now(),
                    'refunded_by' => $user->id,
                    'refund_reason' => $validated['reason'],
                    'refund_amount' => $refundAmount,
                ])
            ]);
            
            Log::info('Payment refunded by support', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'support_user_id' => $user->id,
                'refund_amount' => $refundAmount,
                'reason' => $validated['reason'],
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Reembolso procesado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al procesar el reembolso.');
        }
    }
    
    private function getStatusLabel($status)
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            default => ucfirst($status),
        };
    }
}