<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoHistory extends Model
{
    protected $table = 'demo_history';

    protected $fillable = [
        'tenant_id',
        'module_id',
        'plan_id',
        'demo_type',
        'started_at',
        'ends_at',
        'converted_at',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ends_at' => 'date',
        'converted_at' => 'date',
    ];

    /**
     * Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Module
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Créateur
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Jours restants
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->status !== 'active') {
            return null;
        }
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Est expiré
     */
    public function isExpired(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * Scope actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>=', now());
    }

    /**
     * Scope expirant bientôt
     */
    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }
}
