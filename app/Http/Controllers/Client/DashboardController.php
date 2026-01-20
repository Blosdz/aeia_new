<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Subscription;
use App\Models\InvestmentEarning;

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
            ]);
        }
        
        // Obtener suscripciones del cliente
        $subscriptions = Subscription::whereHas('paymentAllocations', function ($query) use ($profile) {
            $query->whereHas('payment', function ($q) use ($profile) {
                $q->where('payer_profile_id', $profile->id);
            });
        })->with(['planType', 'investmentEarnings.fund'])->get() ?? collect();
        
        // Calcular totales con valores por defecto
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
        
        return Inertia::render('Client/Dashboard', [
            'profile' => $profile,
            'subscriptions' => $subscriptions,
            'totalInvested' => floatval($totalInvested) ?? 0,
            'totalCurrentValue' => floatval($totalCurrentValue) ?? 0,
            'totalGains' => floatval($totalRevenue) ?? 0,
            'gainPercent' => round($gainPercent, 2) ?? 0,
            'activeSubscriptions' => $activeSubscriptions ?? 0,
            'totalRevenue' => floatval($totalRevenue) ?? 0,
            'closureDays' => 42, // Valor por defecto
        ]);
    }
}
