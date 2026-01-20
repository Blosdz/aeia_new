<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Declaracion extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'profile_id',
        'payment_id',
        'contract_type',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'signed_at' => 'datetime',
    ];

    public $timestamps = false;

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}

