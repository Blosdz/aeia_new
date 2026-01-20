<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ClientPayment model - Alias/Deprecated
 * Use Payment model instead
 * This is kept for backwards compatibility
 */
class ClientPayment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'client_account_id',
        'payer_profile_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function clientAccount()
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function payerProfile()
    {
        return $this->belongsTo(Profile::class, 'payer_profile_id');
    }
}

