<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\ReferralCommission;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\InvestmentEarning;
use App\Models\InvestmentEarningHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard principal del staff
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        // Verificar que sea staff
        if ($user->type !== 'staff' && !$user->hasRole('staff')) {
            abort(403, 'No tienes acceso a este recurso');
        }

        // 1. Obtener usuarios referidos (NO depende de perfil)
        $referredUsers = User::where('referred_by_user_id', $user->id)->get();
        $totalReferrals = $referredUsers->count();

        // 2. Contar conversiones (usuarios que hicieron pago)
        $convertedReferrals = $referredUsers
            ->filter(function ($u) {
                return Payment::where('payer_profile_id', $u->profile?->id)
                    ->where('status', 'completed')
                    ->exists();
            })
            ->count();

        $conversionRate = $totalReferrals > 0 ? ($convertedReferrals / $totalReferrals) * 100 : 0;

        // 3. Obtener comisiones del staff (SÍ depende de perfil)
        $totalPending = 0;
        $totalCalculated = 0;
        $totalPaid = 0;
        $totalCommissions = 0;
        $latestCommissions = [];
        $commissions = collect([]);

        if ($profile) {
            $commissions = ReferralCommission::where('advisor_profile_id', $profile->id)
                ->with('subscriptionParticipant.subscription.payment')
                ->get() ?? collect([]);

            $totalPending = $commissions->count() > 0 
                ? $commissions->where('status', 'pending')->sum('commission_amount') 
                : 0;

            $totalCalculated = $commissions->count() > 0 
                ? $commissions->where('status', 'calculated')->sum('commission_amount') 
                : 0;

            $totalPaid = $commissions->count() > 0 
                ? $commissions->where('status', 'paid')->sum('commission_amount') 
                : 0;

            $totalCommissions = $totalPending + $totalCalculated + $totalPaid;

            // 6. Últimas comisiones
            $latestCommissions = $commissions->count() > 0
                ? $commissions
                    ->sortByDesc('updated_at')
                    ->take(5)
                    ->map(function ($commission) {
                        return [
                            'id' => $commission->id,
                            'subscription_code' => $commission->subscriptionParticipant?->subscription?->unique_code ?? 'N/A',
                            'amount' => floatval($commission->commission_amount ?? 0),
                            'percentage' => floatval($commission->commission_percentage ?? 0),
                            'status' => $commission->status ?? 'pending',
                            'calculated_at' => $commission->calculated_at,
                            'paid_at' => $commission->paid_at,
                        ];
                    })->values()->all()
                : [];
        }

        // 4. Gráfico de conversión (últimos 30 días)
        $conversionChart = $this->getConversionChart($referredUsers);

        // 5. Gráfico de comisiones por mes
        $commissionsChart = $this->getCommissionsChart($commissions);
        
    // Gráfico de ganancias de clientes referidos
    $referralsEarningsChart = $this->getReferralsEarningsChart($referredUsers);

        // 7. Detalles de referidos
        $referralsDetail = $referredUsers->count() > 0
            ? $referredUsers->map(function ($u) {
                $profile = $u->profile;
                $payments = Payment::where('payer_profile_id', $profile?->id)
                    ->where('status', 'completed')
                    ->get() ?? collect([]);

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'created_at' => $u->created_at,
                    'is_converted' => $payments->count() > 0,
                    'payment_count' => $payments->count(),
                    'total_invested' => floatval($payments->sum('amount') ?? 0),
                    'status' => $payments->count() > 0 ? 'Activo' : 'Pendiente',
                ];
            })->values()->all()
            : [];

        return Inertia::render('Staff/Dashboard/Index', [
            'stats' => [
                'total_referrals' => $totalReferrals,
                'converted_referrals' => $convertedReferrals,
                'conversion_rate' => round($conversionRate, 2),
                'total_commissions' => floatval($totalCommissions ?? 0),
                'pending_commissions' => floatval($totalPending ?? 0),
                'calculated_commissions' => floatval($totalCalculated ?? 0),
                'paid_commissions' => floatval($totalPaid ?? 0),
                'staff_referral_code' => $user->referral_code ?? $user->getReferralCode(),
            ],
            'charts' => [
                'conversion' => $conversionChart,
                'commissions' => $commissionsChart,
                            'referrals_earnings' => $referralsEarningsChart,
            ],
            'latest_commissions' => $latestCommissions,
            'referrals' => $referralsDetail,
        ]);
    }

    /**
     * Gráfico de conversión (Últimos 30 días)
     */
    private function getConversionChart($referredUsers)
    {
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('d/m');
            $dayEnd = now()->subDays($i)->endOfDay();

            $referralsDay = $referredUsers->filter(function ($u) use ($date) {
                return $u->created_at->format('Y-m-d') === $date;
            })->count();

            $conversionsDay = $referredUsers->filter(function ($u) use ($date, $dayEnd) {
                $userHasPayment = Payment::where('payer_profile_id', $u->profile?->id)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $u->created_at)
                    ->where('created_at', '<=', $dayEnd)
                    ->exists();

                return $u->created_at->format('Y-m-d') === $date && $userHasPayment;
            })->count();

            $data[] = [
                'date' => $dateLabel,
                'referrals' => $referralsDay,
                'conversions' => $conversionsDay,
            ];
        }

        return $data;
    }

    /**
     * Gráfico de comisiones por mes
     */
    private function getCommissionsChart($commissions)
    {
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('M/y');

            $commissionsMonth = $commissions->filter(function ($c) use ($month) {
                return $c->calculated_at && $c->calculated_at->format('Y-m') === $month->format('Y-m');
            })->sum('commission_amount');

            $data[] = [
                'month' => $monthLabel,
                'amount' => floatval($commissionsMonth),
            ];
        }

        return $data;
    }

    /**
     * Detalle de comisiones
     */
    public function commissions(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        // Si no tiene perfil, retornar vacío
        if (!$profile) {
            return Inertia::render('Staff/Commissions/Index', [
                'commissions' => [],
            ]);
        }

        $commissions = ReferralCommission::where('advisor_profile_id', $profile->id)
            ->with([
                'subscriptionParticipant.subscription.payment',
                'histories' => function ($query) {
                    $query->orderByDesc('recorded_at');
                }
            ])
            ->orderByDesc('updated_at')
            ->paginate(15);

        $commissionsData = $commissions->map(function ($commission) {
            return [
                'id' => $commission->id,
                'subscription_code' => $commission->subscriptionParticipant?->subscription?->unique_code ?? 'N/A',
                'subscription_id' => $commission->subscriptionParticipant?->subscription?->id,
                'amount' => floatval($commission->commission_amount ?? 0),
                'percentage' => floatval($commission->commission_percentage ?? 0),
                'status' => $commission->status ?? 'pending',
                'calculated_at' => $commission->calculated_at,
                'paid_at' => $commission->paid_at,
                'histories' => $commission->histories ? $commission->histories->map(function ($h) {
                    return [
                        'event' => $h->event ?? 'N/A',
                        'previous_amount' => floatval($h->previous_amount ?? 0),
                        'new_amount' => floatval($h->new_amount ?? 0),
                        'reason' => $h->reason ?? 'N/A',
                        'recorded_at' => $h->recorded_at,
                    ];
                })->all() : [],
            ];
        });

        return Inertia::render('Staff/Commissions/Index', [
            'commissions' => $commissionsData,
        ]);
    }

    /**
     * Detalle de referidos
     */
    public function referrals(Request $request)
    {
        $user = $request->user();

        $referrals = User::where('referred_by_user_id', $user->id)
            ->with('profile')
            ->orderByDesc('created_at')
            ->paginate(15);

        $referralsData = $referrals->map(function ($u) {
            $profile = $u->profile;
            $payments = Payment::where('payer_profile_id', $profile?->id)
                ->where('status', 'completed')
                ->get() ?? collect([]);

            $subscriptions = Subscription::whereHas('payment', function ($query) use ($profile) {
                $query->where('payer_profile_id', $profile?->id);
            })->count();

            $commissions = ReferralCommission::whereHas('subscriptionParticipant.subscription.payment', function ($query) use ($profile) {
                $query->where('payer_profile_id', $profile?->id);
            })->sum('commission_amount') ?? 0;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at,
                'payment_count' => $payments->count(),
                'subscription_count' => $subscriptions,
                'total_invested' => floatval($payments->sum('amount') ?? 0),
                'advisor_commission' => floatval($commissions ?? 0),
                'status' => $payments->count() > 0 ? 'Activo' : 'Pendiente',
            ];
        });

        return Inertia::render('Staff/Referrals/Index', [
            'referrals' => $referralsData,
        ]);
    }
    /**
     * Gráfico de ganancias acumuladas de clientes referidos
     */
    private function getReferralsEarningsChart($referredUsers)
    {
        $data = [];
        $thirtyDaysAgo = now()->subDays(30);
        
        // Obtener IDs de perfiles de referidos
        $referredProfileIds = $referredUsers->map(fn($u) => $u->profile?->id)->filter();
        
        if ($referredProfileIds->isEmpty()) {
            for ($i = 29; $i >= 0; $i--) {
                $dateLabel = now()->subDays($i)->format('d/m');
                $data[] = [
                    'date' => $dateLabel,
                    'earnings' => 0,
                    'cumulative_earnings' => 0,
                ];
            }
            return $data;
        }
        
        // Obtener suscripciones de referidos
        $subscriptionIds = Subscription::whereIn('profile_id', $referredProfileIds)
            ->pluck('id');
        
        // Obtener historial de ganancias
        $earningsHistory = InvestmentEarningHistory::whereHas('investmentEarning', function ($query) use ($subscriptionIds) {
                $query->whereIn('subscription_id', $subscriptionIds);
            })
            ->where('recorded_at', '>=', $thirtyDaysAgo)
            ->orderBy('recorded_at')
            ->get();
        
        $cumulativeEarnings = 0;
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('d/m');
            
            // Obtener cambios del día
            $dayHistory = $earningsHistory->filter(function ($history) use ($date) {
                return $history->recorded_at->format('Y-m-d') === $date;
            });
            
            $dayEarnings = $dayHistory->sum(function ($history) {
                return ($history->new_amount ?? 0) - ($history->previous_amount ?? 0);
            });
            
            $cumulativeEarnings += $dayEarnings;
            
            $data[] = [
                'date' => $dateLabel,
                'earnings' => round($dayEarnings, 2),
                'cumulative_earnings' => round($cumulativeEarnings, 2),
            ];
        }

        return $data;
    }
}
