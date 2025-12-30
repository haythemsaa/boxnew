<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'is_active',
        'program_name',
        'referrer_reward_amount',
        'referred_reward_amount',
        'reward_type',
        'min_contract_months',
        'reward_delay_days',
        'referral_expiry_days',
        'max_referrals_per_customer',
        'email_template',
        'sms_template',
        'terms_conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referrer_reward_amount' => 'decimal:2',
        'referred_reward_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function getForTenant(int $tenantId): ?self
    {
        return static::where('tenant_id', $tenantId)->first();
    }

    public static function createDefaultForTenant(int $tenantId): self
    {
        return static::create([
            'tenant_id' => $tenantId,
            'is_active' => true,
            'program_name' => 'Programme Parrainage',
            'referrer_reward_amount' => 25.00,
            'referred_reward_amount' => 25.00,
            'reward_type' => 'credit',
            'min_contract_months' => 1,
            'reward_delay_days' => 30,
            'referral_expiry_days' => 90,
            'max_referrals_per_customer' => null,
        ]);
    }
}
