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
        'postal_code',
        'country',
        'logo',
        'primary_color',
        'secondary_color',
        'plan',
        'max_sites',
        'max_boxes',
        'max_users',
        'total_sites',
        'total_boxes',
        'total_users',
        'total_customers',
        'total_contracts',
        'monthly_revenue',
        'total_revenue',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'stripe_customer_id',
        'payment_gateway',
        'billing_email',
        'tax_id',
        'settings',
        'features',
        'last_activity_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'monthly_revenue' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'max_sites' => 'integer',
        'max_boxes' => 'integer',
        'max_users' => 'integer',
        'total_sites' => 'integer',
        'total_boxes' => 'integer',
        'total_users' => 'integer',
        'total_customers' => 'integer',
        'total_contracts' => 'integer',
    ];

    // Relationships
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
        return $query->where('status', 'active');
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
        return $this->status === 'active' &&
               (!$this->subscription_ends_at || $this->subscription_ends_at->isFuture());
    }

    // Helper Methods
    public function canAddSite(): bool
    {
        return $this->total_sites < $this->max_sites;
    }

    public function canAddBox(): bool
    {
        return $this->total_boxes < $this->max_boxes;
    }

    public function canAddUser(): bool
    {
        return $this->total_users < $this->max_users;
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_sites' => $this->sites()->count(),
            'total_boxes' => $this->boxes()->count(),
            'total_customers' => $this->customers()->count(),
            'total_contracts' => $this->contracts()->count(),
        ]);
    }

    public function calculateMonthlyRevenue(): float
    {
        return $this->contracts()
            ->where('status', 'active')
            ->sum('monthly_price');
    }
}
