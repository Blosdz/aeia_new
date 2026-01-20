<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientAccount;
use App\Models\RelationAccount;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CreditCardController extends Controller
{
    /**
     * Listar todas las tarjetas del usuario autenticado
     */
    public function index()
    {
        $userProfile = Auth::user()->profile;
        
        if (!$userProfile) {
            return response()->json(['error' => 'Perfil no encontrado'], 404);
        }

        $creditCards = $userProfile
            ->clientAccounts()
            ->with('relationAccounts')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($card) use ($userProfile) {
                $relationAccount = $card->relationAccounts()
                    ->where('profile_id', $userProfile->id)
                    ->first();

                return [
                    'id' => $card->id,
                    'holder_name' => $card->holder_name,
                    'bank_name' => $card->bank_name,
                    'card_type' => $card->card_type ?? 'unknown',
                    'card_type_name' => $card->getCardTypeName(),
                    'last_four' => $card->last4,
                    'exp_month' => str_pad($card->exp_month, 2, '0', STR_PAD_LEFT),
                    'exp_year' => $card->exp_year,
                    'is_expired' => $card->isExpired(),
                    'is_default' => $relationAccount ? $relationAccount->is_active : false,
                    'created_at' => $card->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $creditCards,
        ]);
    }

    /**
     * Guardar una nueva tarjeta de crédito
     */
    public function store(Request $request)
    {
        $userProfile = Auth::user()->profile;
        
        if (!$userProfile) {
            return response()->json(['error' => 'Perfil no encontrado'], 404);
        }

        // Limpiar el número de tarjeta antes de validar
        $cardNumber = preg_replace('/\s+/', '', $request->input('card_number', ''));
        $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);

        $validated = $request->validate([
            'holder_name' => 'required|string|max:100',
            'exp_month' => 'required|integer|between:1,12',
            'exp_year' => [
                'required',
                'integer',
                'min:' . date('Y'),
                function ($attribute, $value, $fail) use ($request) {
                    $month = $request->input('exp_month');
                    $year = date('Y');
                    $currentMonth = (int) date('m');

                    if ($value == $year && $month < $currentMonth) {
                        $fail('La tarjeta está vencida.');
                    }
                },
            ],
            'cvv' => 'required|string|regex:/^\d{3,4}$/',
            'bank_name' => 'required|string|max:100',
            'address_wallet' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        // Validar número de tarjeta después de limpiar
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'card_number' => ['El número de tarjeta debe tener entre 13 y 19 dígitos.']
                ]
            ], 422);
        }

        if (!$this->luhnCheck($cardNumber)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'card_number' => ['El número de tarjeta no es válido.']
                ]
            ], 422);
        }

        // Extraer últimos 4 dígitos y tipo de tarjeta
        $validated['last4'] = substr($cardNumber, -4);
        $validated['card_type'] = $this->detectCardType($cardNumber);

        // Generar token seguro para la tarjeta (nunca guardar el número completo)
        $validated['card_token'] = $this->generateCardToken($cardNumber, $validated['cvv']);

        // No guardamos el CVV por seguridad
        unset($validated['cvv']);

        // Crear tarjeta
        $clientAccount = ClientAccount::create($validated);

        // Crear relación con el perfil del usuario
        $isDefault = $validated['is_default'] ?? false;
        
        // Si es la primera tarjeta, marcarla como por defecto
        if (!$userProfile->clientAccounts()->exists()) {
            $isDefault = true;
        } else if ($isDefault) {
            // Si es marcada como por defecto, desmarcar otras
            RelationAccount::where('profile_id', $userProfile->id)
                ->update(['is_active' => false]);
        }

        RelationAccount::create([
            'profile_id' => $userProfile->id,
            'client_account_id' => $clientAccount->id,
            'is_active' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Tarjeta agregada exitosamente',
            'data' => [
                'id' => $clientAccount->id,
                'holder_name' => $clientAccount->holder_name,
                'bank_name' => $clientAccount->bank_name,
                'card_type' => $this->detectCardType($clientAccount->last4 ?? ''),
                'last_four' => $clientAccount->last4,
                'exp_month' => $clientAccount->exp_month,
                'exp_year' => $clientAccount->exp_year,
                'is_default' => $isDefault,
            ],
        ], 201);
    }

    /**
     * Marcar tarjeta como por defecto
     */
    public function setDefault(ClientAccount $clientAccount)
    {
        $userProfile = Auth::user()->profile;
        
        if (!$userProfile) {
            return response()->json(['error' => 'Perfil no encontrado'], 403);
        }

        // Verificar que la tarjeta está asociada al usuario
        $relation = RelationAccount::where('profile_id', $userProfile->id)
            ->where('client_account_id', $clientAccount->id)
            ->first();

        if (!$relation) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Desmarcar todas las demás tarjetas del usuario
        RelationAccount::where('profile_id', $userProfile->id)
            ->update(['is_active' => false]);
        
        // Marcar esta como activa
        $relation->update(['is_active' => true]);

        return response()->json([
            'message' => 'Tarjeta establecida como predeterminada',
            'data' => [
                'id' => $clientAccount->id,
                'is_default' => true,
            ],
        ]);
    }

    /**
     * Eliminar una tarjeta de crédito
     */
    public function destroy(ClientAccount $clientAccount)
    {
        $userProfile = Auth::user()->profile;
        
        if (!$userProfile) {
            return response()->json(['error' => 'Perfil no encontrado'], 403);
        }

        // Verificar que la tarjeta está asociada al usuario
        $relation = RelationAccount::where('profile_id', $userProfile->id)
            ->where('client_account_id', $clientAccount->id)
            ->first();

        if (!$relation) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Eliminar la relación
        $relation->delete();

        // Si no hay más relaciones, eliminar la tarjeta
        if ($clientAccount->relationAccounts()->count() === 0) {
            $clientAccount->delete();
        }

        return response()->json([
            'message' => 'Tarjeta eliminada exitosamente',
        ]);
    }

    /**
     * Algoritmo de Luhn para validar número de tarjeta
     */
    private function luhnCheck(string $cardNumber): bool
    {
        $sum = 0;
        $isEven = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int) $cardNumber[$i];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return $sum % 10 === 0;
    }

    /**
     * Detectar tipo de tarjeta por el número completo
     */
    private function detectCardType(string $cardNumber): string
    {
        if (empty($cardNumber)) {
            return 'unknown';
        }

        // Detectar por los primeros dígitos
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^(5[1-5]|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/',
            'amex' => '/^3[47]/',
            'discover' => '/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5])|64[4-9]|65)/',
            'diners' => '/^(30[0-5]|36|38)/',
            'jcb' => '/^35/',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Generar token seguro para la tarjeta
     * En producción, esto debería usar un servicio de tokenización real (Stripe, PayPal, etc)
     */
    private function generateCardToken(string $cardNumber, string $cvv): string
    {
        // Generar un token único y seguro
        // IMPORTANTE: En producción usar un servicio de tokenización real
        $data = $cardNumber . $cvv . now()->timestamp . Str::random(32);
        return 'tok_' . hash('sha256', $data);
    }

    /**
     * Guardar el tipo de tarjeta basado en el número completo
     */
    private function saveCardType(string $cardNumber): string
    {
        return $this->detectCardType($cardNumber);
    }
}
