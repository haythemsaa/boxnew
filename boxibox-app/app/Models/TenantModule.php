<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    protected $fillable = [
        'tenant_id',
        'module_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'price',
        'billing_cycle',
        'is_demo',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_demo' => 'boolean',
        'metadata' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Tenant propriétaire
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
     * Vérifier si le module est actif
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active' && $this->status !== 'trial') {
            return false;
        }

        // Vérifier si la période d'essai est expirée
        if ($this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return false;
        }

        // Vérifier si la date de fin est passée
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Jours restants (essai ou abonnement)
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->status === 'trial' && $this->trial_ends_at) {
            return max(0, now()->diffInDays($this->trial_ends_at, false));
        }

        if ($this->ends_at) {
            return max(0, now()->diffInDays($this->ends_at, false));
        }

        return null;
    }

    /**
     * Scope pour les modules actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope pour les démos
     */
    public function scopeDemo($query)
    {
        return $query->where('is_demo', true);
    }

    /**
     * Scope pour les essais
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '>=', now());
    }
}
