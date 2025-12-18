<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'logo_url',
        'website',
        'plan',
        'widget_level', // none, basic, pro, whitelabel
        'billing_cycle', // monthly, yearly
        'max_sites',
        'max_boxes',
        'max_users',
        'total_customers',
        'monthly_revenue',
        'occupation_rate',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
        'stripe_customer_id',
        'payment_gateway',
        'company_number',
        'vat_number',
        'settings',
        'features',
        'plan_elements',
        'addons', // array of active addons
        // Subscription & Quotas
        'current_plan_id',
        'emails_sent_this_month',
        'sms_sent_this_month',
        'usage_reset_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'plan_elements' => 'array',
        'addons' => 'array',
        'trial_ends_at' => 'date',
        'subscription_ends_at' => 'date',
        'is_active' => 'boolean',
        'monthly_revenue' => 'decimal:2',
        'occupation_rate' => 'decimal:2',
        'max_sites' => 'integer',
        'max_boxes' => 'integer',
        'max_users' => 'integer',
        'total_customers' => 'integer',
        'deleted_at' => 'datetime',
        'current_plan_id' => 'integer',
        'emails_sent_this_month' => 'integer',
        'sms_sent_this_month' => 'integer',
        'usage_reset_at' => 'datetime',
    ];

    // Plan Constants
    public const PLAN_STARTER = 'starter';
    public const PLAN_PROFESSIONAL = 'professional';
    public const PLAN_BUSINESS = 'business';
    public const PLAN_ENTERPRISE = 'enterprise';

    public const WIDGET_NONE = 'none';
    public const WIDGET_BASIC = 'basic';
    public const WIDGET_PRO = 'pro';
    public const WIDGET_WHITELABEL = 'whitelabel';

    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'current_plan_id');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(TenantCredit::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(TenantUsage::class);
    }

    public function emailProviders(): HasMany
    {
        return $this->hasMany(TenantEmailProvider::class);
    }

    public function smsProviders(): HasMany
    {
        return $this->hasMany(TenantSmsProvider::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    // Accessors
    public function getIsTrialAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getIsSubscriptionActiveAttribute(): bool
    {
        return $this->is_active &&
               (!$this->subscription_ends_at || $this->subscription_ends_at->isFuture());
    }

    // Helper Methods
    public function canAddSite(): bool
    {
        return $this->sites()->count() < $this->max_sites;
    }

    public function canAddBox(): bool
    {
        return $this->boxes()->count() < $this->max_boxes;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_customers' => $this->customers()->count(),
            'occupation_rate' => $this->calculateOccupationRate(),
            'monthly_revenue' => $this->calculateMonthlyRevenue(),
        ]);
    }

    public function calculateMonthlyRevenue(): float
    {
        return $this->contracts()
            ->where('status', 'active')
            ->sum('monthly_price');
    }

    public function calculateOccupationRate(): float
    {
        $totalBoxes = $this->boxes()->count();
        if ($totalBoxes === 0) {
            return 0;
        }
        $occupiedBoxes = $this->boxes()->where('status', 'occupied')->count();
        return round(($occupiedBoxes / $totalBoxes) * 100, 2);
    }

    // Pricing & Plan Methods
    public function getPlanConfig(): ?array
    {
        return PricingPlan::get($this->plan);
    }

    public function getMonthlyPrice(): float
    {
        $planConfig = $this->getPlanConfig();
        if (!$planConfig) {
            return 0;
        }

        $basePrice = $this->billing_cycle === self::BILLING_YEARLY
            ? ($planConfig['price_yearly'] / 12)
            : $planConfig['price_monthly'];

        // Add widget addon price if not included
        $widgetPrice = $this->getWidgetAddonPrice();

        return $basePrice + $widgetPrice;
    }

    public function getWidgetAddonPrice(): float
    {
        $planConfig = $this->getPlanConfig();
        if (!$planConfig) {
            return 0;
        }

        // Get what widget level is included in the plan
        $includedWidget = $planConfig['features']['booking_widget'] ?? false;

        // Map included widget to level
        $includedLevel = match ($includedWidget) {
            'basic' => self::WIDGET_BASIC,
            'pro' => self::WIDGET_PRO,
            true => self::WIDGET_BASIC,
            default => self::WIDGET_NONE,
        };

        // If current widget level is higher than included, charge addon
        if ($this->widget_level === self::WIDGET_NONE || !$this->widget_level) {
            return 0;
        }

        $widgetPrices = [
            self::WIDGET_BASIC => 29,
            self::WIDGET_PRO => 49,
            self::WIDGET_WHITELABEL => 99,
        ];

        $includedPrices = [
            self::WIDGET_NONE => 0,
            self::WIDGET_BASIC => 29,
            self::WIDGET_PRO => 49,
            self::WIDGET_WHITELABEL => 99,
        ];

        $currentPrice = $widgetPrices[$this->widget_level] ?? 0;
        $includedPrice = $includedPrices[$includedLevel] ?? 0;

        // Only charge the difference
        return max(0, $currentPrice - $includedPrice);
    }

    public function hasFeature(string $feature): bool
    {
        return PricingPlan::hasFeature($this->plan, $feature);
    }

    public function hasWidgetAccess(): bool
    {
        return $this->widget_level && $this->widget_level !== self::WIDGET_NONE;
    }

    public function hasPromoCodeFeature(): bool
    {
        return in_array($this->widget_level, [self::WIDGET_PRO, self::WIDGET_WHITELABEL]);
    }

    public function hasInteractiveMapSelection(): bool
    {
        return in_array($this->widget_level, [self::WIDGET_PRO, self::WIDGET_WHITELABEL]);
    }

    public function hasWhiteLabelWidget(): bool
    {
        return $this->widget_level === self::WIDGET_WHITELABEL;
    }

    public function canUseSepa(): bool
    {
        return in_array($this->plan, [self::PLAN_BUSINESS, self::PLAN_ENTERPRISE]);
    }

    public function canUseDynamicPricing(): bool
    {
        return in_array($this->plan, [self::PLAN_BUSINESS, self::PLAN_ENTERPRISE]);
    }

    public function canUseApi(): bool
    {
        return in_array($this->plan, [self::PLAN_PROFESSIONAL, self::PLAN_BUSINESS, self::PLAN_ENTERPRISE]);
    }

    public function getApiAccessLevel(): string
    {
        return match ($this->plan) {
            self::PLAN_PROFESSIONAL => 'read',
            self::PLAN_BUSINESS, self::PLAN_ENTERPRISE => 'full',
            default => 'none',
        };
    }

    // Email & SMS Quota Methods

    /**
     * Get current month usage record
     */
    public function getCurrentUsage(): TenantUsage
    {
        return TenantUsage::currentMonth($this->id);
    }

    /**
     * Get remaining email quota for current month
     */
    public function getEmailsRemainingAttribute(): int
    {
        $usage = $this->getCurrentUsage();
        return max(0, $usage->emails_quota - $usage->emails_sent);
    }

    /**
     * Get remaining SMS quota for current month
     */
    public function getSmsRemainingAttribute(): int
    {
        $usage = $this->getCurrentUsage();
        return max(0, $usage->sms_quota - $usage->sms_sent);
    }

    /**
     * Get total available email credits (purchased packs)
     */
    public function getEmailCreditsAttribute(): int
    {
        return $this->credits()
            ->where('type', 'email')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('credits_remaining');
    }

    /**
     * Get total available SMS credits (purchased packs)
     */
    public function getSmsCreditsAttribute(): int
    {
        return $this->credits()
            ->where('type', 'sms')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('credits_remaining');
    }

    /**
     * Check if tenant can send emails (quota or credits available)
     */
    public function canSendEmail(): bool
    {
        // Check plan quota
        if ($this->emails_remaining > 0) {
            return true;
        }

        // Check purchased credits
        if ($this->email_credits > 0) {
            return true;
        }

        // Check if custom provider allowed and configured
        $plan = $this->subscriptionPlan;
        if ($plan && $plan->custom_email_provider_allowed) {
            return $this->emailProviders()->where('is_active', true)->where('is_verified', true)->exists();
        }

        return false;
    }

    /**
     * Check if tenant can send SMS (quota or credits available)
     */
    public function canSendSms(): bool
    {
        // Check plan quota
        if ($this->sms_remaining > 0) {
            return true;
        }

        // Check purchased credits
        if ($this->sms_credits > 0) {
            return true;
        }

        // Check if custom provider allowed and configured
        $plan = $this->subscriptionPlan;
        if ($plan && $plan->custom_sms_provider_allowed) {
            return $this->smsProviders()->where('is_active', true)->where('is_verified', true)->exists();
        }

        return false;
    }

    /**
     * Get email quota usage percentage
     */
    public function getEmailUsagePercentAttribute(): float
    {
        $usage = $this->getCurrentUsage();
        return $usage->email_usage_percent;
    }

    /**
     * Get SMS quota usage percentage
     */
    public function getSmsUsagePercentAttribute(): float
    {
        $usage = $this->getCurrentUsage();
        return $usage->sms_usage_percent;
    }

    /**
     * Check if custom email provider is allowed
     */
    public function canUseCustomEmailProvider(): bool
    {
        $plan = $this->subscriptionPlan;
        return $plan && $plan->custom_email_provider_allowed;
    }

    /**
     * Check if custom SMS provider is allowed
     */
    public function canUseCustomSmsProvider(): bool
    {
        $plan = $this->subscriptionPlan;
        return $plan && $plan->custom_sms_provider_allowed;
    }

    /**
     * Get active email provider (custom if available, null for shared)
     */
    public function getActiveEmailProvider(): ?TenantEmailProvider
    {
        if (!$this->canUseCustomEmailProvider()) {
            return null;
        }

        return $this->emailProviders()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->first();
    }

    /**
     * Get active SMS provider (custom if available, null for shared)
     */
    public function getActiveSmsProvider(): ?TenantSmsProvider
    {
        if (!$this->canUseCustomSmsProvider()) {
            return null;
        }

        return $this->smsProviders()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->first();
    }

    /**
     * Get communication limits summary
     */
    public function getCommunicationLimitsAttribute(): array
    {
        $plan = $this->subscriptionPlan;
        $usage = $this->getCurrentUsage();

        return [
            'email' => [
                'quota' => $usage->emails_quota,
                'used' => $usage->emails_sent,
                'remaining' => $usage->emails_remaining,
                'credits' => $this->email_credits,
                'percent' => $usage->email_usage_percent,
                'can_use_custom' => $this->canUseCustomEmailProvider(),
                'has_custom' => $this->emailProviders()->where('is_active', true)->exists(),
            ],
            'sms' => [
                'quota' => $usage->sms_quota,
                'used' => $usage->sms_sent,
                'remaining' => $usage->sms_remaining,
                'credits' => $this->sms_credits,
                'percent' => $usage->sms_usage_percent,
                'can_use_custom' => $this->canUseCustomSmsProvider(),
                'has_custom' => $this->smsProviders()->where('is_active', true)->exists(),
            ],
            'plan' => $plan ? [
                'name' => $plan->name,
                'emails_per_month' => $plan->emails_per_month,
                'sms_per_month' => $plan->sms_per_month,
            ] : null,
        ];
    }
}
