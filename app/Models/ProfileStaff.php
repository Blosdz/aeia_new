<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStaff extends Model
{
    protected $table = 'profile_staff';

    protected $fillable = [
        'profile_id',
        'boss_id',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function boss()
    {
        return $this->belongsTo(ProfileBoss::class, 'boss_id');
    }
}
