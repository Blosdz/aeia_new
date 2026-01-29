<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\ProfileBeneficiary;
use App\Models\RelationAccount;
use Illuminate\Support\Facades\DB;

class CoverageController extends Controller
{
    /**
     * Listar coberturas del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no tiene perfil, retornar colección vacía
        if (!$profile) {
            return Inertia::render('Client/Coverage/Index', [
                'coverages' => [],
            ]);
        }
        
        $coverages = Subscription::whereHas('payment', function ($query) use ($profile) {
            $query->where('payer_profile_id', $profile->id);
        })->whereHas('planType', function ($query) {
            $query->where('category', 'coverage');
        })->with('planType', 'payment', 'beneficiary')
            ->orderByDesc('created_at')
            ->paginate(15);

        // Agregar información de precio pagado a cada cobertura
        $coverages->getCollection()->transform(function ($coverage) {
            // Obtener metadata para información adicional
            $metadata = json_decode($coverage->payment->metadata, true);
            $coverage->price_per_beneficiary = $metadata['price_per_beneficiary'] ?? $coverage->planType->amount_min;

            // Contar beneficiarios desde las suscripciones del mismo payment
            $coverage->beneficiaries_count = Subscription::where('payment_id', $coverage->payment_id)->count();
            $coverage->total_paid = $coverage->payment->amount;

            // Agregar información del beneficiario si existe
            if ($coverage->beneficiary) {
                $coverage->beneficiary_name = $coverage->beneficiary->full_name;
            }

            return $coverage;
        });

        return Inertia::render('Client/Coverage/Index', [
            'coverages' => $coverages,
        ]);
    }
    
    public function selectCoverage(Request $request)
    {
        $coverages = Plans::where('category', 'coverage')
            ->orderBy('amount_min')
            ->get() ?? collect();
        
        return Inertia::render('Client/Coverage/Select', [
            'coverages' => $coverages,
        ]);
    }
    
    public function selectCoverageDetail(Request $request, $id)
    {
        $coverage = Plans::where('category', 'coverage')->findOrFail($id);
        $user = auth()->user();
        $profile = $user->profile;

        // Si no tiene perfil, retornar arrays vacíos
        $clientAccounts = [];
        $beneficiaries = [];
        $profileId = null;

        if ($profile) {
            $profileId = $profile->id;

            // Obtener cuentas activas
            $clientAccounts = $profile->clientAccounts()
                ->wherePivot('is_active', true)
                ->get() ?? collect();

            // Obtener beneficiarios del perfil
            $beneficiaries = $profile->beneficiaries()
                ->orderBy('created_at', 'desc')
                ->get() ?? collect();
        }

        // Agregar el precio por beneficiario (que es el amount_min del plan)
        $coverage->price_per_beneficiary = $coverage->amount_min;

        return Inertia::render('Client/Coverage/Detail', [
            'coverage' => $coverage,
            'clientAccounts' => $clientAccounts,
            'beneficiaries' => $beneficiaries,
            'profileId' => $profileId,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'beneficiary_ids' => 'required|array|min:1',
            'beneficiary_ids.*' => 'required|exists:profile_beneficiary,id',
            'client_account_id' => 'required|exists:client_accounts,id',
            'currency' => 'required|string|size:3',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return back()->withErrors(['error' => 'Perfil no configurado. Por favor completa tu perfil primero.']);
        }

        // Verificar que sea un plan de cobertura
        $plan = Plans::findOrFail($validated['plan_type_id']);
        if ($plan->category !== 'coverage') {
            abort(422, 'Plan no es de cobertura');
        }

        // Verificar que todos los beneficiarios pertenezcan al perfil
        $beneficiaries = ProfileBeneficiary::whereIn('id', $validated['beneficiary_ids'])
            ->where('profile_id', $profile->id)
            ->get();

        if ($beneficiaries->count() !== count($validated['beneficiary_ids'])) {
            return back()->withErrors(['error' => 'Algunos beneficiarios no son válidos.']);
        }

        // Calcular el monto total: precio por beneficiario * cantidad de beneficiarios
        $pricePerBeneficiary = $plan->amount_min;
        $totalAmount = $pricePerBeneficiary * $beneficiaries->count();

        // Verificar que existe la relación entre profile y client_account
        $relationAccount = RelationAccount::where('profile_id', $profile->id)
            ->where('client_account_id', $validated['client_account_id'])
            ->where('is_active', true)
            ->first();

        if (!$relationAccount) {
            return back()->withErrors(['error' => 'La cuenta de pago seleccionada no está asociada a tu perfil.']);
        }

        try {
            DB::beginTransaction();

            // 1. Crear el pago principal
            $payment = Payment::create([
                'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                'amount' => $totalAmount,
                'currency' => $validated['currency'],
                'status' => 'pending', // Inicia como pending, se actualizará cuando se confirme el pago
                'client_account_id' => $validated['client_account_id'],
                'payer_profile_id' => $profile->id,
                'metadata' => json_encode([
                    'type' => 'coverage',
                    'beneficiaries_count' => $beneficiaries->count(),
                    'price_per_beneficiary' => $pricePerBeneficiary,
                ]),
            ]);

            // 2. Crear una suscripción por cada beneficiario
            foreach ($beneficiaries as $beneficiary) {
                Subscription::create([
                    'payment_id' => $payment->id,
                    'profile_id' => $profile->id, // El perfil del usuario que está pagando
                    'beneficiary_id' => $beneficiary->id, // El beneficiario de la cobertura
                    'plan_type_id' => $plan->id,
                    'started_at' => now(),
                    'expires_at' => $plan->periodicity === 'annual' ? now()->addYear() : now()->addMonth(),
                ]);

                // 3. Actualizar el campo beneficiary_params del beneficiario con el payment_id
                $currentParams = $beneficiary->beneficiary_params ?? [];
                if (!is_array($currentParams)) {
                    $currentParams = [];
                }

                // Agregar el nuevo payment_id al array de payments
                if (!isset($currentParams['payment_ids'])) {
                    $currentParams['payment_ids'] = [];
                }
                $currentParams['payment_ids'][] = $payment->id;

                $beneficiary->update([
                    'beneficiary_params' => $currentParams
                ]);
            }

            DB::commit();

            return redirect()->route('clients.coverage')
                ->with('message', 'Cobertura contratada exitosamente para ' . $beneficiaries->count() . ' beneficiario(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al procesar la cobertura: ' . $e->getMessage()]);
        }
    }
    
    public function show(Request $request, Subscription $coverage)
    {
        $user = $request->user();

        // Verificar que sea cobertura y que pertenezca al usuario
        if (!$coverage->planType || $coverage->planType->category !== 'coverage') {
            abort(404);
        }

        $profile = $user->profile;
        if (!$profile || !$coverage->payment) {
            abort(403);
        }

        // Verificar que el usuario es quien pagó la cobertura
        if ($coverage->payment->payer_profile_id !== $profile->id) {
            abort(403);
        }

        // Agregar información de precio pagado y beneficiarios
        $coverage->load('planType', 'payment', 'beneficiary');
        $metadata = json_decode($coverage->payment->metadata, true);
        $coverage->price_per_beneficiary = $metadata['price_per_beneficiary'] ?? $coverage->planType->amount_min;
        $coverage->total_paid = $coverage->payment->amount;

        // Obtener todos los beneficiarios asociados a este payment
        $allSubscriptions = Subscription::where('payment_id', $coverage->payment_id)
            ->with('beneficiary')
            ->get();

        $coverage->beneficiaries_count = $allSubscriptions->count();
        $coverage->beneficiaries = $allSubscriptions->map(function ($sub) {
            return [
                'id' => $sub->beneficiary->id,
                'name' => $sub->beneficiary->full_name,
                'dni' => $sub->beneficiary->dni,
                'type_document' => $sub->beneficiary->type_document,
                'phone' => $sub->beneficiary->phone_extension . ' ' . $sub->beneficiary->phone,
                'verification_status' => $sub->beneficiary->verification_status,
            ];
        })->toArray();

        return Inertia::render('Client/Coverage/Show', [
            'coverage' => $coverage,
        ]);
    }
}
