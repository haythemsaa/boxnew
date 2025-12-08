<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\DemoHistory;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ModuleController extends Controller
{
    protected ModuleService $moduleService;

    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    /**
     * Liste des modules
     */
    public function index()
    {
        $modules = Module::orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($module) {
                $module->active_count = TenantModule::where('module_id', $module->id)
                    ->whereIn('status', ['active', 'trial'])
                    ->count();
                return $module;
            });

        $categories = $modules->groupBy('category')->map(function ($items, $category) {
            return [
                'name' => $this->getCategoryLabel($category),
                'count' => $items->count(),
                'modules' => $items,
            ];
        });

        $stats = [
            'total_modules' => $modules->count(),
            'core_modules' => $modules->where('is_core', true)->count(),
            'active_subscriptions' => TenantModule::whereIn('status', ['active'])->count(),
            'trial_subscriptions' => TenantModule::where('status', 'trial')->count(),
        ];

        return Inertia::render('SuperAdmin/Modules/Index', [
            'modules' => $modules,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Créer un module
     */
    public function create()
    {
        return Inertia::render('SuperAdmin/Modules/Create', [
            'categories' => $this->getCategories(),
            'existingModules' => Module::select('id', 'code', 'name')->get(),
        ]);
    }

    /**
     * Enregistrer un module
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:modules,code',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'color' => 'required|string',
            'category' => 'required|string',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'routes' => 'nullable|array',
            'dependencies' => 'nullable|array',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ]);

        Module::create($validated);

        return redirect()->route('superadmin.modules.index')
            ->with('success', 'Module créé avec succès');
    }

    /**
     * Modifier un module
     */
    public function edit(Module $module)
    {
        return Inertia::render('SuperAdmin/Modules/Edit', [
            'module' => $module,
            'categories' => $this->getCategories(),
            'existingModules' => Module::where('id', '!=', $module->id)
                ->select('id', 'code', 'name')
                ->get(),
        ]);
    }

    /**
     * Mettre à jour un module
     */
    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'color' => 'required|string',
            'category' => 'required|string',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'routes' => 'nullable|array',
            'dependencies' => 'nullable|array',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $module->update($validated);

        return redirect()->route('superadmin.modules.index')
            ->with('success', 'Module mis à jour avec succès');
    }

    /**
     * Liste des plans
     */
    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get()->map(function ($plan) {
            $plan->tenant_count = Tenant::where('current_plan_id', $plan->id)->count();
            $plan->included_modules_list = $plan->included_modules
                ? Module::whereIn('id', $plan->included_modules)->get(['id', 'code', 'name'])
                : collect();
            return $plan;
        });

        $allModules = Module::orderBy('category')->orderBy('sort_order')->get();

        return Inertia::render('SuperAdmin/Modules/Plans', [
            'plans' => $plans,
            'modules' => $allModules,
        ]);
    }

    /**
     * Mettre à jour un plan
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'badge_color' => 'required|string',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'yearly_discount' => 'required|numeric|min:0|max:100',
            'max_sites' => 'nullable|integer|min:1',
            'max_boxes' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_customers' => 'nullable|integer|min:1',
            'includes_support' => 'boolean',
            'support_level' => 'required|string',
            'included_modules' => 'nullable|array',
            'features' => 'nullable|array',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return back()->with('success', 'Plan mis à jour avec succès');
    }

    /**
     * Gestion des abonnements par tenant
     */
    public function tenantModules(Tenant $tenant)
    {
        $modules = $this->moduleService->getModulesDetailsForTenant($tenant->id);
        $tenantModules = TenantModule::where('tenant_id', $tenant->id)
            ->with('module')
            ->get();
        $plans = SubscriptionPlan::active()->get();

        return Inertia::render('SuperAdmin/Modules/TenantModules', [
            'tenant' => $tenant,
            'modules' => $modules,
            'tenantModules' => $tenantModules,
            'plans' => $plans,
            'currentPlan' => $tenant->current_plan_id
                ? SubscriptionPlan::find($tenant->current_plan_id)
                : null,
        ]);
    }

    /**
     * Activer un module pour un tenant
     */
    public function enableModuleForTenant(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'is_trial' => 'boolean',
            'trial_days' => 'nullable|integer|min:1|max:90',
            'billing_cycle' => 'required|in:monthly,yearly',
            'custom_price' => 'nullable|numeric|min:0',
        ]);

        $module = Module::find($validated['module_id']);
        $options = [
            'billing_cycle' => $validated['billing_cycle'],
        ];

        if ($validated['is_trial'] ?? false) {
            $options['is_trial'] = true;
            $options['trial_ends_at'] = now()->addDays($validated['trial_days'] ?? 14);
            $options['is_demo'] = true;
        }

        if (isset($validated['custom_price'])) {
            $options['price'] = $validated['custom_price'];
        }

        $this->moduleService->enableModule($tenant->id, $module->id, $options);

        // Enregistrer dans l'historique des démos si c'est un trial
        if ($validated['is_trial'] ?? false) {
            DemoHistory::create([
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'demo_type' => 'module',
                'started_at' => now(),
                'ends_at' => $options['trial_ends_at'],
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', "Module {$module->name} activé pour {$tenant->name}");
    }

    /**
     * Désactiver un module pour un tenant
     */
    public function disableModuleForTenant(Tenant $tenant, Module $module)
    {
        $this->moduleService->disableModule($tenant->id, $module->id);

        return back()->with('success', "Module {$module->name} désactivé pour {$tenant->name}");
    }

    /**
     * Changer le plan d'un tenant
     */
    public function changeTenantPlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $this->moduleService->changePlan(
            $tenant->id,
            $validated['plan_id'],
            $validated['billing_cycle']
        );

        $plan = SubscriptionPlan::find($validated['plan_id']);

        return back()->with('success', "Plan changé en {$plan->name} pour {$tenant->name}");
    }

    /**
     * Démarrer une démo complète pour un tenant
     */
    public function startFullDemo(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:90',
            'notes' => 'nullable|string',
        ]);

        $this->moduleService->startFullAppDemo($tenant->id, $validated['days']);

        DemoHistory::create([
            'tenant_id' => $tenant->id,
            'demo_type' => 'full_app',
            'started_at' => now(),
            'ends_at' => now()->addDays($validated['days']),
            'status' => 'active',
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Démo complète activée pour {$tenant->name} ({$validated['days']} jours)");
    }

    /**
     * Historique des démos
     */
    public function demoHistory()
    {
        $demos = DemoHistory::with(['tenant', 'module', 'plan', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $stats = [
            'active_demos' => DemoHistory::where('status', 'active')->count(),
            'converted' => DemoHistory::where('status', 'converted')->count(),
            'expired' => DemoHistory::where('status', 'expired')->count(),
            'conversion_rate' => $this->calculateConversionRate(),
        ];

        return Inertia::render('SuperAdmin/Modules/DemoHistory', [
            'demos' => $demos,
            'stats' => $stats,
        ]);
    }

    /**
     * Prolonger une démo
     */
    public function extendDemo(Request $request, DemoHistory $demo)
    {
        $validated = $request->validate([
            'additional_days' => 'required|integer|min:1|max:30',
        ]);

        $demo->update([
            'ends_at' => Carbon::parse($demo->ends_at)->addDays($validated['additional_days']),
        ]);

        // Mettre à jour le tenant_module correspondant
        if ($demo->module_id) {
            TenantModule::where('tenant_id', $demo->tenant_id)
                ->where('module_id', $demo->module_id)
                ->update([
                    'trial_ends_at' => $demo->ends_at,
                ]);
        }

        return back()->with('success', 'Démo prolongée avec succès');
    }

    /**
     * Marquer une démo comme convertie
     */
    public function convertDemo(DemoHistory $demo)
    {
        $demo->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        // Activer le module de façon permanente
        if ($demo->module_id) {
            TenantModule::where('tenant_id', $demo->tenant_id)
                ->where('module_id', $demo->module_id)
                ->update([
                    'status' => 'active',
                    'is_demo' => false,
                    'trial_ends_at' => null,
                ]);
        }

        return back()->with('success', 'Démo convertie en abonnement');
    }

    /**
     * Annuler une démo
     */
    public function cancelDemo(DemoHistory $demo)
    {
        $demo->update(['status' => 'cancelled']);

        // Désactiver le module
        if ($demo->module_id) {
            $this->moduleService->disableModule($demo->tenant_id, $demo->module_id);
        }

        return back()->with('success', 'Démo annulée');
    }

    /**
     * Catégories disponibles
     */
    private function getCategories(): array
    {
        return [
            'core' => 'Modules de base',
            'marketing' => 'Marketing & CRM',
            'operations' => 'Opérations',
            'integrations' => 'Intégrations',
            'analytics' => 'Analytics',
            'premium' => 'Premium',
        ];
    }

    /**
     * Label de catégorie
     */
    private function getCategoryLabel(string $category): string
    {
        return $this->getCategories()[$category] ?? ucfirst($category);
    }

    /**
     * Calculer le taux de conversion
     */
    private function calculateConversionRate(): float
    {
        $total = DemoHistory::whereIn('status', ['converted', 'expired'])->count();
        if ($total === 0) {
            return 0;
        }
        $converted = DemoHistory::where('status', 'converted')->count();
        return round(($converted / $total) * 100, 1);
    }
}
