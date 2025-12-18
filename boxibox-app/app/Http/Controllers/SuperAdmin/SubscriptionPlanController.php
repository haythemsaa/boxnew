<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionPlanController extends Controller
{
    /**
     * Liste des plans d'abonnement
     */
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                $plan->tenant_count = Tenant::where('current_plan_id', $plan->id)->count();
                $plan->included_modules_list = $plan->included_modules
                    ? Module::whereIn('id', $plan->included_modules)->get(['id', 'code', 'name', 'icon'])
                    : collect();
                return $plan;
            });

        $modules = Module::orderBy('category')->orderBy('sort_order')->get();

        $stats = [
            'total_plans' => $plans->count(),
            'active_plans' => $plans->where('is_active', true)->count(),
            'total_subscriptions' => Tenant::whereNotNull('current_plan_id')->count(),
        ];

        return Inertia::render('SuperAdmin/Plans/Index', [
            'plans' => $plans,
            'modules' => $modules,
            'stats' => $stats,
        ]);
    }

    /**
     * Créer un nouveau plan
     */
    public function create()
    {
        $modules = Module::orderBy('category')->orderBy('sort_order')->get();

        return Inertia::render('SuperAdmin/Plans/Create', [
            'modules' => $modules,
        ]);
    }

    /**
     * Enregistrer un nouveau plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:subscription_plans,code',
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
            'support_level' => 'required|string|in:none,email,priority,dedicated',
            'included_modules' => 'nullable|array',
            'features' => 'nullable|array',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            // Quotas Email & SMS
            'emails_per_month' => 'nullable|integer|min:0',
            'sms_per_month' => 'nullable|integer|min:0',
            'email_tracking_enabled' => 'boolean',
            'custom_email_provider_allowed' => 'boolean',
            'custom_sms_provider_allowed' => 'boolean',
            'api_access' => 'boolean',
            'whitelabel' => 'boolean',
        ]);

        SubscriptionPlan::create($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan créé avec succès');
    }

    /**
     * Modifier un plan
     */
    public function edit(SubscriptionPlan $plan)
    {
        $modules = Module::orderBy('category')->orderBy('sort_order')->get();

        $plan->included_modules_list = $plan->included_modules
            ? Module::whereIn('id', $plan->included_modules)->get(['id', 'code', 'name'])
            : collect();

        return Inertia::render('SuperAdmin/Plans/Edit', [
            'plan' => $plan,
            'modules' => $modules,
        ]);
    }

    /**
     * Mettre à jour un plan
     */
    public function update(Request $request, SubscriptionPlan $plan)
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
            'support_level' => 'required|string|in:none,email,priority,dedicated',
            'included_modules' => 'nullable|array',
            'features' => 'nullable|array',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            // Quotas Email & SMS
            'emails_per_month' => 'nullable|integer|min:0',
            'sms_per_month' => 'nullable|integer|min:0',
            'email_tracking_enabled' => 'boolean',
            'custom_email_provider_allowed' => 'boolean',
            'custom_sms_provider_allowed' => 'boolean',
            'api_access' => 'boolean',
            'whitelabel' => 'boolean',
        ]);

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan mis à jour avec succès');
    }

    /**
     * Supprimer un plan
     */
    public function destroy(SubscriptionPlan $plan)
    {
        // Vérifier qu'aucun tenant n'utilise ce plan
        $tenantCount = Tenant::where('current_plan_id', $plan->id)->count();

        if ($tenantCount > 0) {
            return back()->with('error', "Impossible de supprimer ce plan. {$tenantCount} tenant(s) l'utilisent encore.");
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan supprimé avec succès');
    }

    /**
     * Dupliquer un plan
     */
    public function duplicate(SubscriptionPlan $plan)
    {
        $newPlan = $plan->replicate();
        $newPlan->code = $plan->code . '_copy_' . time();
        $newPlan->name = $plan->name . ' (Copie)';
        $newPlan->is_active = false;
        $newPlan->is_popular = false;
        $newPlan->save();

        return redirect()->route('superadmin.plans.edit', $newPlan)
            ->with('success', 'Plan dupliqué avec succès. Modifiez-le avant de l\'activer.');
    }

    /**
     * Activer/désactiver un plan
     */
    public function toggle(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "Plan {$status} avec succès");
    }
}
