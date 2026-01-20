<?php

namespace App\Services\Traits;

use App\Models\ReferralCommission;
use App\Models\ReferralCommissionHistory;
use App\Models\SubscriptionParticipant;

/**
 * Trait para manejar comisiones de referidos
 * 
 * Se integra en PaymentService para procesar comisiones cuando se generan fondos
 */
trait ReferralCommissionTrait
{
    /**
     * Agregar advisor (referidor) como participante en la suscripción
     * 
     * @param \App\Models\Subscription $subscription
     * @param int|null $advisorProfileId - ID del perfil del advisor/referidor
     * @param float $sharePercent - Porcentaje de participación (comisión)
     * @return \App\Models\SubscriptionParticipant|null
     */
    public function addAdvisorParticipant($subscription, ?int $advisorProfileId, float $sharePercent = 5.0)
    {
        if (!$advisorProfileId) {
            return null;
        }

        // Verificar que no exista ya
        $existing = SubscriptionParticipant::where([
            'subscription_id' => $subscription->id,
            'profile_id' => $advisorProfileId,
            'role' => 'advisor',
        ])->first();

        if ($existing) {
            return $existing;
        }

        // Crear participante como advisor
        $participant = SubscriptionParticipant::create([
            'subscription_id' => $subscription->id,
            'profile_id' => $advisorProfileId,
            'role' => 'advisor',
            'share_percent' => $sharePercent,
            'is_primary_owner' => false,
            'started_at' => now(),
        ]);

        return $participant;
    }

    /**
     * Calcular y registrar comisión cuando se actualiza el valor del fondo
     * 
     * @param \App\Models\Fund $fund
     * @param float $fluctuation - Cambio de valor del fondo
     * @return void
     */
    public function calculateAdvisorCommissions($fund, float $fluctuation): void
    {
        // Obtener todos los earnings del fondo
        $earnings = $fund->investmentEarnings()->with('subscription.participants')->get();

        foreach ($earnings as $earning) {
            $subscription = $earning->subscription;

            // Buscar participants con role 'advisor'
            $advisors = $subscription->participants()
                ->where('role', 'advisor')
                ->get();

            foreach ($advisors as $advisor) {
                // Calcular comisión basada en porcentaje de participación
                $advisorShare = ($fluctuation * $advisor->share_percent) / 100;

                // Buscar o crear registro de comisión
                $commission = ReferralCommission::updateOrCreate(
                    [
                        'advisor_profile_id' => $advisor->profile_id,
                        'subscription_participant_id' => $advisor->id,
                    ],
                    [
                        'commission_percentage' => $advisor->share_percent,
                    ]
                );

                // Registrar historial de cambio
                $previousAmount = floatval($commission->commission_amount);
                $newAmount = $previousAmount + $advisorShare;

                ReferralCommissionHistory::create([
                    'commission_id' => $commission->id,
                    'previous_amount' => $previousAmount,
                    'new_amount' => $newAmount,
                    'event' => 'calculated',
                    'reason' => 'Fund value update - ' . $fund->name,
                    'recorded_at' => now(),
                    'metadata' => [
                        'fund_id' => $fund->id,
                        'fund_name' => $fund->name,
                        'fluctuation' => $fluctuation,
                        'share_percent' => $advisor->share_percent,
                    ]
                ]);

                // Actualizar monto acumulado de comisión
                $commission->markAsCalculated($newAmount);
            }
        }
    }

    /**
     * Obtener resumen de comisiones pendientes del advisor
     * 
     * @param int $advisorProfileId
     * @return array
     */
    public function getAdvisorCommissionsSummary(int $advisorProfileId): array
    {
        $commissions = ReferralCommission::where('advisor_profile_id', $advisorProfileId)
            ->get();

        $pending = $commissions
            ->where('status', 'pending')
            ->sum('commission_amount');

        $calculated = $commissions
            ->where('status', 'calculated')
            ->sum('commission_amount');

        $paid = $commissions
            ->where('status', 'paid')
            ->sum('commission_amount');

        return [
            'total_pending' => floatval($pending),
            'total_calculated' => floatval($calculated),
            'total_paid' => floatval($paid),
            'total_commissions' => floatval($pending + $calculated + $paid),
            'commission_count' => $commissions->count(),
        ];
    }
}
