<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
        'is_enabled',
        'enabled_for_tenants',
        'enabled_for_plans',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_for_tenants' => 'array',
        'enabled_for_plans' => 'array',
    ];

    public static function isEnabled(string $key, ?Tenant $tenant = null): bool
    {
        $flag = self::where('key', $key)->first();

        if (!$flag) {
            return false;
        }

        // Global flag check
        if ($flag->is_enabled) {
            return true;
        }

        // Tenant specific check
        if ($tenant) {
            // Check if enabled for specific tenant
            if ($flag->enabled_for_tenants && in_array($tenant->id, $flag->enabled_for_tenants)) {
                return true;
            }

            // Check if enabled for tenant's plan
            if ($flag->enabled_for_plans && in_array($tenant->plan, $flag->enabled_for_plans)) {
                return true;
            }
        }

        return false;
    }

    public static function enable(string $key): bool
    {
        return self::where('key', $key)->update(['is_enabled' => true]) > 0;
    }

    public static function disable(string $key): bool
    {
        return self::where('key', $key)->update(['is_enabled' => false]) > 0;
    }

    public function enableForTenant(int $tenantId): self
    {
        $tenants = $this->enabled_for_tenants ?? [];
        if (!in_array($tenantId, $tenants)) {
            $tenants[] = $tenantId;
            $this->update(['enabled_for_tenants' => $tenants]);
        }
        return $this;
    }

    public function disableForTenant(int $tenantId): self
    {
        $tenants = $this->enabled_for_tenants ?? [];
        $tenants = array_filter($tenants, fn($id) => $id !== $tenantId);
        $this->update(['enabled_for_tenants' => array_values($tenants)]);
        return $this;
    }

    public function enableForPlan(string $plan): self
    {
        $plans = $this->enabled_for_plans ?? [];
        if (!in_array($plan, $plans)) {
            $plans[] = $plan;
            $this->update(['enabled_for_plans' => $plans]);
        }
        return $this;
    }

    public function disableForPlan(string $plan): self
    {
        $plans = $this->enabled_for_plans ?? [];
        $plans = array_filter($plans, fn($p) => $p !== $plan);
        $this->update(['enabled_for_plans' => array_values($plans)]);
        return $this;
    }
}
