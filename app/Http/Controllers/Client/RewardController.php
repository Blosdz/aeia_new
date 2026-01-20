<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\Fund;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RewardController extends Controller
{
    /**
     * Mostrar todos los rewards del cliente actual
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Obtener todos los rewards del cliente
        $rewards = Reward::where('client_user_id', $user->id)
            ->with([
                'fund:id,name,description',
                'deposit:id,payment_id,fund_id,total_amount',
                'referrer:id,email,first_name,last_name'
            ])
            ->orderBy('closed_at', 'desc')
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'fund_name' => $reward->fund?->name ?? 'N/A',
                    'fund_description' => $reward->fund?->description ?? '',
                    'status' => $reward->status,
                    'closed_at' => $reward->closed_at?->format('Y-m-d H:i:s'),
                    'paid_at' => $reward->paid_at?->format('Y-m-d H:i:s'),
                    'total_investment' => (float) $reward->total_investment,
                    'total_earnings' => (float) $reward->total_earnings,
                    'company_percentage' => (float) $reward->company_percentage,
                    'company_deduction' => (float) $reward->company_deduction,
                    'was_referred' => $reward->was_referred,
                    'referral_percentage' => (float) $reward->referral_percentage,
                    'referral_deduction' => (float) $reward->referral_deduction,
                    'net_earnings' => (float) $reward->net_earnings,
                    'total_return' => (float) ($reward->total_investment + $reward->net_earnings),
                    'referrer_name' => $reward->referrer ? "{$reward->referrer->first_name} {$reward->referrer->last_name}" : null,
                ];
            });

        // Calcular totales
        $totalInvested = $rewards->sum('total_investment');
        $totalEarnings = $rewards->sum('total_earnings');
        $totalDeductions = $rewards->sum(function ($reward) {
            return $reward['company_deduction'] + $reward['referral_deduction'];
        });
        $totalNetEarnings = $rewards->sum('net_earnings');
        $totalReturn = $rewards->sum('total_return');

        // Rewards por estado
        $rewardsByStatus = [
            'paid' => $rewards->where('status', 'paid')->count(),
            'closed' => $rewards->where('status', 'closed')->count(),
            'pending' => $rewards->where('status', 'pending')->count(),
        ];

        return Inertia::render('Client/Rewards/Index', [
            'rewards' => $rewards,
            'summary' => [
                'total_invested' => $totalInvested,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'total_net_earnings' => $totalNetEarnings,
                'total_return' => $totalReturn,
                'count' => $rewards->count(),
                'by_status' => $rewardsByStatus,
            ],
        ]);
    }

    /**
     * Mostrar detalle de un reward específico
     */
    public function show(Request $request, Reward $reward)
    {
        $user = $request->user();
        
        // Verificar que el reward pertenezca al usuario
        if ($reward->client_user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este reward');
        }

        $reward->load([
            'fund:id,name,description',
            'deposit:id,payment_id,fund_id,total_amount',
            'referrer:id,email,first_name,last_name'
        ]);

        return Inertia::render('Client/Rewards/Show', [
            'reward' => [
                'id' => $reward->id,
                'fund_name' => $reward->fund?->name ?? 'N/A',
                'fund_description' => $reward->fund?->description ?? '',
                'status' => $reward->status,
                'closed_at' => $reward->closed_at?->format('Y-m-d H:i:s'),
                'paid_at' => $reward->paid_at?->format('Y-m-d H:i:s'),
                'total_investment' => (float) $reward->total_investment,
                'total_earnings' => (float) $reward->total_earnings,
                'company_percentage' => (float) $reward->company_percentage,
                'company_deduction' => (float) $reward->company_deduction,
                'was_referred' => $reward->was_referred,
                'referral_percentage' => (float) $reward->referral_percentage,
                'referral_deduction' => (float) $reward->referral_deduction,
                'net_earnings' => (float) $reward->net_earnings,
                'total_return' => (float) ($reward->total_investment + $reward->net_earnings),
                'referrer_name' => $reward->referrer ? "{$reward->referrer->first_name} {$reward->referrer->last_name}" : null,
            ],
        ]);
    }
}
