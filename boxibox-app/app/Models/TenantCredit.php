<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Crédits Email/SMS achetés par un tenant
 */
class TenantCredit extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'credits_purchased',
        'credits_remaining',
        'amount_paid',
        'currency',
        'payment_method',
        'stripe_payment_id',
        'purchased_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'credits_purchased' => 'integer',
        'credits_remaining' => 'integer',
        'amount_paid' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('credits_remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeEmail($query)
    {
        return $query->where('type', 'email');
    }

    public function scopeSms($query)
    {
        return $query->where('type', 'sms');
    }

    // Methods

    /**
     * Utiliser des crédits
     */
    public function useCredits(int $amount): int
    {
        $toUse = min($amount, $this->credits_remaining);
        $this->decrement('credits_remaining', $toUse);

        if ($this->credits_remaining <= 0) {
            $this->update(['status' => 'exhausted']);
        }

        return $toUse;
    }

    /**
     * Vérifier si expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Marquer comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }
}
