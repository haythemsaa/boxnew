<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CRMService;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function __construct(
        protected CRMService $crmService
    ) {}

    /**
     * Display leads list
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $leads = Lead::where('tenant_id', $tenantId)
            ->with(['site', 'assignedTo'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('source'), fn($q, $source) => $q->where('source', $source))
            ->when($request->input('hot'), fn($q) => $q->where('score', '>=', 70))
            ->latest()
            ->paginate(20);

        $analytics = $this->crmService->getLeadAnalytics($tenantId, now()->subMonth(), now());

        return Inertia::render('Tenant/CRM/Leads/Index', [
            'leads' => $leads,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        return Inertia::render('Tenant/CRM/Leads/Create', [
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'users' => User::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    /**
     * Show lead details
     */
    public function show(Request $request, Lead $lead)
    {
        $this->authorize('view_leads');

        $lead->load(['site', 'assignedTo']);

        return Inertia::render('Tenant/CRM/Leads/Show', [
            'lead' => $lead,
            'activities' => [], // Could be lead activities/history
            'users' => User::where('tenant_id', $request->user()->tenant_id)->get(['id', 'name']),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Lead $lead)
    {
        $this->authorize('edit_leads');

        $tenantId = $request->user()->tenant_id;

        return Inertia::render('Tenant/CRM/Leads/Edit', [
            'lead' => $lead,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'users' => User::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    /**
     * Store new lead
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|in:website,phone,referral,walk-in,google_ads,facebook',
            'box_type_interest' => 'nullable|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'move_in_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $lead = $this->crmService->createLead($validated);

        return redirect()->back()->with('success', 'Lead created successfully!');
    }

    /**
     * Update lead
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorize('edit_leads');

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:new,contacted,qualified,converted,lost',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $lead->update($validated);
        $lead->updateScore();

        return redirect()->back()->with('success', 'Lead updated successfully!');
    }

    /**
     * Convert lead to customer
     */
    public function convertToCustomer(Request $request, Lead $lead)
    {
        $this->authorize('edit_leads');

        $validated = $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
        ]);

        $customer = $this->crmService->convertLeadToCustomer($lead, $validated);

        return redirect()->route('tenant.customers.show', $customer)
            ->with('success', 'Lead converted to customer successfully!');
    }

    /**
     * Get churn risk customers
     */
    public function churnRisk(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $atRisk = $this->crmService->detectChurnRisk($tenantId);

        return Inertia::render('Tenant/CRM/ChurnRisk', [
            'at_risk' => $atRisk,
        ]);
    }
}
