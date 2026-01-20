<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    protected $table = 'referral_commissions';

    protected $fillable = [
        'advisor_profile_id',
        'subscription_participant_id',
        'commission_percentage',
        'commission_amount',
        'status',
        'calculated_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function advisorProfile()
    {
        return $this->belongsTo(Profile::class, 'advisor_profile_id');
    }

    public function subscriptionParticipant()
    {
        return $this->belongsTo(SubscriptionParticipant::class);
    }

    public function histories()
    {
        return $this->hasMany(ReferralCommissionHistory::class, 'commission_id');
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function projectClosure()
    {
        return $this->belongsTo(ProjectClosure::class);
    }

    // Métodos útiles
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsCalculated(float $amount): void
    {
        $this->update([
            'commission_amount' => $amount,
            'status' => 'calculated',
            'calculated_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function getNetCommission(): float
    {
        return floatval($this->commission_amount);
    }
}
