<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Profile;
use App\Models\Document;
use App\Models\Payment;

class ClientController extends Controller
{
    /**
     * Listar todos los clientes
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
        
        $search = $request->query('search', '');
        $statusFilter = $request->query('status', '');
        
        $query = User::with(['roles', 'profile']);
        
        // Filtrar solo usuarios con rol client o client_business
        $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['client', 'client_business']);
        });
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('unique_code', 'like', "%{$search}%");
        }
        
        if ($statusFilter) {
            $query->whereHas('profile', function ($q) use ($statusFilter) {
                $q->where('verification_status', $statusFilter);
            });
        }
        
        $clients = $query->orderByDesc('created_at')
            ->paginate(20)
            ->through(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'unique_code' => $client->unique_code,
                    'is_active' => $client->is_active,
                    'roles' => $client->roles->pluck('name')->toArray(),
                    'profile' => $client->profile ? [
                        'dni' => $client->profile->dni,
                        'phone' => $client->profile->phone,
                        'phone_extension' => $client->profile->phone_extension,
                        'verification_status' => $client->profile->verification_status,
                        'verified_at' => $client->profile->verified_at,
                    ] : null,
                    'created_at' => $client->created_at,
                    'last_login' => $client->last_login,
                ];
            });
        
        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Ver detalle del cliente
     * 
     * @param Request $request
     * @param User $user
     * @return \Inertia\Response
     */
    public function show(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        // Verificar que sea cliente
        if (!$user->hasRole('client') && !$user->hasRole('client_business')) {
            abort(404);
        }
        
        $user->load(['profile', 'roles']);
        
        $profile = $user->profile;
        
        // Obtener documentos desde el profile (relación polimórfica)
        $documents = [];
        if ($profile) {
            $documents = $profile->documents()
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_type' => $doc->file_type,
                        'file_path' => $doc->file_path,
                        'file_size' => $doc->file_size,
                        'created_at' => $doc->created_at,
                    ];
                });
        }
        
        // Obtener tarjetas (sin CVV)
        $creditCards = [];
        if ($profile) {
            $creditCards = $profile->clientAccounts()
                ->wherePivot('is_active', true)
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'bank_name' => $account->bank_name,
                        'holder_name' => $account->holder_name,
                        'last_four' => $account->last_four,
                        'is_default' => $account->pivot->is_active,
                    ];
                });
        }
        
        // Obtener pagos
        $payments = Payment::where('payer_profile_id', $profile?->id)
            ->with('subscriptions.planType')
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
                    'plan' => $payment->subscriptions?->first()?->planType?->name ?? 'N/A',
                    'membership_charge' => $payment->metadata['membership_applied'] ?? 0,
                    'total_paid' => $payment->getTotalWithMembership(),
                    'created_at' => $payment->created_at,
                ];
            });
        
        return Inertia::render('Admin/Clients/Show', [
            'client' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'unique_code' => $user->unique_code,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->toArray(),
                'last_login' => $user->last_login,
                'created_at' => $user->created_at,
            ],
            'profile' => $profile ? [
                'id' => $profile->id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'type' => $profile->type,
                'type_document' => $profile->type_document,
                'dni' => $profile->dni,
                'phone' => $profile->phone,
                'phone_extension' => $profile->phone_extension,
                'nacionality' => $profile->nacionality,
                'city' => $profile->city,
                'country' => $profile->country,
                'job' => $profile->job,
                'country_dni' => $profile->country_dni,
                'state' => $profile->state,
                'birthdate' => $profile->birthdate,
                'sex' => $profile->sex,
                'photos_dni' => $profile->photos_dni,
                'photo_id_type' => $profile->photo_id_type,
                'signature_digital' => $profile->signature_digital,
                'verified' => $profile->verified,
                'bio' => $profile->bio,
                'verification_status' => $profile->verification_status,
                'verified_at' => $profile->verified_at,
                'verified_by' => $profile->verified_by,
                'verification_notes' => $profile->verification_notes,
            ] : null,
            'documents' => $documents,
            'credit_cards' => $creditCards,
            'payments' => $payments,
        ]);
    }

    /**
     * Verificar cliente
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        if (!$user->hasRole('client') && !$user->hasRole('client_business')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'verification_notes' => 'nullable|string|max:500',
        ]);
        
        $profile = $user->profile;
        
        if (!$profile) {
            return back()->withErrors(['error' => 'El cliente no tiene perfil configurado']);
        }
        
        $profile->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $authUser->id,
            'verified' => 1,
            'verification_notes' => $validated['verification_notes'],
        ]);
        
        return back()->with('message', 'Cliente verificado exitosamente');
    }

    /**
     * Rechazar verificación
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        if (!$user->hasRole('client') && !$user->hasRole('client_business')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'verification_notes' => 'required|string|max:500',
        ]);
        
        $profile = $user->profile;
        
        if (!$profile) {
            return back()->withErrors(['error' => 'El cliente no tiene perfil configurado']);
        }
        
        $profile->update([
            'verification_status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $authUser->id,
            'verification_notes' => $validated['verification_notes'],
        ]);
        
        return back()->with('message', 'Cliente rechazado');
    }

    /**
     * Resetear verificación
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetVerification(Request $request, User $user)
    {
        $authUser = $request->user();
        
        if (!$authUser->hasRole('admin')) {
            abort(403);
        }
        
        $profile = $user->profile;
        
        if (!$profile) {
            return back()->withErrors(['error' => 'El cliente no tiene perfil configurado']);
        }
        
        $profile->update([
            'verification_status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => null,
        ]);
        
        return back()->with('message', 'Estado de verificación reseteado');
    }
}
