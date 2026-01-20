<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCommissionHistory extends Model
{
    protected $table = 'referral_commission_histories';

    public $timestamps = false;

    protected $fillable = [
        'commission_id',
        'previous_amount',
        'new_amount',
        'event',
        'reason',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'recorded_at' => 'datetime',
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function commission()
    {
        return $this->belongsTo(ReferralCommission::class);
    }
}
