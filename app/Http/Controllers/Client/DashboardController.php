<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Subscription;
use App\Models\InvestmentEarning;
use App\Models\InvestmentEarningHistory;

class DashboardController extends Controller
{
    /**
     * Mostrar dashboard del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Manejar caso donde el perfil no existe
        if (!$profile) {
            return Inertia::render('Client/Dashboard', [
                'profile' => null,
                'subscriptions' => [],
                'totalInvested' => 0,
                'totalCurrentValue' => 0,
                'totalGains' => 0,
                'gainPercent' => 0,
                'activeSubscriptions' => 0,
                'totalRevenue' => 0,
                'closureDays' => 0,
                'earningsChart' => [],
                'earningsByFund' => [],
            ]);
        }
        
        // Obtener suscripciones del cliente
        $subscriptions = Subscription::where('profile_id', $profile->id)
            ->with(['planType', 'investmentEarnings.fund'])
            ->get();
        
        // Calcular totales
        $totalInvested = 0;
        $totalCurrentValue = 0;
        $totalRevenue = 0;
        $activeSubscriptions = 0;
        
        if ($subscriptions->isNotEmpty()) {
            $totalInvested = $subscriptions->sum(function ($sub) {
                return $sub->investmentEarnings?->sum('initial_amount') ?? 0;
            });
            
            $totalCurrentValue = $subscriptions->sum(function ($sub) {
                return $sub->investmentEarnings?->sum('current_amount') ?? 0;
            });
            
            $totalRevenue = $totalCurrentValue - $totalInvested;
            $activeSubscriptions = $subscriptions->count();
        }
        
        $gainPercent = $totalInvested > 0 ? (($totalRevenue / $totalInvested) * 100) : 0;
        
        // Gráfico de ganancias - Últimos 30 días
        $thirtyDaysAgo = now()->subDays(30);
        $subscriptionIds = $subscriptions->pluck('id');
        
        $earningsHistory = InvestmentEarningHistory::whereHas('investmentEarning', function ($query) use ($subscriptionIds) {
                $query->whereIn('subscription_id', $subscriptionIds);
            })
            ->with(['investmentEarning.fund'])
            ->where('recorded_at', '>=', $thirtyDaysAgo)
            ->orderBy('recorded_at')
            ->get();
        
        // Agrupar por día y calcular ganancias acumuladas
        $earningsChart = [];
        $cumulativeGains = 0;
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('d/m');
            
            // Obtener cambios del día
            $dayHistory = $earningsHistory->filter(function ($history) use ($date) {
                return $history->recorded_at->format('Y-m-d') === $date;
            });
            
            $dayGains = $dayHistory->sum(function ($history) {
                return ($history->new_amount ?? 0) - ($history->previous_amount ?? 0);
            });
            
            $cumulativeGains += $dayGains;
            
            $earningsChart[] = [
                'date' => $dateLabel,
                'gains' => round($cumulativeGains, 2),
                'daily_gains' => round($dayGains, 2),
                'current_value' => round($totalInvested + $cumulativeGains, 2),
            ];
        }
        
        // Ganancias por fondo del cliente
        $earningsByFund = InvestmentEarning::whereIn('subscription_id', $subscriptionIds)
            ->with('fund')
            ->get()
            ->groupBy('fund_id')
            ->map(function ($earnings, $fundId) {
                $totalInitial = $earnings->sum('initial_amount');
                $totalCurrent = $earnings->sum('current_amount');
                $gains = $totalCurrent - $totalInitial;
                
                return [
                    'fund_name' => $earnings->first()->fund?->name ?? 'N/A',
                    'fund_category' => $earnings->first()->fund?->category ?? 'N/A',
                    'total_initial' => floatval($totalInitial),
                    'total_current' => floatval($totalCurrent),
                    'gains' => floatval($gains),
                    'gains_percent' => $totalInitial > 0 ? round(($gains / $totalInitial) * 100, 2) : 0,
                ];
            })->values();
        
        return Inertia::render('Client/Dashboard', [
            'profile' => $profile,
            'subscriptions' => $subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'unique_code' => $sub->unique_code,
                    'plan_type' => $sub->planType,
                    'started_at' => $sub->started_at,
                    'expires_at' => $sub->expires_at,
                    'investment_earnings' => $sub->investmentEarnings->map(function ($earning) {
                        return [
                            'id' => $earning->id,
                            'fund' => $earning->fund,
                            'initial_amount' => floatval($earning->initial_amount),
                            'current_amount' => floatval($earning->current_amount),
                            'gains' => floatval($earning->current_amount - $earning->initial_amount),
                        ];
                    }),
                ];
            }),
            'totalInvested' => floatval($totalInvested),
            'totalCurrentValue' => floatval($totalCurrentValue),
            'totalGains' => floatval($totalRevenue),
            'gainPercent' => round($gainPercent, 2),
            'activeSubscriptions' => $activeSubscriptions,
            'totalRevenue' => floatval($totalRevenue),
            'closureDays' => 42,
            'earningsChart' => $earningsChart,
            'earningsByFund' => $earningsByFund,
        ]);
    }
}
