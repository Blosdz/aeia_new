<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Payment;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\ClientAccount;

class PaymentController extends Controller
{
    /**
     * Listar pagos del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no tiene perfil, retornar colección vacía
        if (!$profile) {
            return Inertia::render('Client/Payments/Index', [
                'payments' => [],
            ]);
        }
        
        $payments = Payment::where('payer_profile_id', $profile->id)
            ->with('clientAccount', 'subscriptions')
            ->orderByDesc('created_at')
            ->paginate(15) ?? collect();
        
        return Inertia::render('Client/Payments/Index', [
            'payments' => $payments,
        ]);
    }
    
    public function selectPlan(Request $request)
    {
        $user = auth()->user();
        $profile = $user->profile;
        
        $plans = Plans::where('category', 'investment')
            ->orderBy('amount_min')
            ->get() ?? collect();
        
        // Verificar qué membresías ya ha pagado el usuario
        $userMembershipStatus = [];
        if ($profile) {
            $paidMemberships = Payment::whereHas('subscriptions', function ($query) use ($plans) {
                $query->whereIn('plan_type_id', $plans->pluck('id')->toArray());
            })
                ->where('payer_profile_id', $profile->id)
                ->where('status', 'completed')
                ->get();
            
            // Extraer los IDs de planes donde ya pagó membresía
            foreach ($paidMemberships as $payment) {
                foreach ($payment->subscriptions as $subscription) {
                    $userMembershipStatus[$subscription->plan_type_id] = true;
                }
            }
        }
        
        return Inertia::render('Client/Payments/SelectPlan', [
            'plans' => $plans,
            'userMembershipStatus' => $userMembershipStatus,
        ]);
    }
    
    public function selectPlanDetail(Request $request, $id)
    {
        $plan = Plans::findOrFail($id);
        $user = auth()->user();
        $profile = $user->profile;
        
        // Obtener todos los planes para el carrusel
        $allPlans = Plans::where('category', 'investment')
            ->orderBy('amount_min')
            ->get() ?? collect();
        
        // Si no tiene perfil o no tiene cuentas, retornar array vacío
        $clientAccounts = [];
        $userMembershipStatus = []; // Para rastrear qué membresías ya pagó el usuario
        
        if ($profile) {
            $clientAccounts = $profile->clientAccounts()
                ->wherePivot('is_active', true)
                ->get() ?? collect();
            
            // Verificar qué membresías ya ha pagado el usuario
            $paidMemberships = Payment::whereHas('subscriptions', function ($query) use ($allPlans) {
                $query->whereIn('plan_type_id', $allPlans->pluck('id')->toArray());
            })
                ->where('payer_profile_id', $profile->id)
                ->where('status', 'completed')
                ->get();
            
            // Extraer los IDs de planes donde ya pagó membresía
            foreach ($paidMemberships as $payment) {
                foreach ($payment->subscriptions as $subscription) {
                    $userMembershipStatus[$subscription->plan_type_id] = true;
                }
            }
        }
        
        return Inertia::render('Client/Payments/PlanDetail', [
            'plan' => $plan,
            'plans' => $allPlans,
            'clientAccounts' => $clientAccounts,
            'userMembershipStatus' => $userMembershipStatus,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'amount' => 'required|numeric|min:1',
            'client_account_id' => 'required|exists:client_accounts,id',
            'currency' => 'required|string|size:3',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return back()->withErrors(['error' => 'Perfil no configurado. Por favor completa tu perfil primero.']);
        }

        // Obtener el plan
        $plan = Plans::findOrFail($validated['plan_type_id']);

        // Obtener la cuenta de pago
        $clientAccount = ClientAccount::findOrFail($validated['client_account_id']);

        // Calcular si debe cobrar membresía
        $membershipCharge = 0;
        $includesMembership = false;

        if ($plan->membership && $plan->membership > 0) {
            // Verificar si el usuario ya ha pagado la membresía antes
            $existingMembershipPayment = Payment::whereHas('subscriptions', function ($query) use ($plan) {
                $query->where('plan_type_id', $plan->id);
            })
                ->where('payer_profile_id', $profile->id)
                ->where('status', 'completed') // Solo contar pagos completados
                ->first();

            // Si no existe pago anterior, cobrar membresía
            if (!$existingMembershipPayment) {
                $membershipCharge = floatval($plan->membership);
                $includesMembership = true;
            }
        }

        $totalAmount = $validated['amount'] + $membershipCharge;

        // Generar transaction_id temporal para la confirmación
        $transactionId = 'TXN_' . time() . '_' . uniqid();

        // NO crear el pago aquí, solo mostrar la página de confirmación
        // El pago se creará en el método confirm() cuando el usuario confirme

        return Inertia::render('Client/Payments/PaymentConfirm', [
            'plan' => $plan,
            'clientAccount' => $clientAccount,
            'payment' => [
                'investment_amount' => $validated['amount'],
                'membership_charge' => $membershipCharge,
                'total_amount' => $totalAmount,
                'includes_membership' => $includesMembership,
                'currency' => $validated['currency'],
                'client_account_id' => $validated['client_account_id'],
                'plan_type_id' => $validated['plan_type_id'],
                'transaction_id' => $transactionId,
            ],
        ]);
    }
    
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'investment_amount' => 'required|numeric|min:1',
            'membership_charge' => 'required|numeric|min:0',
            'client_account_id' => 'required|exists:client_accounts,id',
            'currency' => 'required|string|size:3',
            'transaction_id' => 'required|string',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return back()->withErrors(['error' => 'Perfil no configurado.']);
        }

        // Obtener el plan
        $plan = Plans::findOrFail($validated['plan_type_id']);

        // Preparar metadata
        $metadata = [];
        if ($validated['membership_charge'] > 0) {
            $metadata['membership_applied'] = floatval($validated['membership_charge']);
        }

        // Crear el pago en la BD
        $payment = Payment::create([
            'transaction_id' => $validated['transaction_id'],
            'amount' => $validated['investment_amount'], // Monto de inversión
            'currency' => $validated['currency'],
            'client_account_id' => $validated['client_account_id'],
            'payer_profile_id' => $profile->id,
            'status' => 'pending',
            'metadata' => $metadata, // Incluye membership_applied: 25.00 si aplica
        ]);

        // Crear subscripción
        Subscription::create([
            'payment_id' => $payment->id,
            'plan_type_id' => $validated['plan_type_id'],
            'unique_code' => 'SUB_' . time() . '_' . uniqid(),
            'started_at' => now(),
        ]);

        // TODO: Aquí iría la integración con el PSP (Stripe, PayPal, etc.)
        // El PSP procesará el pago y cuando confirme, cambiará el estado a 'completed'
        // Por ahora, el pago permanece en estado 'pending' hasta que el PSP lo confirme

        // Redirigir al detalle del pago (el pago permanece en estado pending)
        return redirect()
            ->route('clients.payments.show', $payment->id)
            ->with('success', 'Pago enviado para procesamiento. El estado se actualizará cuando se confirme.');
    }

    public function show(Request $request, Payment $payment)
    {
        $user = $request->user();

        // Verificar que el usuario sea el propietario del pago
        if (!$payment->payerProfile || $payment->payerProfile->user_id !== $user->id) {
            abort(403);
        }

        return Inertia::render('Client/Payments/Show', [
            'payment' => $payment->load('clientAccount', 'subscriptions', 'rewards'),
        ]);
    }
}
