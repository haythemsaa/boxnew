<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'code',
        'name',
        'is_active',
        'max_uses',
        'uses_count',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referralCode) {
            if (empty($referralCode->code)) {
                $referralCode->code = static::generateUniqueCode($referralCode->tenant_id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public static function generateUniqueCode(int $tenantId, int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (static::where('tenant_id', $tenantId)->where('code', $code)->exists());

        return $code;
    }

    public static function findValidCode(string $code, int $tenantId): ?self
    {
        return static::where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            })
            ->first();
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    public function getSuccessfulReferralsCountAttribute(): int
    {
        return $this->referrals()
            ->whereIn('status', ['qualified', 'rewarded'])
            ->count();
    }

    public function getPendingReferralsCountAttribute(): int
    {
        return $this->referrals()
            ->where('status', 'pending')
            ->count();
    }

    public function getShareUrlAttribute(): string
    {
        return url("/book/{$this->tenant->slug}?ref={$this->code}");
    }
}
