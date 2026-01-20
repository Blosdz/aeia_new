<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FondoHistorial extends Model
{
    protected $table = 'fund_histories';

    public $timestamps = false;

    protected $fillable = [
        'fund_id',
        'fluctuation_percent',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function fund()
    {
        return $this->belongsTo(Fondo::class, 'fund_id');
    }
}

