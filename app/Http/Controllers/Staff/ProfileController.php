<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\ProfileStaff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Mostrar formulario de edición de perfil
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        // Verificar que sea staff
        if ($user->type !== 'staff' && !$user->hasRole('staff')) {
            abort(403, 'No tienes acceso a este recurso');
        }

        return Inertia::render('Staff/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $profile ? [
                'id' => $profile->id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'type' => $profile->type,
                'phone' => $profile->phone,
                'city' => $profile->city,
                'country' => $profile->country,
                'dni' => $profile->dni,
                'type_document' => $profile->type_document,
                'nacionality' => $profile->nacionality,
                'job' => $profile->job,
                'birthdate' => $profile->birthdate,
                'sex' => $profile->sex,
                'bio' => $profile->bio,
                'verified' => $profile->verified,
            ] : null,
        ]);
    }

    /**
     * Guardar cambios del perfil
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        // Verificar que sea staff
        if ($user->type !== 'staff' && !$user->hasRole('staff')) {
            abort(403, 'No tienes acceso a este recurso');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'phone_extension' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:50',
            'type_document' => 'nullable|string|max:50',
            'nacionality' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'sex' => 'nullable|in:M,F,O',
            'bio' => 'nullable|string|max:1000',
        ]);

        // Crear o actualizar perfil
        $profile = $user->profile;
        $isNewProfile = !$profile;

        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'type' => 'staff',
                ...$validated,
            ]);
        } else {
            $profile->update($validated);
        }

        // Si el perfil fue creado, agregar al admin como boss automáticamente
        if ($isNewProfile) {
            // Obtener el perfil boss del admin
            $adminProfile = Profile::where('user_id', 1)->first();

            if ($adminProfile && $adminProfile->bossProfile) {
                ProfileStaff::create([
                    'profile_id' => $profile->id,
                    'boss_id' => $adminProfile->bossProfile->id,
                ]);
            }
        }

        return back()->with('success', 'Perfil actualizado correctamente');
    }
}
