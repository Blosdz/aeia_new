<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'img_usuario',
        'unique_code',
        'last_login',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // 🔗 Relación con roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // 🔗 Verificar si el usuario tiene un rol
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    // 🔗 Obtener todos los permisos a través de los roles
    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    // 🔗 Verificar si tiene un permiso
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->contains('name', $permissionName);
    }
}
