<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotation';

    protected $fillable = [
        'lead_id',
        'transaction_id',
        'user_id',
        'currency',
        'status',
        'plan_type_id',
        'url_payment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function planType()
    {
        return $this->belongsTo(Plans::class, 'plan_type_id');
    }

    public function paymentLeads()
    {
        return $this->hasMany(PaymentLead::class);
    }
}
