<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    /**
     * Dashboard principal de Support
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        // Verificar que tenga rol support
        if (!$user->hasRole('support')) {
            abort(403, 'No tienes permisos de soporte');
        }
        
        // Estadísticas para el dashboard
        $stats = [
            'pending_validations' => Profile::where('verified', 0)->count(),
            'verified_users' => Profile::where('verified', 1)->count(),
            'rejected_users' => Profile::where('verified', 2)->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'completed_payments' => Payment::where('status', 'completed')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'total_users_today' => User::whereDate('created_at', today())->count(),
            'total_payments_today' => Payment::whereDate('created_at', today())->count(),
        ];
        
        // Validaciones pendientes más urgentes
        $pending_profiles = Profile::with(['user'])
            ->where('verified', 0)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'user_name' => $profile->user->name,
                    'user_email' => $profile->user->email,
                    'type' => $profile->type,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'dni' => $profile->dni,
                    'created_at' => $profile->created_at,
                    'has_documents' => !empty($profile->photos_dni),
                ];
            });
        
        // Pagos pendientes más urgentes
        $pending_payments = Payment::with(['payerProfile.user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payer_name' => $payment->payerProfile->first_name . ' ' . $payment->payerProfile->last_name,
                    'payer_email' => $payment->payerProfile->user->email,
                    'created_at' => $payment->created_at,
                ];
            });
        
        return Inertia::render('support/Dashboard', [
            'stats' => $stats,
            'pending_profiles' => $pending_profiles,
            'pending_payments' => $pending_payments,
        ]);
    }
}