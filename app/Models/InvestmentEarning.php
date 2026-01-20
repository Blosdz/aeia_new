<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentEarning extends Model
{
    protected $table = 'investment_earnings';

    protected $fillable = [
        'subscription_id',
        'fund_id',
        'initial_amount',
        'current_amount',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class, 'fund_id');
    }

    public function investmentEarningHistories()
    {
        return $this->hasMany(InvestmentEarningHistory::class, 'earning_id');
    }

    public function clientDeposits()
    {
        return $this->hasMany(ClientDeposit::class, 'earning_id');
    }

    // Métodos útiles
    public function getGainLoss(): float
    {
        return floatval($this->current_amount) - floatval($this->initial_amount);
    }

    public function getReturnPercent(): float
    {
        if ($this->initial_amount == 0) return 0;
        return ((floatval($this->current_amount) - floatval($this->initial_amount)) / floatval($this->initial_amount)) * 100;
    }

    public function updateCurrentAmount(float $newAmount): void
    {
        $this->update(['current_amount' => $newAmount]);
    }
}
