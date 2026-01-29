<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'type',
        'type_document',
        'dni',
        'phone_extension',
        'phone',
        'nacionality',
        'city',
        'country',
        'job',
        'country_dni',
        'state',
        'birthdate',
        'sex',
        'photos_dni',
        'photo_id_type',
        'signature_digital',
        'verified',
        'verification_status',
        'verification_notes',
        'verified_at',
        'verified_by',
        'bio',
    ];

    protected $casts = [
        'photos_dni' => 'array',
        'birthdate' => 'date:Y-m-d',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clientAccounts()
    {
        return $this->belongsToMany(ClientAccount::class, 'relation_accounts')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function relationAccounts()
    {
        return $this->hasMany(RelationAccount::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'payer_profile_id');
    }

    public function subscriptionParticipants()
    {
        return $this->hasMany(SubscriptionParticipant::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'subscriber_profile_id');
    }

    public function clientDeposits()
    {
        return $this->hasMany(ClientDeposit::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function staffProfile()
    {
        return $this->hasOne(ProfileStaff::class);
    }

    public function bossProfile()
    {
        return $this->hasOne(ProfileBoss::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(ProfileBeneficiary::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'profile_id');
    }

    public function referralCommissions()
    {
        return $this->hasMany(ReferralCommission::class, 'advisor_profile_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}

