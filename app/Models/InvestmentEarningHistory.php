<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentEarningHistory extends Model
{
    protected $table = 'investment_earnings_history';

    public $timestamps = false;

    protected $fillable = [
        'earning_id',
        'previous_amount',
        'new_amount',
        'fluctuation_percent',
        'reason',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'fluctuation_percent' => 'decimal:4',
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function earning()
    {
        return $this->belongsTo(InvestmentEarning::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'earning_history_id');
    }
}
