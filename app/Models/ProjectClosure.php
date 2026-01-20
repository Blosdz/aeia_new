<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClosure extends Model
{
    protected $table = 'project_closures';

    protected $fillable = [
        'fund_id',
        'period_start',
        'period_end',
        'total_investment',
        'total_earnings',
        'total_clients',
        'company_total',
        'referrals_total',
        'clients_earnings',
        'total_referrals',
        'first_deposits_referred',
        'status',
        'calculated_at',
        'distributed_at',
        'closed_at',
    ];

    protected $casts = [
        'total_investment' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'company_total' => 'decimal:2',
        'referrals_total' => 'decimal:2',
        'clients_earnings' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'calculated_at' => 'datetime',
        'distributed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Fondo del cierre
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * Rewards del cierre
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'fund_id', 'fund_id');
    }

    /**
     * Comisiones de referidos del cierre
     */
    public function referralCommissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Proyectos cerrados
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope: Proyectos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Proyectos de un fondo
     */
    public function scopeByFund($query, $fundId)
    {
        return $query->where('fund_id', $fundId);
    }

    /**
     * Scope: Proyectos entre fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate])
                     ->orWhereBetween('period_end', [$startDate, $endDate]);
    }

    /**
     * Obtener el total distribuido (empresa + referidos + clientes)
     */
    public function getTotalDistributedAttribute()
    {
        return $this->company_total + $this->referrals_total + $this->clients_earnings;
    }

    /**
     * Obtener el porcentaje de comisiones de referidos
     */
    public function getReferralPercentageAttribute()
    {
        if ($this->total_investment == 0) return 0;
        return ($this->referrals_total / $this->total_investment) * 100;
    }

    /**
     * Obtener el porcentaje para clientes
     */
    public function getClientPercentageAttribute()
    {
        if ($this->total_investment == 0) return 0;
        return ($this->clients_earnings / $this->total_investment) * 100;
    }
}
