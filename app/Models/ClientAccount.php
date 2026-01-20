<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientAccount extends Model
{
    protected $table = 'client_accounts';

    protected $fillable = [
        'bank_name',
        'card_type',
        'address_wallet',
        'last4',
        'exp_month',
        'exp_year',
        'card_token',
        'holder_name',
    ];

    protected $casts = [
        'exp_month' => 'integer',
        'exp_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function relationAccounts()
    {
        return $this->hasMany(RelationAccount::class);
    }

    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'relation_accounts', 'client_account_id', 'profile_id')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentLeads()
    {
        return $this->hasMany(PaymentLead::class);
    }

    /**
     * Verificar si la tarjeta está vencida
     */
    public function isExpired(): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        if ($this->exp_year < $currentYear) {
            return true;
        }

        if ($this->exp_year == $currentYear && $this->exp_month < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Obtener el nombre formateado del tipo de tarjeta
     */
    public function getCardTypeName(): string
    {
        $types = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'discover' => 'Discover',
            'diners' => 'Diners Club',
            'jcb' => 'JCB',
        ];

        return $types[$this->card_type] ?? 'Desconocida';
    }
}
