<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    protected $table = 'plan_types';

    public $timestamps = false;
    
    protected $fillable = [
        'category',
        'name',
        'amount_min',
        'amount_max',
        'img_url',
        'periodicity',
        'membership',
    ];

    protected $casts = [
        'amount_min' => 'decimal:2',
        'amount_max' => 'decimal:2',
        'membership' => 'decimal:2',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_type_id');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'plan_type_id');
    }

    public function isInvestment(): bool
    {
        return $this->category === 'investment';
    }

    public function isCoverage(): bool
    {
        return $this->category === 'coverage';
    }

    public function isMonthly(): bool
    {
        return $this->periodicity === 'monthly';
    }

    public function isAnnual(): bool
    {
        return $this->periodicity === 'annual';
    }
}

