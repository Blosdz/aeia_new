<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plans;
use App\Models\Profile;
use App\Models\InvestmentEarning;
use App\Models\InvestmentEarningHistory;
use App\Models\Fund;

class DashboardController extends Controller
{
    /**
     * Dashboard principal del admin
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Verificar que sea admin
        if (!$user->hasRole('admin')) {
            abort(403, 'No tienes permisos de administrador');
        }
        
        // Estadísticas generales
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        
        $totalPayments = Payment::sum('amount');
        $totalPaymentsCount = Payment::count();
        $completedPayments = Payment::where('status', 'completed')->count();
        
        $totalSubscriptions = Subscription::count();
        $totalInvested = Payment::sum('amount');
        
        // Estadísticas de ganancias
        $totalEarningsInitial = InvestmentEarning::sum('initial_amount');
        $totalEarningsCurrent = InvestmentEarning::sum('current_amount');
        $totalGains = $totalEarningsCurrent - $totalEarningsInitial;
        
        // Últimos pagos
        $recentPayments = Payment::with(['payerProfile.user', 'clientAccount', 'subscription.planType'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'user' => $payment->payerProfile?->user?->name ?? 'N/A',
                    'plan' => $payment->subscription?->planType?->name ?? 'N/A',
                    'created_at' => $payment->created_at,
                ];
            });
        
        // Planes más utilizados
        $topPlans = Plans::withCount('subscriptions')
            ->orderByDesc('subscriptions_count')
            ->limit(5)
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'subscriptions_count' => $plan->subscriptions_count,
                    'amount_min' => $plan->amount_min,
                    'amount_max' => $plan->amount_max,
                ];
            });
        
        // Distribución de usuarios por rol
        $usersByRole = User::with('roles')
            ->get()
            ->groupBy(function ($user) {
                return $user->roles->first()?->name ?? 'Sin rol';
            })
            ->map(function ($users) {
                return count($users);
            });
        
        // Gráfico de ganancias - Últimos 30 días usando investment_earnings_history
        $thirtyDaysAgo = now()->subDays(30);
        $earningsHistory = InvestmentEarningHistory::with(['investmentEarning.fund', 'investmentEarning.subscription'])
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
            ];
        }
        
        // Ingresos últimos 30 días (pagos)
        $revenueByDay = Payment::where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Ganancias por fondo
        $earningsByFund = InvestmentEarning::with('fund')
            ->selectRaw('fund_id, SUM(initial_amount) as total_initial, SUM(current_amount) as total_current')
            ->groupBy('fund_id')
            ->get()
            ->map(function ($earning) {
                $gains = $earning->total_current - $earning->total_initial;
                return [
                    'fund_name' => $earning->fund?->name ?? 'N/A',
                    'total_initial' => floatval($earning->total_initial),
                    'total_current' => floatval($earning->total_current),
                    'gains' => floatval($gains),
                    'gains_percent' => $earning->total_initial > 0 ? round(($gains / $earning->total_initial) * 100, 2) : 0,
                ];
            });
        
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_payments' => $totalPaymentsCount,
                'completed_payments' => $completedPayments,
                'total_invested' => $totalInvested,
                'total_subscriptions' => $totalSubscriptions,
                'total_revenue' => $totalPayments,
                'total_earnings_initial' => floatval($totalEarningsInitial),
                'total_earnings_current' => floatval($totalEarningsCurrent),
                'total_gains' => floatval($totalGains),
            ],
            'recent_payments' => $recentPayments,
            'top_plans' => $topPlans,
            'users_by_role' => $usersByRole,
            'revenue_by_day' => $revenueByDay,
            'earnings_chart' => $earningsChart,
            'earnings_by_fund' => $earningsByFund,
        ]);
    }
}
