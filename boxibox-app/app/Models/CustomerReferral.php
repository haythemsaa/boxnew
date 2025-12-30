<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'referrer_customer_id',
        'referred_customer_id',
        'referral_code',
        'invited_email',
        'invited_phone',
        'invited_name',
        'status',
        'contract_id',
        'referrer_reward',
        'referred_reward',
        'reward_type',
        'referrer_reward_paid',
        'referred_reward_applied',
        'reward_paid_at',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip_address',
        'expires_at',
        'registered_at',
        'converted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'registered_at' => 'datetime',
        'converted_at' => 'datetime',
        'reward_paid_at' => 'datetime',
        'referrer_reward' => 'decimal:2',
        'referred_reward' => 'decimal:2',
        'referrer_reward_paid' => 'boolean',
        'referred_reward_applied' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referral) {
            $referral->uuid = $referral->uuid ?? Str::uuid();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_customer_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByReferrer($query, int $customerId)
    {
        return $query->where('referrer_customer_id', $customerId);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isRewarded(): bool
    {
        return $this->status === 'rewarded';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at < now());
    }

    public function markAsRegistered(Customer $customer): void
    {
        $this->update([
            'status' => 'registered',
            'referred_customer_id' => $customer->id,
            'registered_at' => now(),
        ]);
    }

    public function markAsConverted(Contract $contract): void
    {
        $this->update([
            'status' => 'converted',
            'contract_id' => $contract->id,
            'converted_at' => now(),
        ]);
    }

    public function payRewards(): void
    {
        if ($this->status !== 'converted') {
            return;
        }

        // Credit referrer
        if ($this->referrer && $this->referrer_reward > 0) {
            $this->referrer->increment('referral_credits', $this->referrer_reward);
            $this->referrer_reward_paid = true;
        }

        // Apply discount to referred customer
        if ($this->referred && $this->referred_reward > 0) {
            $this->referred->increment('referral_credits', $this->referred_reward);
            $this->referred_reward_applied = true;
        }

        $this->update([
            'status' => 'rewarded',
            'referrer_reward_paid' => $this->referrer_reward_paid,
            'referred_reward_applied' => $this->referred_reward_applied,
            'reward_paid_at' => now(),
        ]);
    }

    public function expire(): void
    {
        if ($this->isPending()) {
            $this->update(['status' => 'expired']);
        }
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'registered' => 'Inscrit',
            'converted' => 'Converti',
            'rewarded' => 'Récompensé',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getRewardTypeLabelAttribute(): string
    {
        return match($this->reward_type) {
            'credit' => 'Crédit',
            'discount_percent' => 'Remise %',
            'free_month' => 'Mois gratuit',
            'cash' => 'Espèces',
            default => 'Crédit',
        };
    }

    public function getShareUrl(): string
    {
        return url('/r/' . $this->referral_code);
    }

    public function getInvitedDisplayNameAttribute(): string
    {
        return $this->invited_name ?? $this->invited_email ?? $this->invited_phone ?? 'Invité';
    }
}
