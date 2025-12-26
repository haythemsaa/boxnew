<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'tenant_id',
        'referral_code_id',
        'referrer_customer_id',
        'referee_customer_id',
        'booking_id',
        'contract_id',
        'status',
        'qualified_at',
        'rewarded_at',
        'reward_snapshot',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'reward_snapshot' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referee_customer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    public function isRewarded(): bool
    {
        return $this->status === 'rewarded';
    }

    public function markAsQualified(): void
    {
        $this->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);
    }

    public function markAsRewarded(): void
    {
        $this->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeEligibleForReward($query)
    {
        return $query->where('status', 'qualified')
            ->whereNull('rewarded_at');
    }
}
