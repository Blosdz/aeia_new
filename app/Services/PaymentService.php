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

            return $fund;
        });
    }

    /**
     * Asignar pagos validados a un fondo
     */
    public function allocatePaymentsToFund(Fund $fund, array $paymentIds, ?array $allocation = null): void
    {
        DB::transaction(function () use ($fund, $paymentIds, $allocation) {
            $payments = Payment::whereIn('id', $paymentIds)->where('status', 'completed')->get();

            foreach ($payments as $payment) {
                // Verificar que el pago tiene suscripción
                if (!$payment->subscription) {
                    $this->validatePayment($payment);
                }

                $subscription = $payment->subscription;

                // Crear InvestmentEarning para esta suscripción en este fondo
                $earning = InvestmentEarning::firstOrCreate(
                    [
                        'subscription_id' => $subscription->id,
                        'fund_id' => $fund->id,
                    ],
                    [
                        'initial_amount' => $payment->amount,
                        'current_amount' => $payment->amount,
                    ]
                );

                // Si es la primera vez, incrementar el current_amount del fondo
                if ($earning->wasRecentlyCreated) {
                    $fund->update([
                        'current_amount' => $fund->current_amount + $payment->amount
                    ]);
                }

                // Crear asignación de pago
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'fund_id' => $fund->id,
                    'amount' => $payment->amount,
                    'status' => 'accrued',
                    'metadata' => $allocation ?? [],
                ]);

                // Registrar en historial de earnings
                InvestmentEarningHistory::create([
                    'earning_id' => $earning->id,
                    'previous_amount' => 0,
                    'new_amount' => floatval($payment->amount),
                    'fluctuation_percent' => 0,
                    'reason' => 'initial_allocation',
                    'recorded_at' => now(),
                    'metadata' => ['event' => 'initial_allocation', 'amount' => $payment->amount]
                ]);
            }

            // Registrar cambio en fondo
            $fundFluctuation = (($fund->current_amount - $fund->initial_amount) / $fund->initial_amount) * 100;
            FundHistory::create([
                'fund_id' => $fund->id,
                'fluctuation_percent' => $fundFluctuation,
                'recorded_at' => now(),
                'metadata' => ['event' => 'payments_allocated', 'payment_count' => count($paymentIds)]
            ]);
        });
    }

    /**
     * Actualizar valor actual del fondo y registrar fluctuación
     * 
     * Ejemplo: Si recaudé 500 y se reporta 521, la fluctuación es +21 (+4.2%)
     * También crea una Subscription para el admin que registra la actualización
     */
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
