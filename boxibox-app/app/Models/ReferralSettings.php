<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_active',
        'referrer_reward_type',
        'referrer_reward_value',
        'referrer_reward_description',
        'referrer_reward_max_uses',
        'referee_reward_type',
        'referee_reward_value',
        'referee_reward_description',
        'min_rental_months',
        'max_referrals_per_customer',
        'reward_delay_days',
        'require_active_contract',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referrer_reward_value' => 'decimal:2',
        'referee_reward_value' => 'decimal:2',
        'require_active_contract' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function getForTenant(int $tenantId, ?int $siteId = null): ?self
    {
        // Try site-specific first
        if ($siteId) {
            $settings = static::where('tenant_id', $tenantId)
                ->where('site_id', $siteId)
                ->first();

            if ($settings) {
                return $settings;
            }
        }

        // Fall back to tenant-level
        return static::where('tenant_id', $tenantId)
            ->whereNull('site_id')
            ->first();
    }

    public function getReferrerRewardLabelAttribute(): string
    {
        return match ($this->referrer_reward_type) {
            'percentage' => $this->referrer_reward_value . '% de réduction',
            'fixed' => $this->referrer_reward_value . '€ de réduction',
            'free_month' => $this->referrer_reward_value . ' mois gratuit(s)',
            default => '',
        };
    }

    public function getRefereeRewardLabelAttribute(): string
    {
        return match ($this->referee_reward_type) {
            'percentage' => $this->referee_reward_value . '% de réduction',
            'fixed' => $this->referee_reward_value . '€ de réduction',
            'free_month' => $this->referee_reward_value . ' mois gratuit(s)',
            default => '',
        };
    }
}
