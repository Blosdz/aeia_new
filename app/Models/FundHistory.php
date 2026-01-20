<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundHistory extends Model
{
    protected $table = 'fund_histories';

    public $timestamps = false;

    protected $fillable = [
        'fund_id',
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

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
