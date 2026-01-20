<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reward extends Model
{
    protected $table = 'rewards';

    protected $fillable = [
        // NUEVO: Campos para cierre de proyecto
        'client_user_id',
        'fund_id',
        'deposit_id',
        'total_investment',
        'total_earnings',
        'company_percentage',
        'company_deduction',
        'was_referred',
        'referrer_user_id',
        'referral_percentage',
        'referral_deduction',
        'net_earnings',
        'status',
        'closed_at',
        'paid_at',
        
        // ANTIGUO: Mantener compatibilidad
        'subscriber_profile_id',
        'payment_id',
        'subscription_id',
        'asesor_id',
        'earning_history_id',
        'reason',
        'percentage',
        'amount',
        'currency',
        'period_at',
        'metadata',
    ];

    protected $casts = [
        // NUEVO: Casteos para cierre
        'total_investment' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'company_percentage' => 'decimal:2',
        'company_deduction' => 'decimal:2',
        'referral_percentage' => 'decimal:2',
        'referral_deduction' => 'decimal:2',
        'net_earnings' => 'decimal:2',
        'was_referred' => 'boolean',
        'closed_at' => 'datetime',
        'paid_at' => 'datetime',
        
        // ANTIGUO: Casteos existentes
        'metadata' => 'array',
        'period_at' => 'date',
    ];

    // ==================== NUEVAS RELACIONES ====================

    /**
     * Cliente que invirtió
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * Fondo asociado
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * PaymentAllocation original (allocation del pago)
     */
    public function deposit(): BelongsTo
    {
        return $this->belongsTo(PaymentAllocation::class, 'deposit_id');
    }

    /**
     * Staff que hizo la referencia (si aplica)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Comisión del referido asociada
     */
    public function referralCommission(): HasOne
    {
        return $this->hasOne(ReferralCommission::class, 'reward_id');
    }

    // ==================== RELACIONES ANTIGUAS ====================

    public function subscriberProfile()
    {
        return $this->belongsTo(Profile::class, 'subscriber_profile_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fundOld()
    {
        return $this->belongsTo(Fondo::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function earningHistory()
    {
        return $this->belongsTo(InvestmentEarningHistory::class, 'earning_history_id');
    }

    public function clientDeposits()
    {
        return $this->hasMany(ClientDeposit::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Rewards pagados
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Rewards cerrados pero no pagados
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed')->whereNull('paid_at');
    }

    /**
     * Scope: Rewards pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Rewards de un cliente
     */
    public function scopeByClient($query, $userId)
    {
        return $query->where('client_user_id', $userId);
    }

    /**
     * Scope: Rewards de un fondo (cierre de proyecto)
     */
    public function scopeByFund($query, $fundId)
    {
        return $query->where('fund_id', $fundId);
    }

    /**
     * Scope: Rewards referidos
     */
    public function scopeReferred($query)
    {
        return $query->where('was_referred', true);
    }
}
