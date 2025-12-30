<?php

namespace App\Http\Controllers\Customer\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InsuranceClaim;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal Insurance Controller
 * Handles insurance-related functionality for customer portal
 */
class PortalInsuranceController extends Controller
{
    /**
     * Get authenticated customer from session
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        $customerId = session('customer_portal_id');

        if (!$customerId) {
            abort(403, 'No customer authenticated. Please log in.');
        }

        return Customer::findOrFail($customerId);
    }

    /**
     * Display insurance overview
     */
    public function index(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Get active contracts with their insurance status
        $contracts = $customer->contracts()
            ->where('status', 'active')
            ->with(['box', 'insurancePolicy.plan'])
            ->get();

        // Get available insurance plans
        $plans = InsurancePlan::where('tenant_id', $customer->tenant_id)
            ->where('is_active', true)
            ->orderBy('coverage_amount')
            ->get();

        // Get insurance claims history
        $claims = InsuranceClaim::whereHas('policy', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->with(['policy.contract.box'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Customer/Portal/Insurance', [
            'customer' => $customer,
            'contracts' => $contracts,
            'plans' => $plans,
            'claims' => $claims,
        ]);
    }

    /**
     * Subscribe to insurance plan
     */
    public function subscribe(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'plan_id' => 'required|exists:insurance_plans,id',
        ]);

        $contract = $customer->contracts()->findOrFail($validated['contract_id']);
        $plan = InsurancePlan::where('tenant_id', $customer->tenant_id)
            ->findOrFail($validated['plan_id']);

        // Check if contract already has insurance
        if ($contract->insurancePolicy && $contract->insurancePolicy->status === 'active') {
            return back()->with('error', 'This contract already has an active insurance policy.');
        }

        try {
            $policy = InsurancePolicy::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'contract_id' => $contract->id,
                'plan_id' => $plan->id,
                'policy_number' => 'INS-' . strtoupper(uniqid()),
                'coverage_amount' => $plan->coverage_amount,
                'monthly_premium' => $plan->monthly_premium,
                'deductible' => $plan->deductible,
                'status' => 'active',
                'start_date' => now(),
            ]);

            Log::info('Insurance policy created via portal', [
                'policy_id' => $policy->id,
                'customer_id' => $customer->id,
                'contract_id' => $contract->id,
            ]);

            return back()->with('success', 'Insurance successfully activated! Policy number: ' . $policy->policy_number);
        } catch (\Exception $e) {
            Log::error('Failed to create insurance policy', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to activate insurance. Please try again.');
        }
    }

    /**
     * Cancel insurance policy
     */
    public function cancel(Request $request, InsurancePolicy $policy)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($policy->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to insurance policy.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $policy->update([
            'status' => 'cancelled',
            'end_date' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        Log::info('Insurance policy cancelled via portal', [
            'policy_id' => $policy->id,
            'customer_id' => $customer->id,
        ]);

        return back()->with('success', 'Insurance policy has been cancelled.');
    }

    /**
     * Submit insurance claim
     */
    public function submitClaim(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'policy_id' => 'required|exists:insurance_policies,id',
            'incident_date' => 'required|date|before_or_equal:today',
            'incident_type' => 'required|string|in:theft,damage,fire,water,other',
            'description' => 'required|string|max:2000',
            'estimated_value' => 'required|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240', // 10MB max per file
        ]);

        $policy = InsurancePolicy::where('customer_id', $customer->id)
            ->findOrFail($validated['policy_id']);

        if ($policy->status !== 'active') {
            return back()->with('error', 'Cannot submit claim for inactive policy.');
        }

        try {
            $claim = InsuranceClaim::create([
                'tenant_id' => $customer->tenant_id,
                'policy_id' => $policy->id,
                'customer_id' => $customer->id,
                'claim_number' => 'CLM-' . strtoupper(uniqid()),
                'incident_date' => $validated['incident_date'],
                'incident_type' => $validated['incident_type'],
                'description' => $validated['description'],
                'estimated_value' => $validated['estimated_value'],
                'status' => 'submitted',
            ]);

            // Handle document uploads if any
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store("claims/{$claim->id}", 'private');
                    $claim->documents()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            Log::info('Insurance claim submitted via portal', [
                'claim_id' => $claim->id,
                'policy_id' => $policy->id,
                'customer_id' => $customer->id,
            ]);

            return back()->with('success', 'Claim submitted successfully. Claim number: ' . $claim->claim_number . '. We will contact you within 48 hours.');
        } catch (\Exception $e) {
            Log::error('Failed to submit insurance claim', [
                'policy_id' => $policy->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to submit claim. Please try again.');
        }
    }
}
