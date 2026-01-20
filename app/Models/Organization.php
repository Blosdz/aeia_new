<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $table = 'organization';

    protected $fillable = [
        'name',
        'uuid',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Perfiles de jefes (boss) de la organización
     */
    public function bossProfiles(): HasMany
    {
        return $this->hasMany(ProfileBoss::class);
    }

    /**
     * Obtener todos los perfiles de staff asociados a esta organización
     */
    public function staffProfiles()
    {
        return $this->hasManyThrough(
            ProfileStaff::class,
            ProfileBoss::class,
            'organization_id', // Foreign key en profile_boss
            'boss_id',         // Foreign key en profile_staff
            'id',              // Local key en organization
            'id'               // Local key en profile_boss
        );
    }
}
