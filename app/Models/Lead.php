<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'name',
        'user_id',
        'lastname',
        'email',
        'url_payment',
        'birthdate',
        'email_verification',
        'unique_code',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuario que creó el lead
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cotizaciones del lead
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Obtener el nombre completo del lead
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->lastname);
    }

    /**
     * Verificar si el email está verificado
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->email_verification);
    }
}
