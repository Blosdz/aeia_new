<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelationAccount extends Model
{
    protected $table = 'relation_accounts';

    protected $fillable = [
        'profile_id',
        'client_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function clientAccount()
    {
        return $this->belongsTo(ClientAccount::class);
    }
}
