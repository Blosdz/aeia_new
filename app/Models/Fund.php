<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    protected $table = 'funds';

    protected $fillable = [
        'category',
        'name',
        'description',
        'initial_amount',
        'current_amount',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'json',
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function histories()
    {
        return $this->hasMany(FundHistory::class);
    }

    /**
     * Alias para acceso más fácil
     */
    public function history()
    {
        return $this->hasMany(FundHistory::class);
    }

    public function investmentEarnings()
    {
        return $this->hasMany(InvestmentEarning::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function projectClosures()
    {
        return $this->hasMany(ProjectClosure::class);
    }

    // Métodos útiles
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function getFluctuation(): float
    {
        return floatval($this->current_amount) - floatval($this->initial_amount);
    }

    public function getFluctuationPercent(): float
    {
        if ($this->initial_amount == 0) return 0;
        return ((floatval($this->current_amount) - floatval($this->initial_amount)) / floatval($this->initial_amount)) * 100;
    }

    public function getTotalParticipants(): int
    {
        return $this->investmentEarnings()->count();
    }

    public function getTotalInvested(): float
    {
        return floatval($this->investmentEarnings()->sum('initial_amount')) ?? 0;
    }
}
