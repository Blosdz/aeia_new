<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnsureProfileVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return Redirect::route('login');
        }
        $profile = $user->profile;
        if (!$profile || empty($profile->verified) || $profile->verified == 0) {
            // Redirigir al perfil para que el usuario pueda completar/verificar su información
            return redirect()->route('clients.payments')
                ->with('warning', 'Tu perfil aun no esta verificado.');
        }

        return $next($request);
    }
}
