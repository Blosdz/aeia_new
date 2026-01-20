<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'client_account_id',
        'payer_profile_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function clientAccount()
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function payerProfile()
    {
        return $this->belongsTo(Profile::class, 'payer_profile_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Alias para acceso más fácil en templates
     */
    public function allocations()
    {
        return $this->paymentAllocations();
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Obtener el monto total incluyendo membresía si aplica
     * 
     * @return float
     */
    public function getTotalWithMembership()
    {
        $total = $this->amount;
        
        if ($this->metadata && isset($this->metadata['membership_applied'])) {
            $total += $this->metadata['membership_applied'];
        }
        
        return $total;
    }

    /**
     * Verificar si este pago incluye cobro de membresía
     *
     * @return bool
     */
    public function hasMembershipCharge()
    {
        return $this->metadata && isset($this->metadata['membership_applied']) && $this->metadata['membership_applied'] > 0;
    }

    /**
     * Verificar si un perfil ya pagó membresía para un plan específico
     *
     * @param int $profileId
     * @param int $planTypeId
     * @return bool
     */
    public static function hasPaidMembershipForPlan(int $profileId, int $planTypeId): bool
    {
        return self::where('payer_profile_id', $profileId)
            ->where('status', 'completed')
            ->whereHas('subscriptions', function ($query) use ($planTypeId) {
                $query->where('plan_type_id', $planTypeId);
            })
            ->where(function ($query) {
                $query->whereNotNull('metadata')
                    ->whereRaw("JSON_EXTRACT(metadata, '$.membership_applied') > 0");
            })
            ->exists();
    }

    /**
     * Obtener el ID del plan asociado a este pago
     *
     * @return int|null
     */
    public function getPlanTypeId(): ?int
    {
        $subscription = $this->subscriptions()->first();
        return $subscription ? $subscription->plan_type_id : null;
    }

    /**
     * Verificar estados del pago
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function validate(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function reject(): void
    {
        $this->update(['status' => 'failed']);
    }
}

