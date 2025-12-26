<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReward extends Model
{
    protected $fillable = [
        'tenant_id',
        'referral_id',
        'customer_id',
        'recipient_type',
        'reward_type',
        'reward_value',
        'reward_amount',
        'description',
        'status',
        'applied_at',
        'applied_to_invoice_id',
        'notes',
    ];

    protected $casts = [
        'reward_value' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function apply(?int $invoiceId = null): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
            'applied_to_invoice_id' => $invoiceId,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getFormattedRewardAttribute(): string
    {
        return match ($this->reward_type) {
            'percentage' => $this->reward_value . '% de réduction',
            'fixed' => number_format($this->reward_value, 2) . '€ de réduction',
            'free_month' => $this->reward_value . ' mois gratuit(s)',
            'credit' => number_format($this->reward_value, 2) . '€ de crédit',
            default => '',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeForReferrer($query)
    {
        return $query->where('recipient_type', 'referrer');
    }

    public function scopeForReferee($query)
    {
        return $query->where('recipient_type', 'referee');
    }
}
