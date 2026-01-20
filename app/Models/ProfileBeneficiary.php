<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProfileBeneficiary extends Model
{
    protected $table = 'profile_beneficiary';

    protected $fillable = [
        'profile_id',
        'sex',
        'first_name',
        'type',
        'last_name',
        'type_document',
        'dni',
        'phone_extension',
        'phone',
        'nacionality',
        'city',
        'photos_dni',
        'photo_beneficiary',
        'beneficiary_params',
        'verification_status',
        'verification_notes',
        'verified_at',
    ];

    protected $casts = [
        'photos_dni' => 'array',
        'beneficiary_params' => 'array',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Perfil principal al que pertenece el beneficiario
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Obtener el nombre completo del beneficiario
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Verificar si el beneficiario está verificado
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Verificar si el beneficiario está pendiente
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Verificar si el beneficiario fue rechazado
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Obtener todos los payments asociados a este beneficiario
     */
    public function payments()
    {
        // Obtener los payment_ids desde beneficiary_params
        $paymentIds = $this->beneficiary_params['payment_ids'] ?? [];

        if (empty($paymentIds)) {
            return collect([]);
        }

        return Payment::whereIn('id', $paymentIds)->get();
    }

    /**
     * Verificar si el beneficiario tiene al menos un payment
     */
    public function hasPayments(): bool
    {
        $paymentIds = $this->beneficiary_params['payment_ids'] ?? [];
        return !empty($paymentIds);
    }

    /**
     * Obtener el último payment asociado
     */
    public function getLastPayment()
    {
        $paymentIds = $this->beneficiary_params['payment_ids'] ?? [];

        if (empty($paymentIds)) {
            return null;
        }

        return Payment::whereIn('id', $paymentIds)
            ->orderByDesc('created_at')
            ->first();
    }
}

