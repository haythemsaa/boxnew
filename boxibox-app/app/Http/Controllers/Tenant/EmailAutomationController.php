<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\EmailAutomationService;
use App\Models\EmailSequence;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailAutomationController extends Controller
{
    public function __construct(
        protected EmailAutomationService $automationService
    ) {}

    /**
     * Email automation dashboard
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $data = $this->automationService->getDashboardData($tenantId);

        return Inertia::render('Tenant/Marketing/EmailAutomation', [
            'stats' => $data['stats'],
            'sequences' => $data['sequences'],
            'recentActivity' => $data['recent_activity'],
            'performanceChart' => $data['performance_chart'],
            'triggers' => $this->automationService->getAvailableTriggers(),
        ]);
    }

    /**
     * Show sequence builder/editor
     */
    public function create(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $templates = EmailTemplate::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'subject', 'category']);

        return Inertia::render('Tenant/Marketing/EmailAutomationCreate', [
            'triggers' => $this->automationService->getAvailableTriggers(),
            'templates' => $templates,
        ]);
    }

    /**
     * Store new sequence
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger' => 'required|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.template_id' => 'nullable|exists:email_templates,id',
        ]);

        $sequence = $this->automationService->createSequence(
            $request->user()->tenant_id,
            $validated
        );

        return redirect()
            ->route('tenant.marketing.automation.show', $sequence)
            ->with('success', 'Automatisation créée avec succès');
    }

    /**
     * Show sequence details
     */
    public function show(Request $request, EmailSequence $sequence)
    {
        $this->authorize('view', $sequence);

        $analytics = $this->automationService->getSequenceAnalytics($sequence);

        return Inertia::render('Tenant/Marketing/EmailAutomationShow', [
            'sequence' => $this->automationService->getDashboardData($request->user()->tenant_id)['sequences']
                ->firstWhere('id', $sequence->id),
            'analytics' => $analytics,
        ]);
    }

    /**
     * Edit sequence
     */
    public function edit(Request $request, EmailSequence $sequence)
    {
        $this->authorize('update', $sequence);

        $tenantId = $request->user()->tenant_id;
        $templates = EmailTemplate::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'subject', 'category']);

        return Inertia::render('Tenant/Marketing/EmailAutomationEdit', [
            'sequence' => $sequence,
            'triggers' => $this->automationService->getAvailableTriggers(),
            'templates' => $templates,
        ]);
    }

    /**
     * Update sequence
     */
    public function update(Request $request, EmailSequence $sequence)
    {
        $this->authorize('update', $sequence);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger' => 'sometimes|string',
            'is_active' => 'boolean',
            'steps' => 'sometimes|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.delay_minutes' => 'required|integer|min:0',
        ]);

        $this->automationService->updateSequence($sequence, $validated);

        return redirect()
            ->route('tenant.marketing.automation.show', $sequence)
            ->with('success', 'Automatisation mise à jour');
    }

    /**
     * Toggle sequence active status
     */
    public function toggle(EmailSequence $sequence)
    {
        $this->authorize('update', $sequence);

        $sequence->update(['is_active' => !$sequence->is_active]);

        return back()->with('success', $sequence->is_active
            ? 'Automatisation activée'
            : 'Automatisation désactivée');
    }

    /**
     * Delete sequence
     */
    public function destroy(EmailSequence $sequence)
    {
        $this->authorize('delete', $sequence);

        $sequence->enrollments()->delete();
        $sequence->delete();

        return redirect()
            ->route('tenant.marketing.automation.index')
            ->with('success', 'Automatisation supprimée');
    }

    /**
     * Get sequence analytics (AJAX)
     */
    public function analytics(EmailSequence $sequence)
    {
        $this->authorize('view', $sequence);

        return response()->json([
            'success' => true,
            'analytics' => $this->automationService->getSequenceAnalytics($sequence),
        ]);
    }

    /**
     * Manually enroll contacts
     */
    public function enroll(Request $request, EmailSequence $sequence)
    {
        $this->authorize('update', $sequence);

        $validated = $request->validate([
            'customer_ids' => 'array',
            'customer_ids.*' => 'exists:customers,id',
            'lead_ids' => 'array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        $enrolled = 0;

        foreach ($validated['customer_ids'] ?? [] as $customerId) {
            $customer = \App\Models\Customer::find($customerId);
            if ($this->automationService->enroll($sequence, $customer)) {
                $enrolled++;
            }
        }

        foreach ($validated['lead_ids'] ?? [] as $leadId) {
            $lead = \App\Models\Lead::find($leadId);
            if ($this->automationService->enroll($sequence, null, $lead)) {
                $enrolled++;
            }
        }

        return back()->with('success', "{$enrolled} contact(s) inscrit(s) à l'automatisation");
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request, EmailSequence $sequence)
    {
        $this->authorize('update', $sequence);

        $validated = $request->validate([
            'email' => 'required|email',
            'step_index' => 'required|integer|min:0',
        ]);

        $steps = $sequence->steps ?? [];
        $step = $steps[$validated['step_index']] ?? null;

        if (!$step) {
            return back()->withErrors(['step_index' => 'Étape non trouvée']);
        }

        // Send test email
        try {
            \Mail::raw($step['content'], function ($message) use ($validated, $step) {
                $message->to($validated['email'])
                    ->subject('[TEST] ' . $step['subject']);
            });

            return back()->with('success', 'Email de test envoyé à ' . $validated['email']);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Erreur lors de l\'envoi: ' . $e->getMessage()]);
        }
    }
}
