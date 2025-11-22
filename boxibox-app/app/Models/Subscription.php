<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan',
        'billing_period',
        'status',
        'trial_ends_at',
        'started_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'amount',
        'discount',
        'currency',
        'stripe_subscription_id',
        'stripe_customer_id',
        'quantity_sites',
        'quantity_boxes',
        'quantity_users',
        'features',
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'started_at' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'cancelled_at' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'quantity_sites' => 'integer',
        'quantity_boxes' => 'integer',
        'quantity_users' => 'integer',
        'features' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing')
            ->whereDate('trial_ends_at', '>=', now());
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function getIsTrialingAttribute(): bool
    {
        return $this->status === 'trialing' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getDaysUntilRenewalAttribute(): ?int
    {
        if (!$this->current_period_end) {
            return null;
        }
        return now()->diffInDays($this->current_period_end, false);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->amount - $this->discount;
    }

    // Helper Methods
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);
    }

    public function renew(): void
    {
        $months = $this->billing_period === 'yearly' ? 12 : 1;

        $this->update([
            'current_period_start' => $this->current_period_end,
            'current_period_end' => $this->current_period_end->addMonths($months),
        ]);
    }

    public function changePlan(string $newPlan, array $quantities = []): void
    {
        $this->update(array_merge([
            'plan' => $newPlan,
        ], $quantities));
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
