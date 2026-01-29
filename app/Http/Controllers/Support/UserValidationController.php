<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserValidationController extends Controller
{
    /**
     * Lista de usuarios pendientes de validación
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403, 'No tienes permisos de soporte');
        }
        
        $search = $request->query('search', '');
        $statusFilter = $request->query('status', '');
        $typeFilter = $request->query('type', '');
        
        $query = Profile::with(['user']);
        
        // Filtros
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($statusFilter !== '') {
            $query->where('verified', $statusFilter);
        }
        
        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }
        
        $profiles = $query->orderByRaw('verified = 0 DESC, created_at DESC')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($profile) {
                return [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'user_name' => $profile->user->name,
                    'user_email' => $profile->user->email,
                    'user_created_at' => $profile->user->created_at,
                    'type' => $profile->type,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'dni' => $profile->dni,
                    'type_document' => $profile->type_document,
                    'phone' => $profile->phone,
                    'phone_extension' => $profile->phone_extension,
                    'nacionality' => $profile->nacionality,
                    'city' => $profile->city,
                    'country' => $profile->country,
                    'country_dni' => $profile->country_dni,
                    'birthdate' => $profile->birthdate,
                    'sex' => $profile->sex,
                    'verified' => $profile->verified,
                    'verified_status' => $this->getVerificationStatus($profile->verified),
                    'has_documents' => !empty($profile->photos_dni),
                    'has_signature' => !empty($profile->signature_digital),
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                ];
            });
        
        return Inertia::render('support/UserValidation/Index', [
            'profiles' => $profiles,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'type' => $typeFilter,
            ],
            'statusOptions' => [
                '0' => 'Pendiente',
                '1' => 'Verificado',
                '2' => 'Rechazado',
            ],
            'typeOptions' => ['user', 'boss', 'staff'],
        ]);
    }
    
    /**
     * Ver detalles del perfil para validación
     */
    public function show(Profile $profile)
    {
        $user = request()->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $profile->load(['user', 'beneficiaries']);
        
        // Obtener información adicional del usuario
        $userData = [
            'id' => $profile->user->id,
            'name' => $profile->user->name,
            'email' => $profile->user->email,
            'first_name' => $profile->user->first_name,
            'last_name' => $profile->user->last_name,
            'unique_code' => $profile->user->unique_code,
            'referral_code' => $profile->user->referral_code,
            'referred_by_user_id' => $profile->user->referred_by_user_id,
            'last_login' => $profile->user->last_login,
            'created_at' => $profile->user->created_at,
            'is_active' => $profile->user->is_active,
        ];
        
        $profileData = [
            'id' => $profile->id,
            'type' => $profile->type,
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'type_document' => $profile->type_document,
            'dni' => $profile->dni,
            'phone_extension' => $profile->phone_extension,
            'phone' => $profile->phone,
            'nacionality' => $profile->nacionality,
            'city' => $profile->city,
            'country' => $profile->country,
            'country_dni' => $profile->country_dni,
            'state' => $profile->state,
            'job' => $profile->job,
            'birthdate' => $profile->birthdate,
            'sex' => $profile->sex,
            'photos_dni' => $profile->photos_dni,
            'photo_id_type' => $profile->photo_id_type,
            'signature_digital' => $profile->signature_digital,
            'verified' => $profile->verified,
            'verified_status' => $this->getVerificationStatus($profile->verified),
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];
        
        return Inertia::render('support/UserValidation/Show', [
            'user' => $userData,
            'profile' => $profileData,
            'beneficiaries' => $profile->beneficiaries->map(function ($beneficiary) {
                return [
                    'id' => $beneficiary->id,
                    'first_name' => $beneficiary->first_name,
                    'last_name' => $beneficiary->last_name,
                    'dni' => $beneficiary->dni,
                    'type_document' => $beneficiary->type_document,
                    'verification_status' => $beneficiary->verification_status,
                    'verification_notes' => $beneficiary->verification_notes,
                    'verified_at' => $beneficiary->verified_at,
                ];
            }),
        ]);
    }
    
    /**
     * Validar/Aprobar perfil de usuario
     */
    public function approve(Request $request, Profile $profile)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            $profile->update([
                'verified' => 1,
                'updated_at' => now(),
            ]);
            
            // Log de la acción
            Log::info('Profile approved by support', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'support_user_id' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Usuario verificado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving profile', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al verificar el usuario.');
        }
    }
    
    /**
     * Rechazar perfil de usuario
     */
    public function reject(Request $request, Profile $profile)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            $profile->update([
                'verified' => 2,
                'updated_at' => now(),
            ]);
            
            // Log de la acción
            Log::info('Profile rejected by support', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'support_user_id' => $user->id,
                'reason' => $validated['reason'],
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Usuario rechazado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting profile', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al rechazar el usuario.');
        }
    }
    
    /**
     * Resetear verificación de perfil (volver a pendiente)
     */
    public function reset(Request $request, Profile $profile)
    {
        $user = $request->user();
        
        if (!$user->hasRole('support')) {
            abort(403);
        }
        
        DB::beginTransaction();
        try {
            $profile->update([
                'verified' => 0,
                'updated_at' => now(),
            ]);
            
            Log::info('Profile verification reset by support', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'support_user_id' => $user->id,
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Verificación restablecida a pendiente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting profile verification', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Error al restablecer la verificación.');
        }
    }
    
    private function getVerificationStatus($verified)
    {
        return match ($verified) {
            0 => 'Pendiente',
            1 => 'Verificado',
            2 => 'Rechazado',
            default => 'Desconocido',
        };
    }
}