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
        'first_name',
        'last_name',
        'email',
        'password',
        'img_usuario',
        'unique_code',
        'referral_code',
        'referred_by_user_id',
        'referral_accepted_at',
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
            'referral_accepted_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->contains('name', $permissionName);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'asesor_id');
    }

    // Relaciones de referral
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function clientRewards()
    {
        return $this->hasMany(Reward::class, 'client_user_id');
    }

    public function referrerRewards()
    {
        return $this->hasMany(Reward::class, 'referrer_user_id');
    }

    public function getReferralCode(): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        // Generar código de referral si no existe
        $code = 'REF_' . strtoupper(substr($this->unique_code ?? $this->email, 0, 6)) . '_' . $this->id;
        $this->update(['referral_code' => $code]);
        return $code;
    }
}
