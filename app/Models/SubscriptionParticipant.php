<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionParticipant extends Model
{
    protected $table = 'subscription_participants';

    protected $fillable = [
        'subscription_id',
        'profile_id',
        'investment_earnings_id',
        'role',
        'share_percent',
        'final_investment_amount',
        'is_primary_owner',
        'participating',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'is_primary_owner' => 'boolean',
        'participating' => 'boolean',
        'share_percent' => 'decimal:2',
        'final_investment_amount' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function investmentEarning()
    {
        return $this->belongsTo(InvestmentEarning::class, 'investment_earnings_id');
    }

    public function referralCommissions()
    {
        return $this->hasMany(ReferralCommission::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isBeneficiary(): bool
    {
        return $this->role === 'beneficiary';
    }

    public function isAdvisor(): bool
    {
        return $this->role === 'advisor';
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at) || $this->ended_at->isFuture();
    }
}
