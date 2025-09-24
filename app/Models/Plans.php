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
    ];
}
