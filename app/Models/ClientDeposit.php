<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDeposit extends Model
{
    protected $table = 'client_deposits';

    protected $fillable = [
        'profile_id',
        'reward_id',
        'earning_id',
        'amount',
        'currency',
        'status',
        'transaction_ref',
        'deposit_date',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'deposit_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function earning()
    {
        return $this->belongsTo(InvestmentEarning::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSettled(): bool
    {
        return $this->status === 'settled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
