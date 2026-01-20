<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request, $referral_code = null): Response
    {
        $referred_by_user_id = null;
        $validReferralCode = null;
        
        // Si hay un referral_code, buscamos al usuario
        if ($referral_code) {
            // Primero intentamos buscar por referral_code
            $referrer = User::where('referral_code', $referral_code)->first();
            
            // Si no encuentra, intentamos por ID (por compatibilidad)
            if (!$referrer && is_numeric($referral_code)) {
                $referrer = User::find((int)$referral_code);
            }
            
            if ($referrer) {
                $referred_by_user_id = $referrer->id;
                $validReferralCode = $referrer->referral_code;
            }
        }

        return Inertia::render('auth/Register', [
            'referred_by_user_id' => $referred_by_user_id,
            'referral_code' => $validReferralCode,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    /*default store client*/
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referred_by_user_id' => 'nullable|integer|exists:users,id',
        ]);

        \Log::info('Register validation passed', $validated);

        // Crear usuario sin referral_code primero
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'unique_code'=> Str::random(10),
            'referral_code' => null, // Será actualizado después
            'referred_by_user_id' => $validated['referred_by_user_id'] ?? null,
            'referral_accepted_at' => isset($validated['referred_by_user_id']) && $validated['referred_by_user_id'] ? now() : null,
        ]);

        // Generar referral_code único basado en el ID del usuario
        $referral_code = 'REF_' . strtoupper(Str::random(6)) . '_' . $user->id;
        $user->update(['referral_code' => $referral_code]);

        \Log::info('User created with referral_code', $user->toArray());

        UserRole::create([
            'user_id' => $user->id,
            //rol basico de user admin y staff se crean de mi lado de backend  
            'role_id' => 2,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
