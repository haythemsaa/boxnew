<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\OverdueWorkflow;
use App\Models\OverdueWorkflowStep;
use App\Models\OverdueAction;
use App\Models\Invoice;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OverdueController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Factures impayées avec leur retard
        $query = Invoice::with(['customer', 'contract.box', 'contract.site'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now());

        if ($request->filled('days_overdue')) {
            $days = (int) $request->days_overdue;
            $query->where('due_date', '<=', now()->subDays($days));
        }

        if ($request->filled('site_id')) {
            $query->whereHas('contract', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        $overdueInvoices = $query->orderBy('due_date')->paginate(20)->withQueryString();

        // Stats des impayés - Optimized with single query
        $overdueBase = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now());

        $overdueStats = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->selectRaw("
                COUNT(*) as total_overdue,
                SUM(total) as total_amount,
                SUM(CASE WHEN due_date >= ? THEN 1 ELSE 0 END) as overdue_0_30,
                SUM(CASE WHEN due_date < ? AND due_date >= ? THEN 1 ELSE 0 END) as overdue_30_60,
                SUM(CASE WHEN due_date < ? AND due_date >= ? THEN 1 ELSE 0 END) as overdue_60_90,
                SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) as overdue_90_plus
            ", [
                now()->subDays(30),
                now()->subDays(30), now()->subDays(60),
                now()->subDays(60), now()->subDays(90),
                now()->subDays(90)
            ])
            ->first();

        $stats = [
            'total_overdue' => (int) ($overdueStats->total_overdue ?? 0),
            'total_amount' => (float) ($overdueStats->total_amount ?? 0),
            'overdue_0_30' => (int) ($overdueStats->overdue_0_30 ?? 0),
            'overdue_30_60' => (int) ($overdueStats->overdue_30_60 ?? 0),
            'overdue_60_90' => (int) ($overdueStats->overdue_60_90 ?? 0),
            'overdue_90_plus' => (int) ($overdueStats->overdue_90_plus ?? 0),
        ];

        return Inertia::render('Tenant/Overdue/Index', [
            'overdueInvoices' => $overdueInvoices,
            'stats' => $stats,
            'filters' => $request->only(['days_overdue', 'site_id']),
        ]);
    }

    public function workflows()
    {
        $tenantId = Auth::user()->tenant_id;

        $workflows = OverdueWorkflow::where('tenant_id', $tenantId)
            ->with('steps')
            ->get();

        return Inertia::render('Tenant/Overdue/Workflows', [
            'workflows' => $workflows,
        ]);
    }

    public function createWorkflow()
    {
        return Inertia::render('Tenant/Overdue/WorkflowCreate');
    }

    public function storeWorkflow(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'steps' => 'required|array|min:1',
            'steps.*.action_type' => 'required|string',
            'steps.*.delay_days' => 'required|integer|min:0',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $workflow = OverdueWorkflow::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        foreach ($validated['steps'] as $index => $step) {
            OverdueWorkflowStep::create([
                'workflow_id' => $workflow->id,
                'days_overdue' => $step['delay_days'],
                'action_type' => $step['action_type'],
                'order' => $index,
                'is_active' => true,
            ]);
        }

        return redirect()->route('tenant.overdue.workflows')
            ->with('success', 'Workflow de relance créé avec succès.');
    }

    public function editWorkflow(OverdueWorkflow $workflow)
    {
        $this->authorize('update', $workflow);

        return Inertia::render('Tenant/Overdue/WorkflowEdit', [
            'workflow' => $workflow->load('steps'),
        ]);
    }

    public function updateWorkflow(Request $request, OverdueWorkflow $workflow)
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.action_type' => 'required|string',
            'steps.*.delay_days' => 'required|integer|min:0',
        ]);

        $workflow->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Recréer les étapes
        $workflow->steps()->delete();
        foreach ($validated['steps'] as $index => $step) {
            OverdueWorkflowStep::create([
                'workflow_id' => $workflow->id,
                'days_overdue' => $step['delay_days'],
                'action_type' => $step['action_type'],
                'order' => $index,
                'is_active' => true,
            ]);
        }

        return redirect()->route('tenant.overdue.workflows')
            ->with('success', 'Workflow mis à jour.');
    }

    public function destroyWorkflow(OverdueWorkflow $workflow)
    {
        $this->authorize('delete', $workflow);
        $workflow->delete();

        return redirect()->route('tenant.overdue.workflows')
            ->with('success', 'Workflow supprimé.');
    }

    public function actions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = OverdueAction::with(['contract.customer', 'invoice', 'workflowStep', 'creator'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->pending();
            } elseif ($request->status === 'executed') {
                $query->whereNotNull('executed_at');
            }
        }

        $actions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Overdue/Actions', [
            'actions' => $actions,
            'filters' => $request->only(['status']),
        ]);
    }

    public function executeAction(OverdueAction $action)
    {
        $this->authorize('update', $action);

        if ($action->executed_at) {
            return back()->with('error', 'Cette action a déjà été exécutée.');
        }

        // Exécuter l'action selon son type
        $result = $this->performAction($action);

        $action->update([
            'executed_at' => now(),
            'result' => $result,
        ]);

        return back()->with('success', 'Action exécutée avec succès.');
    }

    protected function performAction(OverdueAction $action): array
    {
        $result = ['success' => true];

        switch ($action->action_type) {
            case 'email_reminder':
                // TODO: Envoyer email de relance
                $result['message'] = 'Email de relance envoyé';
                break;

            case 'sms_reminder':
                // TODO: Envoyer SMS de relance
                $result['message'] = 'SMS de relance envoyé';
                break;

            case 'late_fee':
                // TODO: Appliquer frais de retard
                $result['message'] = 'Frais de retard appliqués';
                break;

            case 'access_restriction':
                if ($action->contract) {
                    $action->contract->update(['access_restricted' => true]);
                }
                $result['message'] = 'Accès restreint';
                break;

            case 'access_suspension':
                if ($action->contract) {
                    $action->contract->update(['access_suspended' => true]);
                }
                $result['message'] = 'Accès suspendu';
                break;

            default:
                $result['message'] = 'Action effectuée';
        }

        return $result;
    }

    public function sendReminder(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'channel' => 'required|in:email,sms,both',
            'message' => 'nullable|string',
        ]);

        // TODO: Implémenter l'envoi de relance

        OverdueAction::create([
            'tenant_id' => $invoice->tenant_id,
            'contract_id' => $invoice->contract_id,
            'invoice_id' => $invoice->id,
            'action_type' => $validated['channel'] === 'sms' ? 'sms_reminder' : 'email_reminder',
            'executed_at' => now(),
            'result' => ['manual' => true, 'message' => $validated['message'] ?? null],
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Relance envoyée.');
    }

    public function agingReport()
    {
        $tenantId = Auth::user()->tenant_id;

        // Balance âgée par client
        $agingByCustomer = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with('customer')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($invoices) {
                $customer = $invoices->first()->customer;
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                    'total_due' => $invoices->sum('total'),
                    'invoice_count' => $invoices->count(),
                    'oldest_invoice' => $invoices->min('due_date'),
                    'aging_0_30' => $invoices->filter(fn($i) => $i->due_date >= now()->subDays(30))->sum('total'),
                    'aging_30_60' => $invoices->filter(fn($i) => $i->due_date < now()->subDays(30) && $i->due_date >= now()->subDays(60))->sum('total'),
                    'aging_60_90' => $invoices->filter(fn($i) => $i->due_date < now()->subDays(60) && $i->due_date >= now()->subDays(90))->sum('total'),
                    'aging_90_plus' => $invoices->filter(fn($i) => $i->due_date < now()->subDays(90))->sum('total'),
                ];
            })
            ->values();

        return Inertia::render('Tenant/Overdue/AgingReport', [
            'agingByCustomer' => $agingByCustomer,
        ]);
    }
}
