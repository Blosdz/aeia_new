<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fondo extends Model
{
    protected $table = 'funds';

    protected $fillable = [
        'category',
        'name',
        'initial_amount',
        'current_amount',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function fundHistories()
    {
        return $this->hasMany(FondoHistorial::class, 'fund_id');
    }

    public function investmentEarnings()
    {
        return $this->hasMany(InvestmentEarning::class, 'fund_id');
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class, 'fund_id');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'fund_id');
    }
}

