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
        
        // Últimos pagos
        $recentPayments = Payment::with(['payerProfile.user', 'clientAccount', 'subscriptions.planType'])
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
                    'plan' => $payment->subscriptions?->first()?->planType?->name ?? 'N/A',
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
        
        // Ingresos últimos 30 días
        $thirtyDaysAgo = now()->subDays(30);
        $revenueByDay = Payment::where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_payments' => $totalPaymentsCount,
                'completed_payments' => $completedPayments,
                'total_invested' => $totalInvested,
                'total_subscriptions' => $totalSubscriptions,
                'total_revenue' => $totalPayments,
            ],
            'recent_payments' => $recentPayments,
            'top_plans' => $topPlans,
            'users_by_role' => $usersByRole,
            'revenue_by_day' => $revenueByDay,
        ]);
    }
}
