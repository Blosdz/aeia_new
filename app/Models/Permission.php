<?php

namespace App\Models;

// app/Models/Permission.php


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // Relación con roles (muchos a muchos)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
