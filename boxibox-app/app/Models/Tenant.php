<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
