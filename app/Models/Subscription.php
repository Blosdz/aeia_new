<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'payment_id',
        'plan_type_id',
        'unique_code',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->unique_code) {
                // Generar código único: SUB-XXXXXX-YYYY
                $model->unique_code = 'SUB-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
            }
        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function planType()
    {
        return $this->belongsTo(Plans::class, 'plan_type_id');
    }

    public function investmentEarnings()
    {
        return $this->hasMany(InvestmentEarning::class);
    }

    public function subscriptionParticipants()
    {
        return $this->hasMany(SubscriptionParticipant::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    // Método para obtener el propietario principal
    public function owner()
    {
        return $this->subscriptionParticipants()
            ->where('is_primary_owner', true)
            ->with('profile')
            ->first();
    }

    // Métodos de estado
    public function isActive(): bool
    {
        $now = now();
        return $this->started_at <= $now && (!$this->expires_at || $this->expires_at >= $now);
    }

    // Métodos de cálculo
    public function getTotalInvested(): float
    {
        return $this->investmentEarnings()->sum('initial_amount') ?? 0;
    }

    public function getTotalCurrentAmount(): float
    {
        return $this->investmentEarnings()->sum('current_amount') ?? 0;
    }

    public function getGainLoss(): float
    {
        $invested = $this->getTotalInvested();
        $current = $this->getTotalCurrentAmount();
        return $current - $invested;
    }

    public function getReturnPercent(): float
    {
        $invested = $this->getTotalInvested();
        if ($invested == 0) return 0;
        return (($this->getTotalCurrentAmount() - $invested) / $invested) * 100;
    }
}
