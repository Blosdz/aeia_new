<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProfileBeneficiary;

class BeneficiaryController extends Controller
{
    /**
     * Store a newly created beneficiary
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'sex' => 'required|string|in:M,F,O',
            'type' => 'required|string|in:user,boss,staff',
            'type_document' => 'required|string|max:50',
            'dni' => 'required|string|max:50',
            'phone_extension' => 'nullable|string|max:5',
            'phone' => 'nullable|string|max:20',
            'nacionality' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        // Verificar que el profile_id corresponde al usuario autenticado
        if (!$profile || $profile->id != $validated['profile_id']) {
            abort(403, 'No autorizado para crear beneficiarios en este perfil.');
        }

        $beneficiary = ProfileBeneficiary::create([
            'profile_id' => $validated['profile_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'sex' => $validated['sex'],
            'type' => $validated['type'],
            'type_document' => $validated['type_document'],
            'dni' => $validated['dni'],
            'phone_extension' => $validated['phone_extension'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'nacionality' => $validated['nacionality'] ?? null,
            'city' => $validated['city'] ?? null,
            'verification_status' => 'pending',
        ]);

        return back()->with('message', 'Beneficiario agregado exitosamente.');
    }

    /**
     * Remove the specified beneficiary
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            abort(403, 'No autorizado.');
        }

        $beneficiary = ProfileBeneficiary::where('id', $id)
            ->where('profile_id', $profile->id)
            ->firstOrFail();

        $beneficiary->delete();

        return back()->with('message', 'Beneficiario eliminado exitosamente.');
    }
}
