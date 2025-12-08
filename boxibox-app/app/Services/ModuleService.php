<?php

namespace App\Services;

use App\Models\Module;
use App\Models\TenantModule;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ModuleService
{
    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'tenant_modules_';
    const CACHE_TTL = 3600; // 1 heure

    /**
     * Vérifier si un tenant a accès à un module
     */
    public function hasModule(int $tenantId, string $moduleCode): bool
    {
        $enabledModules = $this->getEnabledModules($tenantId);
        return in_array($moduleCode, $enabledModules);
    }

    /**
     * Obtenir tous les modules activés pour un tenant
     */
    public function getEnabledModules(int $tenantId): array
    {
        $cacheKey = self::CACHE_PREFIX . $tenantId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $modules = [];

            // 1. Modules core (toujours activés)
            $coreModules = Module::where('is_core', true)->pluck('code')->toArray();
            $modules = array_merge($modules, $coreModules);

            // 2. Modules inclus dans le plan actuel
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->current_plan_id) {
                $plan = SubscriptionPlan::find($tenant->current_plan_id);
                if ($plan && $plan->included_modules) {
                    $planModuleCodes = Module::whereIn('id', $plan->included_modules)
                        ->pluck('code')
                        ->toArray();
                    $modules = array_merge($modules, $planModuleCodes);
                }
            }

            // 3. Modules additionnels achetés séparément
            $additionalModules = TenantModule::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->with('module')
                ->get()
                ->pluck('module.code')
                ->toArray();

            $modules = array_merge($modules, $additionalModules);

            // 4. Modules en période d'essai
            $trialModules = TenantModule::where('tenant_id', $tenantId)
                ->where('status', 'trial')
                ->where('trial_ends_at', '>=', now())
                ->with('module')
                ->get()
                ->pluck('module.code')
                ->toArray();

            $modules = array_merge($modules, $trialModules);

            return array_unique($modules);
        });
    }

    /**
     * Vérifier si une route est accessible pour un tenant
     */
    public function canAccessRoute(int $tenantId, string $routeName): bool
    {
        $enabledModules = $this->getEnabledModules($tenantId);

        // Charger les routes de tous les modules activés
        $modules = Module::whereIn('code', $enabledModules)->get();

        foreach ($modules as $module) {
            if ($module->routes) {
                foreach ($module->routes as $pattern) {
                    if ($this->matchRoutePattern($routeName, $pattern)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Matcher un pattern de route
     */
    protected function matchRoutePattern(string $routeName, string $pattern): bool
    {
        // Convertir le pattern en regex
        $regex = str_replace('.', '\.', $pattern);
        $regex = str_replace('*', '.*', $regex);
        $regex = '/^' . $regex . '$/';

        return preg_match($regex, $routeName) === 1;
    }

    /**
     * Activer un module pour un tenant
     */
    public function enableModule(int $tenantId, int $moduleId, array $options = []): TenantModule
    {
        $module = Module::findOrFail($moduleId);

        // Vérifier les dépendances
        if ($module->dependencies) {
            foreach ($module->dependencies as $depCode) {
                if (!$this->hasModule($tenantId, $depCode)) {
                    throw new \Exception("Le module {$depCode} est requis pour activer {$module->code}");
                }
            }
        }

        $tenantModule = TenantModule::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
            ],
            [
                'status' => $options['is_trial'] ?? false ? 'trial' : 'active',
                'trial_ends_at' => $options['trial_ends_at'] ?? null,
                'starts_at' => $options['starts_at'] ?? now(),
                'ends_at' => $options['ends_at'] ?? null,
                'price' => $options['price'] ?? $module->monthly_price,
                'billing_cycle' => $options['billing_cycle'] ?? 'monthly',
                'is_demo' => $options['is_demo'] ?? false,
                'metadata' => $options['metadata'] ?? null,
            ]
        );

        // Invalider le cache
        $this->clearCache($tenantId);

        Log::info("Module {$module->code} activé pour tenant {$tenantId}", [
            'module_id' => $moduleId,
            'options' => $options,
        ]);

        return $tenantModule;
    }

    /**
     * Désactiver un module pour un tenant
     */
    public function disableModule(int $tenantId, int $moduleId): bool
    {
        $tenantModule = TenantModule::where('tenant_id', $tenantId)
            ->where('module_id', $moduleId)
            ->first();

        if (!$tenantModule) {
            return false;
        }

        $tenantModule->update(['status' => 'disabled']);

        $this->clearCache($tenantId);

        $module = Module::find($moduleId);
        Log::info("Module {$module->code} désactivé pour tenant {$tenantId}");

        return true;
    }

    /**
     * Démarrer une période d'essai pour un module
     */
    public function startModuleTrial(int $tenantId, int $moduleId, int $days = 14): TenantModule
    {
        return $this->enableModule($tenantId, $moduleId, [
            'is_trial' => true,
            'trial_ends_at' => now()->addDays($days),
            'is_demo' => true,
        ]);
    }

    /**
     * Démarrer une démo complète de l'application
     */
    public function startFullAppDemo(int $tenantId, int $days = 14): void
    {
        // Activer tous les modules en mode démo
        $modules = Module::where('is_core', false)->get();

        foreach ($modules as $module) {
            $this->enableModule($tenantId, $module->id, [
                'is_trial' => true,
                'trial_ends_at' => now()->addDays($days),
                'is_demo' => true,
            ]);
        }

        // Mettre à jour le statut du tenant
        $tenant = Tenant::find($tenantId);
        $tenant->update([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays($days),
        ]);

        Log::info("Démo complète démarrée pour tenant {$tenantId} pour {$days} jours");
    }

    /**
     * Mettre à jour le plan d'un tenant
     */
    public function changePlan(int $tenantId, int $planId, string $billingCycle = 'monthly'): TenantSubscription
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $tenant = Tenant::findOrFail($tenantId);

        // Annuler l'ancien abonnement si existe
        TenantSubscription::where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'trial'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        // Créer le nouvel abonnement
        $price = $billingCycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

        $subscription = TenantSubscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'billing_cycle' => $billingCycle,
            'status' => 'active',
            'starts_at' => now(),
            'price' => $price,
        ]);

        // Mettre à jour le tenant
        $tenant->update([
            'current_plan_id' => $planId,
            'subscription_status' => 'active',
        ]);

        // Désactiver les modules qui ne sont plus inclus
        $includedModuleIds = $plan->included_modules ?? [];
        TenantModule::where('tenant_id', $tenantId)
            ->whereNotIn('module_id', $includedModuleIds)
            ->where('is_demo', false) // Garder les modules achetés séparément
            ->update(['status' => 'disabled']);

        $this->clearCache($tenantId);

        Log::info("Plan changé pour tenant {$tenantId} vers {$plan->code}");

        return $subscription;
    }

    /**
     * Obtenir les détails des modules pour un tenant
     */
    public function getModulesDetailsForTenant(int $tenantId): array
    {
        $allModules = Module::orderBy('category')->orderBy('sort_order')->get();
        $enabledModules = $this->getEnabledModules($tenantId);

        $tenant = Tenant::find($tenantId);
        $currentPlan = $tenant?->current_plan_id ? SubscriptionPlan::find($tenant->current_plan_id) : null;
        $planModuleIds = $currentPlan?->included_modules ?? [];

        $result = [];

        foreach ($allModules as $module) {
            $tenantModule = TenantModule::where('tenant_id', $tenantId)
                ->where('module_id', $module->id)
                ->first();

            $result[] = [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description,
                'icon' => $module->icon,
                'color' => $module->color,
                'category' => $module->category,
                'monthly_price' => $module->monthly_price,
                'yearly_price' => $module->yearly_price,
                'features' => $module->features,
                'is_core' => $module->is_core,
                'is_enabled' => in_array($module->code, $enabledModules),
                'is_included_in_plan' => in_array($module->id, $planModuleIds),
                'is_trial' => $tenantModule?->status === 'trial',
                'is_demo' => $tenantModule?->is_demo ?? false,
                'trial_ends_at' => $tenantModule?->trial_ends_at?->format('Y-m-d'),
                'days_remaining' => $tenantModule?->days_remaining,
            ];
        }

        return $result;
    }

    /**
     * Vider le cache d'un tenant
     */
    public function clearCache(int $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . $tenantId);
    }

    /**
     * Vérifier les modules expirés et les désactiver
     */
    public function checkExpiredModules(): int
    {
        $expiredCount = 0;

        // Modules dont l'essai est expiré
        $expiredTrials = TenantModule::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $tenantModule) {
            $tenantModule->update(['status' => 'expired']);
            $this->clearCache($tenantModule->tenant_id);
            $expiredCount++;

            Log::info("Module trial expiré: tenant {$tenantModule->tenant_id}, module {$tenantModule->module_id}");
        }

        // Modules dont la date de fin est passée
        $expiredModules = TenantModule::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredModules as $tenantModule) {
            $tenantModule->update(['status' => 'expired']);
            $this->clearCache($tenantModule->tenant_id);
            $expiredCount++;

            Log::info("Module expiré: tenant {$tenantModule->tenant_id}, module {$tenantModule->module_id}");
        }

        return $expiredCount;
    }
}
