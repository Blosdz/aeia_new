<?php

namespace App\Services;

use App\Models\PaymentAllocation;
use App\Models\Fund;
use App\Models\ProjectClosure;
use App\Models\Reward;
use App\Models\ReferralCommission;
use Illuminate\Support\Collection;

class ProjectClosureService
{
    /**
     * Cerrar un período de proyecto y distribuir ganancias
     * LÓGICA CORRECTA: Las deducciones se calculan SOBRE LA GANANCIA, no sobre la inversión
     * 
     * FLUJO:
     * 1. Ganancia bruta = Inversión × Rendimiento del fondo
     * 2. Deducción empresa = Ganancia × 20% (SIEMPRE)
     * 3. Deducción referral = Ganancia × 15% (SOLO si es primer pago referido)
     * 4. Ganancia neta = Ganancia - deducción empresa - deducción referral
     * 
     * EJEMPLO SIN REFERENCIA:
     * ├─ Inversión: $1,000
     * ├─ Ganancia bruta: $1,000 × 20% = $200
     * ├─ Empresa: 20% × $200 = $40
     * └─ Ganancia neta: $200 - $40 = $160
     * 
     * EJEMPLO PRIMER PAGO REFERIDO:
     * ├─ Inversión: $1,000
     * ├─ Ganancia bruta: $1,000 × 20% = $200
     * ├─ Empresa: 20% × $200 = $40
     * ├─ Staff: 15% × $200 = $30
     * └─ Ganancia neta: $200 - $40 - $30 = $130
     * 
     * @param Fund $fund El fondo a cerrar
     * @param array $options Opciones: period_start, period_end, fund_yield (default 0.20)
     * @return ProjectClosure
     * @throws \Exception
     */
    public function closeProjectPeriod(Fund $fund, array $options = []): ProjectClosure
    {
        // Obtener asignaciones de pago activas del fondo
        $allocations = PaymentAllocation::where('fund_id', $fund->id)
            ->where('status', '!=', 'cancelled')
            ->with(['payment.subscription', 'subscription'])
            ->get();

        if ($allocations->isEmpty()) {
            throw new \Exception("No hay inversiones activas para cerrar el fondo: {$fund->name}");
        }

        // Rendimiento del fondo (default 20%)
        $fundYield = $options['fund_yield'] ?? 0.20;

        // Totales generales
        $totalInvestment = $allocations->sum('amount');
        $totalGainBrute = $totalInvestment * $fundYield;
        $totalClients = $allocations->groupBy(function ($allocation) {
            return $allocation->payment->payerProfile->user_id ?? null;
        })->filter()->count();

        // PASO 1: Calcular deducciones POR CLIENTE (sobre la GANANCIA, no la inversión)
        $companyTotal = 0;
        $referralsTotal = 0;
        $clientsEarningsTotal = 0;

        foreach ($allocations as $allocation) {
            // Obtener información del cliente desde el payment
            $payment = $allocation->payment;
            if (!$payment || !$payment->payerProfile) continue;
            
            $clientUserId = $payment->payerProfile->user_id;
            if (!$clientUserId) continue;

            $investment = $allocation->amount;
            
            // PASO 1: Calcular ganancia BRUTA del cliente
            $gainBrute = $investment * $fundYield;
            
            // PASO 2: Deducción EMPRESA = 20% de la GANANCIA
            $companyDeduction = $gainBrute * 0.20;
            $companyTotal += $companyDeduction;

            // PASO 3: Deducción REFERRAL = 15% de la GANANCIA (solo primer depósito referido)
            $referralDeduction = 0;
            $wasReferred = false;
            $referrerId = null;

            // Verificar si es cliente referido (buscar en ReferralCommission)
            $referralRecord = ReferralCommission::where('referred_user_id', $clientUserId)
                ->where('is_first_deposit', true)
                ->first();

            if ($referralRecord) {
                // Verificar si es primer depósito del cliente EN ESTE FONDO
                $firstDepositInFund = PaymentAllocation::where('fund_id', $fund->id)
                    ->whereHas('payment', function ($query) use ($clientUserId) {
                        $query->whereHas('payerProfile', function ($q) use ($clientUserId) {
                            $q->where('user_id', $clientUserId);
                        });
                    })
                    ->orderBy('created_at')
                    ->first();

                $isFirstDeposit = $firstDepositInFund && $firstDepositInFund->id === $allocation->id;

                if ($isFirstDeposit) {
                    $wasReferred = true;
                    $referrerId = $referralRecord->staff_user_id;
                    $referralDeduction = $gainBrute * 0.15; // 15% de la GANANCIA
                    $referralsTotal += $referralDeduction;
                }
            }

            // PASO 4: Ganancia neta del cliente = Ganancia bruta - empresa - referral
            $netEarnings = $gainBrute - $companyDeduction - $referralDeduction;
            $clientsEarningsTotal += $netEarnings;

            // Crear Reward para esta asignación
            $this->createRewardForAllocationCorrected(
                $allocation,
                $fund,
                $clientUserId,
                $investment,              // Inversión original
                $gainBrute,               // Ganancia bruta
                $companyDeduction,        // Deducción empresa (sobre ganancia)
                $wasReferred,
                $referrerId,
                $referralDeduction,       // Deducción referral (sobre ganancia)
                $netEarnings              // Ganancia neta del cliente
            );
        }

        // PASO 2: Crear registro de cierre
        $closure = ProjectClosure::create([
            'fund_id' => $fund->id,
            'period_start' => $options['period_start'] ?? $fund->created_at->toDateString(),
            'period_end' => $options['period_end'] ?? now()->toDateString(),
            'total_investment' => $totalInvestment,
            'total_earnings' => $totalGainBrute, // Ganancia BRUTA total del fondo
            'total_clients' => $totalClients,
            'company_total' => $companyTotal,
            'referrals_total' => $referralsTotal,
            'clients_earnings' => $clientsEarningsTotal,
            'total_referrals' => ReferralCommission::where('is_first_deposit', true)->count(),
            'first_deposits_referred' => ReferralCommission::where('is_first_deposit', true)->count(),
            'status' => 'closed',
            'calculated_at' => now(),
            'distributed_at' => now(),
            'closed_at' => now(),
        ]);

        // Actualizar estado del fondo
        $fund->update(['status' => 'closed']);
        return $closure;
    }

    /**
     * Crear reward para una asignación de pago con LÓGICA CORRECTA
     * Deducciones sobre GANANCIA: empresa 20%, referral 15% (solo 1er pago)
     */
    private function createRewardForAllocationCorrected(
        PaymentAllocation $allocation,
        Fund $fund,
        int $clientUserId,
        float $originalInvestment,  // Inversión original
        float $gainBrute,           // Ganancia bruta (inversión × rendimiento)
        float $companyDeduction,    // Deducción empresa (20% de ganancia)
        bool $wasReferred,
        ?int $referrerId,
        float $referralDeduction,   // Deducción referral (15% de ganancia, solo 1er pago)
        float $netEarnings          // Ganancia final del cliente
    ): Reward
    {
        // Crear reward con la lógica correcta
        $reward = Reward::create([
            'client_user_id' => $clientUserId,
            'fund_id' => $fund->id,
            'deposit_id' => $allocation->id,
            'total_investment' => $originalInvestment,          // Inversión original
            'total_earnings' => $gainBrute,                     // Ganancia bruta del cliente
            'company_percentage' => 20,
            'company_deduction' => $companyDeduction,           // Empresa: 20% de ganancia
            'was_referred' => $wasReferred,
            'referrer_user_id' => $referrerId,
            'referral_percentage' => $wasReferred ? 15 : 0,     // 15% si primer depósito
            'referral_deduction' => $referralDeduction,         // Staff: 15% de ganancia (solo 1er pago)
            'net_earnings' => $netEarnings,                     // Ganancia neta del cliente
            'reason' => 'closure',
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Actualizar comisión del referido si aplica
        if ($wasReferred && $referrerId) {
            ReferralCommission::where('referred_user_id', $clientUserId)
                ->where('staff_user_id', $referrerId)
                ->where('is_first_deposit', true)
                ->update([
                    'commission_amount' => $referralDeduction, // Comisión = 15% de ganancia
                    'reward_id' => $reward->id,
                    'status' => 'paid',
                ]);
        }

        return $reward;
    }

    /**
     * Obtener resumen de cierre de un fondo
     */
    public function getClosureSummary(Fund $fund): array
    {
        $closure = ProjectClosure::where('fund_id', $fund->id)
            ->orderByDesc('closed_at')
            ->first();

        if (!$closure) {
            return [];
        }

        $rewards = $closure->rewards;
        $commissions = ReferralCommission::whereIn(
            'reward_id',
            $rewards->pluck('id')
        )->get();

        return [
            'closure' => $closure,
            'total_rewards' => $rewards->count(),
            'total_referral_commissions' => $commissions->count(),
            'average_client_earning' => $rewards->avg('net_earnings'),
            'highest_earning' => $rewards->max('net_earnings'),
            'lowest_earning' => $rewards->min('net_earnings'),
            'total_commissions_paid' => $commissions->sum('commission_amount'),
        ];
    }

    /**
     * Obtener todos los rewards de un fondo con detalle completo
     * Para mostrar en la tabla de distribución
     */
    public function getFundRewardsWithDetails(Fund $fund): array
    {
        $rewards = Reward::where('fund_id', $fund->id)
            ->with(['client', 'referrer', 'deposit'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'client_name' => $reward->client?->name ?? 'N/A',
                    'client_email' => $reward->client?->email ?? 'N/A',
                    'investment' => $reward->total_investment,
                    'individual_gain' => $reward->net_earnings, // La ganancia real del cliente
                    'company_percentage' => $reward->company_percentage,
                    'company_deduction' => $reward->company_deduction,
                    'was_referred' => $reward->was_referred,
                    'referrer_name' => $reward->was_referred ? ($reward->referrer?->name ?? 'N/A') : null,
                    'referral_percentage' => $reward->referral_percentage,
                    'referral_deduction' => $reward->referral_deduction,
                    'net_earnings' => $reward->net_earnings,
                    'status' => $reward->status,
                    'closed_at' => $reward->closed_at,
                ];
            })->toArray();

        return $rewards;
    }

    /**
     * Obtener resumen de distribución del cierre
     * Totales por: Empresa, Staff, Clientes
     */
    public function getFundDistributionSummary(Fund $fund): array
    {
        $closure = ProjectClosure::where('fund_id', $fund->id)
            ->where('status', 'closed')
            ->latest()
            ->first();

        if (!$closure) {
            return [
                'total_investment' => 0,
                'total_fund_earnings' => 0,
                'company_total' => 0,
                'company_percentage' => 0,
                'referrals_total' => 0,
                'referrals_percentage' => 0,
                'clients_total' => 0,
                'clients_percentage' => 0,
                'total_participants' => 0,
                'first_deposits_referred' => 0,
                'status' => 'No cerrado',
            ];
        }

        $totalEarnings = $closure->total_earnings;

        return [
            'total_investment' => $closure->total_investment,
            'total_fund_earnings' => $totalEarnings,
            'company_total' => $closure->company_total,
            'company_percentage' => $totalEarnings > 0 ? ($closure->company_total / $totalEarnings) * 100 : 0,
            'referrals_total' => $closure->referrals_total,
            'referrals_percentage' => $totalEarnings > 0 ? ($closure->referrals_total / $totalEarnings) * 100 : 0,
            'clients_total' => $closure->clients_earnings,
            'clients_percentage' => $totalEarnings > 0 ? ($closure->clients_earnings / $totalEarnings) * 100 : 0,
            'total_participants' => $closure->total_clients,
            'first_deposits_referred' => $closure->first_deposits_referred,
            'status' => $closure->status,
            'closed_at' => $closure->closed_at,
        ];
    }

    /**
     * Obtener rewards de un cliente
     */
    public function getClientRewards(int $userId, ?int $fundId = null)
    {
        $query = Reward::where('client_user_id', $userId)
            ->with(['fund', 'referrer', 'deposit']);

        if ($fundId) {
            $query->where('fund_id', $fundId);
        }

        return $query->orderByDesc('closed_at')->get();
    }

    /**
     * Obtener comisiones de un staff
     */
    public function getStaffCommissions(int $staffUserId, ?int $fundId = null)
    {
        $query = ReferralCommission::where('staff_user_id', $staffUserId)
            ->with(['reward', 'referredUser', 'fundClosure']);

        if ($fundId) {
            $query->whereHas('reward', function ($q) use ($fundId) {
                $q->where('fund_id', $fundId);
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Calcular ganancia total de un cliente
     */
    public function getClientTotalEarnings(int $userId): float
    {
        return Reward::where('client_user_id', $userId)
            ->where('status', 'closed')
            ->sum('net_earnings');
    }

    /**
     * Calcular comisión total de un staff
     */
    public function getStaffTotalCommissions(int $staffUserId): float
    {
        return ReferralCommission::where('staff_user_id', $staffUserId)
            ->where('status', '!=', 'pending')
            ->sum('commission_amount');
    }
}
