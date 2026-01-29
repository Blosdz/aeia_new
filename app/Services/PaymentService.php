<?php

namespace App\Services;

use App\Models\Fund;
use App\Models\FundHistory;
use App\Models\InvestmentEarning;
use App\Models\InvestmentEarningHistory;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\ReferralCommission;
use App\Models\Subscription;
use App\Models\SubscriptionParticipant;
use App\Services\Traits\ReferralCommissionTrait;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    use ReferralCommissionTrait;
    /**
     * Validar un pago y crear suscripción
     */
    public function validatePayment(Payment $payment, ?string $notes = null): Subscription
    {
        return DB::transaction(function () use ($payment, $notes) {
            // 1. Cambiar estado del pago a completado
            $payment->update([
                'status' => 'completed',
                'metadata' => array_merge($payment->metadata ?? [], ['validated_at' => now()])
            ]);

            // 2. Obtener plan type (asumo que viene en metadata)
            $planTypeId = $payment->metadata['plan_type_id'] ?? null;

            // 3. Crear suscripción con código único
            $subscription = Subscription::create([
                'payment_id' => $payment->id,
                'plan_type_id' => $planTypeId,
                'started_at' => now(),
            ]);

            // 4. Crear un fondo temporal o usar uno existente (esto debería ser asignado por el admin después)
            // Por ahora, crearemos un registro de InvestmentEarning sin fondo asignado
            // El admin deberá asignar el fondo posteriormente
            $investmentEarning = InvestmentEarning::create([
                'subscription_id' => $subscription->id,
                'fund_id' => 1, // ID temporal del fondo - debe ser reemplazado por el admin
                'initial_amount' => $payment->amount,
                'current_amount' => $payment->amount,
                'metadata' => ['note' => 'Fondo pendiente de asignación por admin']
            ]);

            // 5. Agregar propietario como participante principal
            SubscriptionParticipant::create([
                'subscription_id' => $subscription->id,
                'profile_id' => $payment->payer_profile_id,
                'investment_earnings_id' => $investmentEarning->id,
                'role' => 'owner',
                'share_percent' => 100,
                'final_investment_amount' => $payment->amount,
                'is_primary_owner' => true,
                'started_at' => now(),
            ]);

            // 5. Verificar si el cliente fue referido y crear ReferralCommission si es su primer pago
            if ($payment->payerProfile && $payment->payerProfile->user && $payment->payerProfile->user->referred_by_user_id) {
                $clientUser = $payment->payerProfile->user;
                
                // Verificar si es el primer pago completado de este cliente
                $previousCompletedPayments = Payment::where('payer_profile_id', $payment->payer_profile_id)
                    ->where('status', 'completed')
                    ->where('id', '!=', $payment->id) // Excluir el pago actual
                    ->count();
                
                // Si es el primer pago, crear ReferralCommission
                if ($previousCompletedPayments === 0) {
                    \App\Models\ReferralCommission::create([
                        'staff_user_id' => $clientUser->referred_by_user_id,
                        'referred_user_id' => $clientUser->id,
                        'deposit_amount' => $payment->amount,
                        'commission_percentage' => 15, // 15% según MD
                        'commission_amount' => 0, // Se calcula en cierre
                        'is_first_deposit' => true,
                        'status' => 'pending',
                    ]);
                    
                    \Log::info('ReferralCommission creada para primer pago referido', [
                        'staff_user_id' => $clientUser->referred_by_user_id,
                        'referred_user_id' => $clientUser->id,
                        'payment_amount' => $payment->amount,
                    ]);
                }
            }

            return $subscription;
        });
    }

    /**
     * Rechazar un pago
     */
    public function rejectPayment(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => now()
            ])
        ]);
    }

    /**
     * Crear un fondo con pagos específicos y crear estructura completa
     * para cada participante (subscription_participant, investment_earnings, etc.)
     * Incluye la creación de la subscription del admin para el 20% de ganancias
     */
    public function createFund(string $category, string $name, array $paymentIds, ?string $description = null): Fund
    {
        return DB::transaction(function () use ($category, $name, $paymentIds, $description) {
            // Obtener pagos completados seleccionados
            $payments = Payment::whereIn('id', $paymentIds)
                ->where('status', 'completed')
                ->with('subscription')
                ->get();

            if ($payments->isEmpty()) {
                throw new \Exception('No hay pagos válidos para crear el fondo');
            }

            // Calcular monto total del fondo
            $totalAmount = $payments->sum('amount');

            // Crear fondo
            $fund = Fund::create([
                'category' => $category,
                'name' => $name,
                'initial_amount' => $totalAmount,
                'current_amount' => $totalAmount,
                'metadata' => [
                    'description' => $description,
                    'created_at' => now(),
                    'payment_count' => $payments->count(),
                ],
                'status' => 'open',
            ]);

            // Registrar primera entrada en historial de fondo
            FundHistory::create([
                'fund_id' => $fund->id,
                'previous_amount' => 0,
                'new_amount' => $totalAmount,
                'fluctuation_percent' => 0,
                'reason' => 'fund_created',
                'recorded_at' => now(),
                'metadata' => [
                    'event' => 'fund_created',
                    'initial_amount' => $totalAmount,
                    'payment_count' => $payments->count(),
                ]
            ]);

            // Procesar cada pago
            foreach ($payments as $payment) {
                // Verificar que el pago tiene suscripción
                if (!$payment->subscription) {
                    \Log::warning("Pago {$payment->id} no tiene suscripción, se omite");
                    continue;
                }

                $subscription = $payment->subscription;

                // Calcular porcentaje de participación basado en el monto invertido
                $sharePercent = ($payment->amount / $totalAmount) * 100;

                // 1. Crear InvestmentEarning para esta suscripción en este fondo
                $earning = InvestmentEarning::create([
                    'subscription_id' => $subscription->id,
                    'fund_id' => $fund->id,
                    'initial_amount' => $payment->amount,
                    'current_amount' => $payment->amount,
                    'metadata' => [
                        'share_percent' => $sharePercent,
                        'created_at' => now(),
                    ]
                ]);

                // 2. Crear SubscriptionParticipant (owner - el cliente que invirtió)
                SubscriptionParticipant::create([
                    'subscription_id' => $subscription->id,
                    'profile_id' => $payment->payer_profile_id,
                    'investment_earnings_id' => $earning->id,
                    'role' => 'owner',
                    'share_percent' => $sharePercent,
                    'final_investment_amount' => $payment->amount,
                    'is_primary_owner' => true,
                    'participating' => true,
                    'started_at' => now(),
                ]);

                // 3. Crear primera entrada en InvestmentEarningHistory
                InvestmentEarningHistory::create([
                    'earning_id' => $earning->id,
                    'previous_amount' => 0,
                    'new_amount' => floatval($payment->amount),
                    'fluctuation_percent' => 0,
                    'reason' => 'initial_allocation',
                    'recorded_at' => now(),
                    'metadata' => [
                        'event' => 'fund_creation',
                        'fund_id' => $fund->id,
                        'share_percent' => $sharePercent,
                        'initial_amount' => $payment->amount,
                    ]
                ]);

                // 4. Crear PaymentAllocation para vincular pago con fondo
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'fund_id' => $fund->id,
                    'amount' => $payment->amount,
                    'percent' => $sharePercent,
                    'status' => 'accrued',
                    'metadata' => [
                        'allocated_at' => now(),
                        'fund_name' => $fund->name,
                    ]
                ]);

                \Log::info("Participante agregado al fondo", [
                    'fund_id' => $fund->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $payment->amount,
                    'share_percent' => $sharePercent,
                ]);
            }

            // Crear estructura para el ADMIN (20% de futuras ganancias)
            $adminProfile = \App\Models\Profile::where('user_id', 1)->first(); // Admin AEIA
            
            if ($adminProfile) {
                // Obtener o crear plan type para comisiones del admin
                $adminPlanType = \App\Models\Plans::firstOrCreate(
                    ['category' => 'investment', 'name' => 'Admin Commission'],
                    ['amount_min' => 0, 'amount_max' => 0, 'periodicity' => 'monthly']
                );

                // Crear subscription especial para el admin (sin payment asociado)
                $adminSubscription = Subscription::create([
                    'payment_id' => null,
                    'profile_id' => $adminProfile->id,
                    'plan_type_id' => $adminPlanType->id,
                    'started_at' => now(),
                ]);

                // Crear InvestmentEarning para el admin (comienza en 0)
                $adminEarning = InvestmentEarning::create([
                    'subscription_id' => $adminSubscription->id,
                    'fund_id' => $fund->id,
                    'initial_amount' => 0,
                    'current_amount' => 0,
                    'metadata' => [
                        'type' => 'admin_commission',
                        'commission_rate' => 20,
                        'note' => 'Admin receives 20% of fund profits',
                    ]
                ]);

                // Crear SubscriptionParticipant para el admin
                SubscriptionParticipant::create([
                    'subscription_id' => $adminSubscription->id,
                    'profile_id' => $adminProfile->id,
                    'investment_earnings_id' => $adminEarning->id,
                    'role' => 'advisor',
                    'share_percent' => 20,
                    'final_investment_amount' => 0,
                    'is_primary_owner' => false,
                    'participating' => true,
                    'started_at' => now(),
                ]);

                // Registrar en historial de earnings del admin
                InvestmentEarningHistory::create([
                    'earning_id' => $adminEarning->id,
                    'previous_amount' => 0,
                    'new_amount' => 0,
                    'fluctuation_percent' => 0,
                    'reason' => 'admin_commission_setup',
                    'recorded_at' => now(),
                    'metadata' => [
                        'event' => 'admin_commission_setup',
                        'fund_id' => $fund->id,
                        'fund_name' => $fund->name,
                        'commission_rate' => 20,
                    ]
                ]);

                \Log::info("Admin commission structure created for fund", [
                    'fund_id' => $fund->id,
                    'admin_subscription_id' => $adminSubscription->id,
                    'admin_earning_id' => $adminEarning->id,
                ]);
            }

            return $fund;
        });
    }

    /**
     * Asignar pagos validados a un fondo
     */
    public function allocatePaymentsToFund(Fund $fund, array $paymentIds, ?array $allocation = null): void
    {Implementa distribución 80/20:
     * - 80% de las ganancias se distribuyen proporcionalmente entre clientes
     * - 20% de las ganancias van al admin
     * - Las pérdidas las absorben 100% los clientes (admin no pierde)
     */
    public function updateFundValue(Fund $fund, float $newAmount, ?string $reason = null, ?int $adminProfileId = null): void
    {
        DB::transaction(function () use ($fund, $newAmount, $reason, $adminProfileId) {
            $oldFundAmount = $fund->current_amount;
            $initialFundAmount = $fund->initial_amount;
            
            // Calcular ganancia/pérdida total desde el inicio
            $totalProfit = $newAmount - $initialFundAmount;
            
            // Determinar si hay ganancia o pérdida
            $hasProfit = $totalProfit > 0;
            
            // Calcular distribución 80/20 solo si hay ganancia
            $adminProfit = $hasProfit ? $totalProfit * 0.20 : 0;
            $clientsProfit = $hasProfit ? $totalProfit * 0.80 : $totalProfit; // Si hay pérdida, va 100% a clientes
            
            // Calcular fluctuación porcentual desde última actualización
            $fluctuation = $newAmount - $oldFundAmount;
            $fluctuationPercent = $oldFundAmount > 0 ? ($fluctuation / $oldFundAmount) * 100 : 0;

            // Actualizar fondo
            $fund->update(['current_amount' => $newAmount]);

            // Registrar en historial del fondo
            FundHistory::create([
                'fund_id' => $fund->id,
                'previous_amount' => $oldFundAmount,
                'new_amount' => $newAmount,
                'fluctuation_percent' => $fluctuationPercent,
                'reason' => $reason,
                'recorded_at' => now(),
                'metadata' => [
                    'event' => 'value_update',
                    'fluctuation' => $fluctuation,
                    'total_profit' => $totalProfit,
                    'admin_profit' => $adminProfit,
                    'clients_profit' => $clientsProfit,
                    'has_profit' => $hasProfit,
                    'admin_profile_id' => $adminProfileId,
                ]
            ]);

            // Obtener todas las earnings (clientes + admin)
            $earnings = $fund->investmentEarnings()->get();
            
            if ($earnings->isEmpty()) {
                \Log::warning("No hay investment earnings para el fondo {$fund->id}");
                return;
            }

            // Separar earnings de clientes y admin
            $clientEarnings = $earnings->filter(function ($earning) {
                return floatval($earning->initial_amount) > 0; // Clientes tienen initial_amount > 0
            });
            
            $adminEarning = $earnings->filter(function ($earning) {
                return floatval($earning->initial_amount) === 0.0; // Admin tiene initial_amount = 0
            })->first();

            // Actualizar earnings de CLIENTES (80% de ganancias o 100% de pérdidas)
            $totalClientInitial = $clientEarnings->sum('initial_amount');
            
            foreach ($clientEarnings as $earning) {
                // Calcular proporción del cliente en el fondo
                $proportion = $totalClientInitial > 0 
                    ? floatval($earning->initial_amount) / floatval($totalClientInitial) 
                    : 0;
                
                // Nuevo monto = inversión inicial + (ganancia/pérdida de clientes * proporción)
                $clientShare = $clientsProfit * $proportion;
                $newEarningAmount = floatval($earning->initial_amount) + $clientShare;
                $oldEarningAmount = $earning->current_amount;
                
                $earning->update(['current_amount' => $newEarningAmount]);

                // Calcular fluctuación del earning
                $earningFluctuation = $oldEarningAmount > 0 
                    ? (($newEarningAmount - $oldEarningAmount) / $oldEarningAmount) * 100 
                    : 0;
                
                // ROI total del cliente
                $roiTotal = $earning->initial_amount > 0 
                    ? (($newEarningAmount - $earning->initial_amount) / $earning->initial_amount) * 100 
                    : 0;
                
                InvestmentEarningHistory::create([
                    'earning_id' => $earning->id,
                    'previous_amount' => $oldEarningAmount,
                    'new_amount' => $newEarningAmount,
                    'fluctuation_percent' => $earningFluctuation,
                    'reason' => $reason,
                    'recorded_at' => now(),
                    'metadata' => [
                        'event' => 'fund_value_update',
                        'fund_fluctuation_percent' => $fluctuationPercent,
                        'client_share' => $clientShare,
                        'proportion' => $proportion * 100,
                        'roi_total' => $roiTotal,
                        'distribution_type' => $hasProfit ? '80% of profit' : '100% of loss',
                    ]
                ]);

                // Actualizar SubscriptionParticipants asociados
                $participants = SubscriptionParticipant::where('investment_earnings_id', $earning->id)->get();
                foreach ($participants as $participant) {
                    $participant->update([
                        'final_investment_amount' => $newEarningAmount
                    ]);
                }
            }

            // Actualizar earning del ADMIN (20% de ganancias, 0 si hay pérdida)
            if ($adminEarning) {
                $oldAdminAmount = $adminEarning->current_amount;
                $newAdminAmount = $adminProfit; // Comisión acumulada total
                
                $adminEarning->update(['current_amount' => $newAdminAmount]);

                // Calcular fluctuación del admin
                $adminFluctuation = $oldAdminAmount > 0 
                    ? (($newAdminAmount - $oldAdminAmount) / $oldAdminAmount) * 100 
                    : 0;

                InvestmentEarningHistory::create([
                    'earning_id' => $adminEarning->id,
                    'previous_amount' => $oldAdminAmount,
                    'new_amount' => $newAdminAmount,
                    'fluctuation_percent' => $adminFluctuation,
                    'reason' => $reason,
                    'recorded_at' => now(),
                    'metadata' => [
                        'event' => 'admin_commission_update',
                        'fund_fluctuation_percent' => $fluctuationPercent,
                        'total_fund_profit' => $totalProfit,
                        'admin_commission_rate' => 20,
                        'commission_earned' => $newAdminAmount - $oldAdminAmount,
                        'has_profit' => $hasProfit,
                    ]
                ]);

                // Actualizar SubscriptionParticipant del admin
                $adminParticipant = SubscriptionParticipant::where('investment_earnings_id', $adminEarning->id)->first();
                if ($adminParticipant) {
                    $adminParticipant->update([
                        'final_investment_amount' => $newAdminAmount
                    ]);
                }

                \Log::info("Admin commission updated", [
                    'fund_id' => $fund->id,
                    'old_amount' => $oldAdminAmount,
                    'new_amount' => $newAdminAmount,
                    'commission_earned' => $newAdminAmount - $oldAdminAmount,
                ]);
            } else {
                \Log::warning("No se encontró investment earning del admin para el fondo {$fund->id}");
            }

            \Log::info("Fund value updated with 80/20 distribution", [
                'fund_id' => $fund->id,
                'new_amount' => $newAmount,
                'total_profit' => $totalProfit,
                'admin_profit' => $adminProfit,
                'clients_profit' => $clientsProfit,
                'has_profit' => $hasProfit,
            ]);
    public function updateFundValue(Fund $fund, float $newAmount, ?string $reason = null, ?int $adminProfileId = null): void
    {
        DB::transaction(function () use ($fund, $newAmount, $reason, $adminProfileId) {
            $oldAmount = $fund->current_amount;
            $fluctuation = $newAmount - $oldAmount;
            $fluctuationPercent = ($fluctuation / $oldAmount) * 100;

            // Actualizar fondo
            $fund->update(['current_amount' => $newAmount]);

            // Registrar en historial del fondo
            FundHistory::create([
                'fund_id' => $fund->id,
                'previous_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'fluctuation_percent' => $fluctuationPercent,
                'reason' => $reason,
                'recorded_at' => now(),
                'metadata' => [
                    'event' => 'value_update',
                    'fluctuation' => $fluctuation,
                    'admin_profile_id' => $adminProfileId, // Track who updated it here
                ]
            ]);

            // Actualizar InvestmentEarnings proportionalmente
            $earnings = $fund->investmentEarnings()->get();
            
            if ($earnings->count() > 0) {
                foreach ($earnings as $earning) {
                    // Mantener la proporción
                    $proportion = floatval($earning->initial_amount) / floatval($fund->initial_amount);
                    $newEarningAmount = $newAmount * $proportion;
                    $oldEarningAmount = $earning->current_amount;
                    
                    $earning->update(['current_amount' => $newEarningAmount]);

                    // Registrar en historial de earnings del usuario
                    // Calcular fluctuación respecto al monto anterior (no el inicial)
                    $earningFluctuation = 0;
                    if ($oldEarningAmount > 0) {
                        $earningFluctuation = (($newEarningAmount - $oldEarningAmount) / $oldEarningAmount) * 100;
                    }
                    
                    InvestmentEarningHistory::create([
                        'earning_id' => $earning->id,
                        'previous_amount' => $oldEarningAmount,
                        'new_amount' => $newEarningAmount,
                        'fluctuation_percent' => $earningFluctuation,
                        'reason' => $reason,
                        'recorded_at' => now(),
                        'metadata' => [
                            'event' => 'fund_value_update',
                            'fund_fluctuation_percent' => $fluctuationPercent,
                            'roi_total' => $earning->initial_amount > 0 ? (($newEarningAmount - $earning->initial_amount) / $earning->initial_amount) * 100 : 0
                        ]
                    ]);

                     // Actualizar SubscriptionParticipants asociados: final_investment_amount
                     $participants = SubscriptionParticipant::where('investment_earnings_id', $earning->id)->get();
                     foreach ($participants as $participant) {
                         $participant->update([
                             'final_investment_amount' => $newEarningAmount
                         ]);
                     }
                }
            }
        });
    }

    /**
     * Obtener resumen de pagos pendientes
     */
    public function getPendingPaymentsSummary(): array
    {
        $payments = Payment::where('status', 'pending')
            ->with('payerProfile', 'clientAccount')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = $payments->sum('amount');
        $count = $payments->count();

        return [
            'count' => $count,
            'total_amount' => $totalAmount,
            'payments' => $payments,
        ];
    }

    /**
     * Obtener resumen de suscripciones activas
     */
    public function getActiveSubscriptionsSummary(): array
    {
        $subscriptions = Subscription::where('started_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->with('payment.payerProfile', 'investmentEarnings.fund')
            ->get();

        $totalInvested = $subscriptions->sum(fn($s) => $s->getTotalInvested());
        $totalCurrent = $subscriptions->sum(fn($s) => $s->getTotalCurrentAmount());

        return [
            'count' => $subscriptions->count(),
            'total_invested' => $totalInvested,
            'total_current' => $totalCurrent,
            'total_gain_loss' => $totalCurrent - $totalInvested,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Obtener resumen de fondos
     */
    public function getFundsSummary(): array
    {
        $funds = Fund::where('status', 'open')
            ->with('investmentEarnings', 'histories')
            ->get();

        return [
            'count' => $funds->count(),
            'total_invested' => $funds->sum('initial_amount'),
            'total_current' => $funds->sum('current_amount'),
            'funds' => $funds,
        ];
    }
}
