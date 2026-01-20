<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLead extends Model
{
    protected $table = 'payment_leads';

    protected $fillable = [
        'transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'quotation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
