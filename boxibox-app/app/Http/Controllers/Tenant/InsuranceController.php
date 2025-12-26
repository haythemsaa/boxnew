<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\InsurancePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InsuranceController extends Controller
{
    /**
     * List insurance providers
     */
    public function providers(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $providers = InsuranceProvider::where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')
              ->orWhere('tenant_id', $tenantId);
        })
        ->where('is_active', true)
        ->with(['plans' => function ($q) use ($tenantId) {
            $q->where(function ($query) use ($tenantId) {
                $query->whereNull('tenant_id')
                      ->orWhere('tenant_id', $tenantId);
            })->where('is_active', true);
        }])
        ->get();

        return response()->json([
            'providers' => $providers,
        ]);
    }

    /**
     * Compare insurance plans
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'plan_ids' => 'required|array|min:2|max:4',
            'plan_ids.*' => 'exists:insurance_plans,id',
        ]);

        $plans = InsurancePlan::whereIn('id', $validated['plan_ids'])
            ->with('provider')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'provider' => $plan->provider?->name,
                    'price_monthly' => $plan->price_monthly,
                    'coverage_amount' => $plan->coverage_amount,
                    'deductible' => $plan->deductible,
                    'features' => $plan->features ?? [],
                    'coverage_details' => $plan->coverage_details ?? [],
                ];
            });

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Subscribe customer to insurance plan
     */
    public function subscribe(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'contract_id' => ['required', 'exists:contracts,id', new \App\Rules\SameTenantResource(\App\Models\Contract::class, $tenantId)],
            'plan_id' => ['required', 'exists:insurance_plans,id', new \App\Rules\SameTenantResource(\App\Models\InsurancePlan::class, $tenantId)],
            'start_date' => 'required|date',
        ]);

        $plan = InsurancePlan::findOrFail($validated['plan_id']);

        $policy = \App\Models\InsurancePolicy::create([
            'tenant_id' => $request->user()->tenant_id,
            'customer_id' => $validated['customer_id'],
            'contract_id' => $validated['contract_id'],
            'insurance_plan_id' => $plan->id,
            'policy_number' => 'POL-' . strtoupper(uniqid()),
            'start_date' => $validated['start_date'],
            'premium_amount' => $plan->price_monthly,
            'coverage_amount' => $plan->coverage_amount,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'policy' => $policy,
        ]);
    }
}
