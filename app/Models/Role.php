<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // Relación con users (muchos a muchos)
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    // Relación con permisos
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
