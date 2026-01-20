<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\Fund;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RewardController extends Controller
{
    /**
     * Mostrar todos los rewards (por fondo)
     */
    public function index(Request $request)
    {
        $fundId = $request->query('fund_id');
        $status = $request->query('status');
        
        $query = Reward::with([
            'client:id,email,first_name,last_name',
            'fund:id,name,description',
            'deposit:id,payment_id,fund_id,total_amount',
            'referrer:id,email,first_name,last_name'
        ]);

        // Filtrar por fondo si se proporciona
        if ($fundId) {
            $query->where('fund_id', $fundId);
        }

        // Filtrar por estado si se proporciona
        if ($status && in_array($status, ['pending', 'closed', 'paid'])) {
            $query->where('status', $status);
        }

        $rewards = $query
            ->orderBy('closed_at', 'desc')
            ->paginate(15);

        // Obtener lista de fondos para dropdown
        $funds = Fund::select('id', 'name')->orderBy('name')->get();

        // Calcular totales
        $allRewardsQuery = Reward::query();
        if ($fundId) {
            $allRewardsQuery->where('fund_id', $fundId);
        }
        if ($status) {
            $allRewardsQuery->where('status', $status);
        }

        $allRewards = $allRewardsQuery->get();
        $summary = [
            'total_invested' => $allRewards->sum('total_investment'),
            'total_earnings' => $allRewards->sum('total_earnings'),
            'total_deductions' => $allRewards->sum(function ($r) {
                return $r->company_deduction + $r->referral_deduction;
            }),
            'total_net_earnings' => $allRewards->sum('net_earnings'),
            'total_return' => $allRewards->sum(function ($r) {
                return $r->total_investment + $r->net_earnings;
            }),
            'count_by_status' => [
                'paid' => $allRewards->where('status', 'paid')->count(),
                'closed' => $allRewards->where('status', 'closed')->count(),
                'pending' => $allRewards->where('status', 'pending')->count(),
            ]
        ];

        return Inertia::render('Admin/Rewards/Index', [
            'rewards' => $rewards->through(function ($reward) {
                return [
                    'id' => $reward->id,
                    'client_name' => $reward->client ? "{$reward->client->first_name} {$reward->client->last_name}" : 'N/A',
                    'client_email' => $reward->client?->email ?? 'N/A',
                    'fund_name' => $reward->fund?->name ?? 'N/A',
                    'status' => $reward->status,
                    'closed_at' => $reward->closed_at?->format('Y-m-d'),
                    'paid_at' => $reward->paid_at?->format('Y-m-d'),
                    'total_investment' => (float) $reward->total_investment,
                    'total_earnings' => (float) $reward->total_earnings,
                    'company_deduction' => (float) $reward->company_deduction,
                    'referral_deduction' => (float) $reward->referral_deduction,
                    'net_earnings' => (float) $reward->net_earnings,
                    'total_return' => (float) ($reward->total_investment + $reward->net_earnings),
                    'was_referred' => $reward->was_referred,
                    'referrer_name' => $reward->referrer ? "{$reward->referrer->first_name} {$reward->referrer->last_name}" : null,
                ];
            }),
            'funds' => $funds,
            'summary' => $summary,
            'filters' => [
                'fund_id' => $fundId,
                'status' => $status,
            ]
        ]);
    }

    /**
     * Mostrar detalle de un reward
     */
    public function show(Reward $reward)
    {
        $reward->load([
            'client:id,email,first_name,last_name',
            'fund:id,name,description',
            'deposit:id,payment_id,fund_id,total_amount',
            'referrer:id,email,first_name,last_name'
        ]);

        return Inertia::render('Admin/Rewards/Show', [
            'reward' => [
                'id' => $reward->id,
                'client_name' => $reward->client ? "{$reward->client->first_name} {$reward->client->last_name}" : 'N/A',
                'client_email' => $reward->client?->email ?? 'N/A',
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
