<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileBoss extends Model
{
    protected $table = 'profile_boss';

    protected $fillable = [
        'profile_id',
        'organization_id',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function staffMembers()
    {
        return $this->hasMany(ProfileStaff::class, 'boss_id');
    }
}
