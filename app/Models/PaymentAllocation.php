<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $table = 'payment_allocations';

    protected $fillable = [
        'payment_id',
        'subscription_id',
        'fund_id',
        'amount',
        'total_amount',
        'percent',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'json',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'percent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function isAccrued(): bool
    {
        return $this->status === 'accrued';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
