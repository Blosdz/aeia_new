<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Mostrar perfil del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no existe el perfil, devolver valores por defecto
        if (!$profile) {
            return Inertia::render('Client/Profile', [
                'profile' => [
                    'id' => null,
                    'user_id' => $user->id,
                    'first_name' => null,
                    'last_name' => null,
                    'phone' => null,
                    'city' => null,
                    'country' => null,
                    'email' => $user->email,
                    'type' => 'user',
                    'dni' => null,
                    'type_document' => null,
                    'phone_extension' => null,
                    'nacionality' => null,
                    'job' => null,
                    'country_dni' => null,
                    'state' => null,
                    'birthdate' => null,
                    'sex' => null,
                    'photos_dni' => null,
                    'photo_id_type' => null,
                    'signature_digital' => null,
                    'verified' => false,
                ],
            ]);
        }
        
        // Agregar el email del usuario al perfil
        $profileData = $profile->toArray();
        $profileData['email'] = $user->email;

        return Inertia::render('Client/Profile', [
            'profile' => $profileData,
        ]);
    }
    
    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no existe el perfil, crear uno nuevo
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'type' => 'user',
                'verified' => 0,
            ]);
        }
        
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'phone_extension' => 'nullable|string|max:10',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'type_document' => 'nullable|string',
            'dni' => 'nullable|string|max:50',
            'country_dni' => 'nullable|string|max:50',
            'nacionality' => 'nullable|string',
            'job' => 'nullable|string',
            'birthdate' => 'nullable|date',
            'sex' => 'nullable|string',
            'photos_dni' => 'nullable|array|max:2',
            'photos_dni.*' => 'nullable|string',
        ]);
        
        // Procesar archivos photos_dni si existen
        $photosUrls = [];
        if (!empty($validated['photos_dni'])) {
            foreach ($validated['photos_dni'] as $index => $photoData) {
                if ($photoData && is_string($photoData)) {
                    // Verificar si es base64 (comienza con data:)
                    if (strpos($photoData, 'data:') === 0) {
                        try {
                            // Extraer datos base64
                            $parts = explode(',', $photoData);
                            if (count($parts) === 2) {
                                $base64Data = base64_decode($parts[1]);
                                
                                // Determinar extensión del archivo
                                preg_match('/data:image\/(\w+);/', $photoData, $matches);
                                $extension = $matches[1] ?? 'jpg';
                                
                                // Generar nombre único para el archivo
                                $filename = 'dni_' . ($index === 0 ? 'frente' : 'reverso') . '_' . Str::random(16) . '.' . $extension;
                                $path = 'profiles/' . $profile->id . '/' . $filename;
                                
                                // Guardar archivo
                                Storage::disk('public')->put($path, $base64Data);
                                
                                // Guardar URL
                                $photosUrls[$index] = Storage::disk('public')->url($path);
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error procesando DNI image: ' . $e->getMessage());
                        }
                    } else {
                        // Si ya es una URL, mantenerla
                        $photosUrls[$index] = $photoData;
                    }
                }
            }
        }
        
        // Remover photos_dni del array validado antes de actualizar
        unset($validated['photos_dni']);
        
        // Actualizar photos_dni solo si hay nuevas imágenes
        if (!empty($photosUrls)) {
            $validated['photos_dni'] = $photosUrls;
        }
        
        $profile->update($validated);
        
        return back()->with('message', 'Perfil actualizado correctamente.');
    }
}
