<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Subscription;
use App\Models\InvestmentEarning;

class SubscriptionController extends Controller
{
    /**
     * Listar suscripciones del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no tiene perfil, retornar colección vacía
        if (!$profile) {
            return Inertia::render('Client/Subscriptions/Index', [
                'subscriptions' => [],
            ]);
        }
        
        // Obtener suscripciones con sus relaciones
        // Solo obtener suscripciones que tengan investment earnings (fondos asignados)
        $subscriptions = Subscription::with([
            'payment' => function ($query) use ($profile) {
                $query->where('payer_profile_id', $profile->id);
            },
            'planType',
            'investmentEarnings' => function ($query) {
                $query->with(['fund', 'investmentEarningHistories' => function ($query) {
                    $query->orderByDesc('recorded_at')->limit(5);
                }]);
            },
            'subscriptionParticipants' => function ($query) {
                $query->with(['profile', 'investmentEarning.fund'])->orderBy('is_primary_owner', 'desc');
            },
        ])
        ->whereHas('payment', function ($query) use ($profile) {
            $query->where('payer_profile_id', $profile->id);
        })
        ->whereHas('investmentEarnings') // Solo suscripciones con fondos asignados
        ->orderByDesc('created_at')
        ->paginate(10);
        
        // Mapear datos para el frontend
        $subscriptionsData = $subscriptions->getCollection()->map(function ($subscription) {
            $payment = $subscription->payment;
            $earnings = $subscription->investmentEarnings;

            // Calcular ganancias totales
            $totalEarnings = 0;
            $totalCurrent = 0;
            $associatedFunds = [];

            foreach ($earnings as $earning) {
                $totalEarnings += ($earning->current_amount - $earning->initial_amount);
                $totalCurrent += $earning->current_amount;

                if ($earning->fund) {
                    $associatedFunds[] = [
                        'id' => $earning->fund->id,
                        'name' => $earning->fund->name,
                        'category' => $earning->fund->category,
                        'initial_amount' => floatval($earning->initial_amount),
                        'current_amount' => floatval($earning->current_amount),
                        'earning_amount' => floatval($earning->current_amount - $earning->initial_amount),
                        'earning_percentage' => $earning->initial_amount > 0
                            ? (($earning->current_amount - $earning->initial_amount) / $earning->initial_amount) * 100
                            : 0,
                        'recent_history' => $earning->investmentEarningHistories->map(function ($history) {
                            return [
                                'id' => $history->id,
                                'previous_amount' => floatval($history->previous_amount),
                                'new_amount' => floatval($history->new_amount),
                                'change' => floatval($history->new_amount) - floatval($history->previous_amount),
                                'fluctuation_percent' => floatval($history->fluctuation_percent),
                                'reason' => $history->reason,
                                'recorded_at' => $history->recorded_at,
                            ];
                        })->toArray(),
                    ];
                }
            }

            // Obtener participantes de la suscripción
            $participants = $subscription->subscriptionParticipants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'role' => $participant->role,
                    'share_percent' => floatval($participant->share_percent ?? 0),
                    'final_investment_amount' => floatval($participant->final_investment_amount ?? 0),
                    'is_primary_owner' => $participant->is_primary_owner,
                    'participating' => $participant->participating,
                    'started_at' => $participant->started_at,
                    'ended_at' => $participant->ended_at,
                    'is_active' => $participant->isActive(),
                    'profile' => $participant->profile ? [
                        'id' => $participant->profile->id,
                        'first_name' => $participant->profile->first_name,
                        'last_name' => $participant->profile->last_name,
                        'email' => $participant->profile->email,
                    ] : null,
                    'investment_earning' => $participant->investmentEarning ? [
                        'id' => $participant->investmentEarning->id,
                        'fund_id' => $participant->investmentEarning->fund_id,
                        'fund_name' => $participant->investmentEarning->fund->name ?? null,
                        'initial_amount' => floatval($participant->investmentEarning->initial_amount),
                        'current_amount' => floatval($participant->investmentEarning->current_amount),
                        'earning' => floatval($participant->investmentEarning->current_amount - $participant->investmentEarning->initial_amount),
                        'participant_earning' => $participant->share_percent > 0
                            ? floatval(($participant->investmentEarning->current_amount - $participant->investmentEarning->initial_amount) * ($participant->share_percent / 100))
                            : 0,
                    ] : null,
                ];
            })->toArray();

            return [
                'id' => $subscription->id,
                'unique_code' => $subscription->unique_code,
                'payment' => [
                    'id' => $payment->id,
                    'amount' => floatval($payment->amount),
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'membership_charge' => floatval($payment->metadata['membership_applied'] ?? 0),
                    'total_paid' => floatval($payment->getTotalWithMembership()),
                    'created_at' => $payment->created_at,
                ],
                'plan' => [
                    'id' => $subscription->planType->id,
                    'name' => $subscription->planType->name,
                    'category' => $subscription->planType->category,
                    'amount_min' => floatval($subscription->planType->amount_min),
                    'amount_max' => floatval($subscription->planType->amount_max),
                    'membership' => floatval($subscription->planType->membership),
                ],
                'started_at' => $subscription->started_at,
                'expires_at' => $subscription->expires_at,
                'associated_funds' => $associatedFunds,
                'participants' => $participants,
                'total_invested' => floatval($subscription->investmentEarnings->sum('initial_amount')),
                'total_current_value' => floatval($totalCurrent),
                'total_earnings' => floatval($totalEarnings),
                'earnings_percentage' => $subscription->investmentEarnings->sum('initial_amount') > 0
                    ? ($totalEarnings / $subscription->investmentEarnings->sum('initial_amount')) * 100
                    : 0,
            ];
        });

        // Reemplazar la colección mapeada en el paginador
        $subscriptions->setCollection($subscriptionsData);

        return Inertia::render('Client/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Ver detalles de una suscripción
     * 
     * @param Request $request
     * @param Subscription $subscription
     * @return \Inertia\Response
     */
    public function show(Request $request, Subscription $subscription)
    {
        $user = $request->user();
        
        // Verificar que el usuario sea el propietario
        if (!$subscription->payment || $subscription->payment->payerProfile->user_id !== $user->id) {
            abort(403);
        }
        
        $subscription->load([
            'payment',
            'planType',
            'investmentEarnings' => function ($query) {
                $query->with(['fund', 'investmentEarningHistories' => function ($query) {
                    $query->orderByDesc('recorded_at');
                }]);
            },
            'subscriptionParticipants' => function ($query) {
                $query->with(['profile', 'investmentEarning.fund'])->orderBy('is_primary_owner', 'desc');
            },
        ]);
        
        $payment = $subscription->payment;
        $earnings = $subscription->investmentEarnings;
        
        // Calcular ganancias
        $totalEarnings = 0;
        $totalCurrent = 0;
        $earningsHistory = [];
        $fundDetails = [];
        
        foreach ($earnings as $earning) {
            $totalEarnings += ($earning->current_amount - $earning->initial_amount);
            $totalCurrent += $earning->current_amount;
            
            // Obtener historial de ganancias (usando los nuevos campos de la tabla)
            foreach ($earning->investmentEarningHistories as $history) {
                $earningsHistory[] = [
                    'id' => $history->id,
                    'fund_id' => $earning->fund->id,
                    'fund_name' => $earning->fund->name,
                    'previous_amount' => floatval($history->previous_amount),
                    'new_amount' => floatval($history->new_amount),
                    'change' => floatval($history->new_amount) - floatval($history->previous_amount),
                    'fluctuation_percent' => floatval($history->fluctuation_percent),
                    'reason' => $history->reason,
                    'recorded_at' => $history->recorded_at,
                ];
            }
            
            // Detalles del fondo
            $fundDetails[] = [
                'id' => $earning->fund->id,
                'name' => $earning->fund->name,
                'category' => $earning->fund->category,
                'initial_amount' => floatval($earning->initial_amount),
                'current_amount' => floatval($earning->current_amount),
                'earning' => floatval($earning->current_amount) - floatval($earning->initial_amount),
                'earning_percentage' => $earning->initial_amount > 0 
                    ? ((floatval($earning->current_amount) - floatval($earning->initial_amount)) / floatval($earning->initial_amount)) * 100 
                    : 0,
                'histories' => $earning->investmentEarningHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'previous_amount' => floatval($history->previous_amount),
                        'new_amount' => floatval($history->new_amount),
                        'change' => floatval($history->new_amount) - floatval($history->previous_amount),
                        'fluctuation_percent' => floatval($history->fluctuation_percent),
                        'reason' => $history->reason,
                        'recorded_at' => $history->recorded_at,
                    ];
                })->toArray(),
            ];
        }
        
        // Ordenar historial por fecha descendente
        usort($earningsHistory, function ($a, $b) {
            return strtotime($b['recorded_at']) - strtotime($a['recorded_at']);
        });

        // Obtener participantes de la suscripción
        $participants = $subscription->subscriptionParticipants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'role' => $participant->role,
                'share_percent' => floatval($participant->share_percent ?? 0),
                'final_investment_amount' => floatval($participant->final_investment_amount ?? 0),
                'is_primary_owner' => $participant->is_primary_owner,
                'participating' => $participant->participating,
                'started_at' => $participant->started_at,
                'ended_at' => $participant->ended_at,
                'is_active' => $participant->isActive(),
                'profile' => $participant->profile ? [
                    'id' => $participant->profile->id,
                    'first_name' => $participant->profile->first_name,
                    'last_name' => $participant->profile->last_name,
                    'email' => $participant->profile->email,
                ] : null,
                'investment_earning' => $participant->investmentEarning ? [
                    'id' => $participant->investmentEarning->id,
                    'fund_id' => $participant->investmentEarning->fund_id,
                    'fund_name' => $participant->investmentEarning->fund->name ?? null,
                    'initial_amount' => floatval($participant->investmentEarning->initial_amount),
                    'current_amount' => floatval($participant->investmentEarning->current_amount),
                ] : null,
            ];
        })->toArray();

        return Inertia::render('Client/Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'unique_code' => $subscription->unique_code,
                'payment' => [
                    'id' => $payment->id,
                    'amount' => floatval($payment->amount),
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'membership_charge' => floatval($payment->metadata['membership_applied'] ?? 0),
                    'total_paid' => floatval($payment->getTotalWithMembership()),
                    'created_at' => $payment->created_at,
                ],
                'plan' => [
                    'id' => $subscription->planType->id,
                    'name' => $subscription->planType->name,
                    'category' => $subscription->planType->category,
                    'amount_min' => floatval($subscription->planType->amount_min),
                    'amount_max' => floatval($subscription->planType->amount_max),
                    'membership' => floatval($subscription->planType->membership),
                ],
                'started_at' => $subscription->started_at,
                'expires_at' => $subscription->expires_at,
                'total_invested' => floatval($subscription->investmentEarnings->sum('initial_amount')),
                'total_current_value' => floatval($totalCurrent),
                'total_earnings' => floatval($totalEarnings),
                'earnings_percentage' => $subscription->investmentEarnings->sum('initial_amount') > 0
                    ? ($totalEarnings / floatval($subscription->investmentEarnings->sum('initial_amount'))) * 100
                    : 0,
                'earnings_history' => $earningsHistory,
                'funds' => $fundDetails,
                'participants' => $participants,
            ],
        ]);
    }
}
