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
        $this->authorize('viewAny', Lead::class);
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
        $this->authorize('create', Lead::class);
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
        $this->authorize('view', $lead);

        $lead->load(['site', 'assignedTo']);

        // Get recent interactions
        $recentInteractions = $lead->interactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'type' => $i->type,
                'formatted_type' => $i->formatted_type,
                'icon' => $i->icon,
                'color' => $i->color,
                'subject' => $i->subject,
                'content' => $i->content,
                'outcome' => $i->outcome,
                'created_at' => $i->created_at->format('d/m/Y H:i'),
                'created_at_human' => $i->created_at->diffForHumans(),
                'user' => $i->user ? ['id' => $i->user->id, 'name' => $i->user->name] : null,
            ]);

        return Inertia::render('Tenant/CRM/Leads/Show', [
            'lead' => $lead,
            'recentInteractions' => $recentInteractions,
            'interactionCount' => $lead->interactions()->count(),
            'users' => User::where('tenant_id', $request->user()->tenant_id)->get(['id', 'name']),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

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
        $this->authorize('create', Lead::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'type' => 'nullable|in:individual,company',
            'source' => 'nullable|in:website,phone,referral,walk-in,google_ads,facebook',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class)],
            'assigned_to' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser()],
            'box_type_interest' => 'nullable|string',
            'budget_min' => 'nullable|numeric',
            'budget_max' => 'nullable|numeric',
            'move_in_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        // Set defaults if not provided
        $validated['type'] = $validated['type'] ?? 'individual';
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $lead = $this->crmService->createLead($validated);

        return redirect()->route('tenant.crm.leads.index')->with('success', 'Lead créé avec succès!');
    }

    /**
     * Update lead
     */
    public function update(Request $request, Lead $lead)
    {

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:new,contacted,qualified,converted,lost',
            'assigned_to' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser()],
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

    /**
     * Kanban board view for leads pipeline
     */
    public function kanban(Request $request)
    {
        $this->authorize('viewAny', Lead::class);
        $tenantId = $request->user()->tenant_id;

        // Group leads by status
        $statuses = ['new', 'contacted', 'qualified', 'negotiation', 'converted', 'lost'];

        $columns = [];
        foreach ($statuses as $status) {
            $leads = Lead::where('tenant_id', $tenantId)
                ->where('status', $status)
                ->with(['site', 'assignedTo'])
                ->orderByDesc('priority')
                ->orderByDesc('score')
                ->get()
                ->map(fn($lead) => [
                    'id' => $lead->id,
                    'name' => "{$lead->first_name} {$lead->last_name}",
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company' => $lead->company,
                    'source' => $lead->source,
                    'score' => $lead->score,
                    'priority' => $lead->priority,
                    'site' => $lead->site?->name,
                    'assigned_to' => $lead->assignedTo?->name,
                    'budget_min' => $lead->budget_min,
                    'budget_max' => $lead->budget_max,
                    'move_in_date' => $lead->move_in_date?->format('d/m/Y'),
                    'last_contacted_at' => $lead->last_contacted_at?->format('d/m'),
                    'created_at' => $lead->created_at->format('d/m/Y'),
                    'days_in_status' => $lead->updated_at->diffInDays(now()),
                ]);

            $columns[$status] = [
                'name' => $this->getStatusLabel($status),
                'color' => $this->getStatusColor($status),
                'leads' => $leads,
                'count' => $leads->count(),
                'value' => $leads->sum(fn($l) => $l['budget_max'] ?? $l['budget_min'] ?? 0),
            ];
        }

        // Pipeline metrics
        $metrics = [
            'total_leads' => Lead::where('tenant_id', $tenantId)->count(),
            'new_this_week' => Lead::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'conversion_rate' => $this->calculateConversionRate($tenantId),
            'avg_time_to_convert' => $this->calculateAvgTimeToConvert($tenantId),
            'pipeline_value' => Lead::where('tenant_id', $tenantId)
                ->whereNotIn('status', ['converted', 'lost'])
                ->sum('budget_max'),
        ];

        return Inertia::render('Tenant/CRM/Leads/Kanban', [
            'columns' => $columns,
            'metrics' => $metrics,
            'users' => User::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    /**
     * Update lead status (for drag & drop)
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,negotiation,converted,lost',
            'position' => 'nullable|integer',
        ]);

        $lead->update([
            'status' => $validated['status'],
        ]);

        // Update score based on status change
        $lead->updateScore();

        return response()->json([
            'success' => true,
            'lead' => $lead->fresh(),
        ]);
    }

    /**
     * Bulk update leads
     */
    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', Lead::class);

        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => ['exists:leads,id', new \App\Rules\SameTenantResource(\App\Models\Lead::class)],
            'status' => 'nullable|in:new,contacted,qualified,negotiation,converted,lost',
            'assigned_to' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser()],
            'priority' => 'nullable|in:cold,lukewarm,warm,hot,very_hot',
        ]);

        $leads = Lead::whereIn('id', $validated['lead_ids'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->get();

        foreach ($leads as $lead) {
            $updateData = [];
            if (isset($validated['status'])) $updateData['status'] = $validated['status'];
            if (isset($validated['assigned_to'])) $updateData['assigned_to'] = $validated['assigned_to'];
            if (isset($validated['priority'])) $updateData['priority'] = $validated['priority'];

            if (!empty($updateData)) {
                $lead->update($updateData);
            }
        }

        return response()->json(['success' => true, 'updated' => $leads->count()]);
    }

    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Nouveau',
            'contacted' => 'Contacté',
            'qualified' => 'Qualifié',
            'negotiation' => 'Négociation',
            'converted' => 'Converti',
            'lost' => 'Perdu',
            default => ucfirst($status),
        };
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'new' => 'blue',
            'contacted' => 'cyan',
            'qualified' => 'amber',
            'negotiation' => 'purple',
            'converted' => 'emerald',
            'lost' => 'gray',
            default => 'gray',
        };
    }

    protected function calculateConversionRate(int $tenantId): float
    {
        $total = Lead::where('tenant_id', $tenantId)
            ->whereIn('status', ['converted', 'lost'])
            ->count();

        if ($total === 0) return 0;

        $converted = Lead::where('tenant_id', $tenantId)
            ->where('status', 'converted')
            ->count();

        return round($converted / $total * 100, 1);
    }

    protected function calculateAvgTimeToConvert(int $tenantId): ?int
    {
        $converted = Lead::where('tenant_id', $tenantId)
            ->where('status', 'converted')
            ->whereNotNull('converted_at')
            ->get();

        if ($converted->isEmpty()) return null;

        $totalDays = $converted->sum(function ($lead) {
            return $lead->created_at->diffInDays($lead->converted_at);
        });

        return round($totalDays / $converted->count());
    }
}
