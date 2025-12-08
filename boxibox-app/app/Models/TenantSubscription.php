<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_cycle',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'price',
        'payment_method',
        'stripe_subscription_id',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'cancelled_at' => 'date',
        'metadata' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Plan d'abonnement
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Vérifier si l'abonnement est actif
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    /**
     * Vérifier si en période d'essai
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Jours restants d'essai
     */
    public function getTrialDaysRemainingAttribute(): ?int
    {
        if (!$this->isOnTrial()) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Scope pour les abonnements actifs
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial']);
    }

    /**
     * Scope pour les essais
     */
    public function scopeOnTrial($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '>=', now());
    }

    /**
     * Scope expirant bientôt
     */
    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }
}
