<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionSchedule;
use App\Models\InspectionIssue;
use App\Models\Patrol;
use App\Models\PatrolSchedule;
use App\Models\PatrolCheckpoint;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InspectionController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Stats for dashboard
        $stats = [
            'inspections_this_month' => Inspection::where('tenant_id', $tenantId)
                ->whereMonth('inspection_date', now()->month)
                ->whereYear('inspection_date', now()->year)
                ->count(),
            'patrols_completed' => Patrol::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->count(),
            'issues_found' => InspectionIssue::whereHas('inspection', fn($q) => $q->where('tenant_id', $tenantId))
                ->where('status', '!=', 'resolved')
                ->count(),
            'scheduled_today' => InspectionSchedule::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereDate('next_due_date', now()->toDateString())
                ->count(),
        ];

        // Recent inspections (last 5)
        $recentInspections = Inspection::with(['site', 'inspector'])
            ->where('tenant_id', $tenantId)
            ->latest('inspection_date')
            ->limit(5)
            ->get();

        // Recent patrols (last 5)
        $recentPatrols = Patrol::with(['site', 'conductor'])
            ->where('tenant_id', $tenantId)
            ->latest('started_at')
            ->limit(5)
            ->get()
            ->map(function ($patrol) {
                $patrol->user = $patrol->conductor;
                $patrol->checkpoints_scanned = $patrol->checkpoints()->whereNotNull('scanned_at')->count();
                $patrol->total_checkpoints = $patrol->checkpoints()->count();
                return $patrol;
            });

        // Upcoming scheduled inspections
        $upcomingSchedules = InspectionSchedule::with('site')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereDate('next_due_date', '>=', now()->toDateString())
            ->orderBy('next_due_date')
            ->limit(5)
            ->get()
            ->map(function ($schedule) {
                $schedule->next_run_at = $schedule->next_due_date;
                return $schedule;
            });

        return Inertia::render('Tenant/Inspections/Index', [
            'stats' => $stats,
            'recentInspections' => $recentInspections,
            'recentPatrols' => $recentPatrols,
            'upcomingSchedules' => $upcomingSchedules,
        ]);
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $boxes = \App\Models\Box::where('tenant_id', $tenantId)
            ->get(['id', 'site_id', 'number', 'name', 'length', 'width'])
            ->map(function ($box) {
                $box->code = $box->number;
                $box->size_m2 = round($box->length * $box->width, 2);
                return $box;
            });

        return Inertia::render('Tenant/Inspections/Create', [
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'boxes' => $boxes,
            'contracts' => \App\Models\Contract::where('tenant_id', $tenantId)
                ->with(['customer' => fn($q) => $q->select('id', 'first_name', 'last_name')])
                ->get(['id', 'contract_number', 'box_id', 'customer_id'])
                ->map(function ($contract) {
                    if ($contract->customer) {
                        $contract->customer->full_name = $contract->customer->first_name . ' ' . $contract->customer->last_name;
                    }
                    return $contract;
                }),
            'inspectors' => \App\Models\User::where('tenant_id', $tenantId)->get(['id', 'name']),
            'templates' => [], // No template model yet
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'schedule_id' => 'nullable|exists:inspection_schedules,id',
            'type' => 'required|string',
            'inspection_date' => 'nullable|date',
            'checklist_template' => 'nullable|array',
            'findings' => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $inspection = Inspection::create([
            'tenant_id' => $tenantId,
            'site_id' => $validated['site_id'],
            'schedule_id' => $validated['schedule_id'] ?? null,
            'inspector_id' => Auth::id(),
            'type' => $validated['type'],
            'inspection_date' => $validated['inspection_date'] ?? now()->toDateString(),
            'status' => 'scheduled',
            'checklist_results' => $validated['checklist_template'] ?? null,
            'findings' => $validated['findings'] ?? null,
        ]);

        return redirect()->route('tenant.inspections.show', $inspection)
            ->with('success', 'Inspection créée.');
    }

    public function show(Inspection $inspection)
    {
        $this->authorize('view', $inspection);

        $inspection->load(['site', 'inspector', 'schedule', 'issues']);

        return Inertia::render('Tenant/Inspections/Show', [
            'inspection' => $inspection,
        ]);
    }

    public function start(Inspection $inspection)
    {
        $this->authorize('update', $inspection);

        $inspection->update([
            'status' => 'in_progress',
        ]);

        return back()->with('success', 'Inspection démarrée.');
    }

    public function complete(Request $request, Inspection $inspection)
    {
        $this->authorize('update', $inspection);

        $validated = $request->validate([
            'checklist_results' => 'required|array',
            'result' => 'required|in:pass,pass_with_issues,fail',
            'findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'photos' => 'nullable|array',
            'follow_up_date' => 'nullable|date',
        ]);

        $inspection->update([
            'status' => 'completed',
            'checklist_results' => $validated['checklist_results'],
            'result' => $validated['result'],
            'findings' => $validated['findings'] ?? $inspection->findings,
            'recommendations' => $validated['recommendations'] ?? null,
            'photos' => $validated['photos'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        // Mettre à jour le planning si lié
        if ($inspection->schedule) {
            $inspection->schedule->update([
                'last_completed_at' => now(),
                'next_due_date' => $this->calculateNextDueDate($inspection->schedule),
            ]);
        }

        return redirect()->route('tenant.inspections.show', $inspection)
            ->with('success', 'Inspection terminée.');
    }

    public function addIssue(Request $request, Inspection $inspection)
    {
        $this->authorize('update', $inspection);

        $validated = $request->validate([
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'photos' => 'nullable|array',
        ]);

        InspectionIssue::create([
            'inspection_id' => $inspection->id,
            'location' => $validated['location'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'photos' => $validated['photos'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('success', 'Problème signalé.');
    }

    public function resolveIssue(Request $request, InspectionIssue $issue)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $issue->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_notes' => $validated['resolution_notes'],
        ]);

        return back()->with('success', 'Problème résolu.');
    }

    // Plannings d'inspection
    public function schedules()
    {
        $tenantId = Auth::user()->tenant_id;

        $schedules = InspectionSchedule::where('tenant_id', $tenantId)
            ->with(['site', 'assignee'])
            ->withCount('inspections')
            ->get();

        return Inertia::render('Tenant/Inspections/Schedules', [
            'schedules' => $schedules,
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,annually',
            'checklist_template' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $tenantId = Auth::user()->tenant_id;

        InspectionSchedule::create([
            'tenant_id' => $tenantId,
            'site_id' => $validated['site_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'checklist_template' => $validated['checklist_template'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'next_due_date' => now(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Planning créé.');
    }

    // Rondes de sécurité
    public function patrols(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Patrol::with(['site', 'conductor', 'schedule'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $patrols = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Inspections/Patrols', [
            'patrols' => $patrols,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'filters' => $request->only(['site_id', 'status']),
        ]);
    }

    public function startPatrol(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'schedule_id' => 'nullable|exists:patrol_schedules,id',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $patrol = Patrol::create([
            'tenant_id' => $tenantId,
            'site_id' => $validated['site_id'],
            'schedule_id' => $validated['schedule_id'] ?? null,
            'conducted_by' => Auth::id(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Créer les checkpoints si schedule
        if ($patrol->schedule && $patrol->schedule->checkpoints) {
            foreach ($patrol->schedule->checkpoints as $index => $checkpoint) {
                PatrolCheckpoint::create([
                    'patrol_id' => $patrol->id,
                    'checkpoint_name' => $checkpoint['name'],
                    'checkpoint_order' => $index + 1,
                    'qr_code' => $checkpoint['qr_code'] ?? null,
                    'status' => 'pending',
                ]);
            }
        }

        return redirect()->route('tenant.inspections.patrol.show', $patrol)
            ->with('success', 'Ronde démarrée.');
    }

    public function showPatrol(Patrol $patrol)
    {
        $this->authorize('view', $patrol);

        $patrol->load(['site', 'conductor', 'checkpoints']);

        return Inertia::render('Tenant/Inspections/PatrolShow', [
            'patrol' => $patrol,
        ]);
    }

    public function scanCheckpoint(Request $request, PatrolCheckpoint $checkpoint)
    {
        $validated = $request->validate([
            'status' => 'required|in:ok,issue_found',
            'notes' => 'nullable|string',
            'photo_path' => 'nullable|string',
            'issues_found' => 'nullable|array',
        ]);

        $checkpoint->update([
            'scanned_at' => now(),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'photo_path' => $validated['photo_path'] ?? null,
            'issues_found' => $validated['issues_found'] ?? null,
        ]);

        return back()->with('success', 'Point de contrôle validé.');
    }

    public function completePatrol(Request $request, Patrol $patrol)
    {
        $this->authorize('update', $patrol);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $patrol->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $validated['notes'] ?? $patrol->notes,
        ]);

        return redirect()->route('tenant.inspections.patrols')
            ->with('success', 'Ronde terminée.');
    }

    protected function getInspectionTypes(): array
    {
        return [
            ['value' => 'safety', 'label' => 'Sécurité'],
            ['value' => 'cleanliness', 'label' => 'Propreté'],
            ['value' => 'equipment', 'label' => 'Équipement'],
            ['value' => 'fire_safety', 'label' => 'Sécurité incendie'],
            ['value' => 'access_control', 'label' => 'Contrôle d\'accès'],
            ['value' => 'general', 'label' => 'Général'],
        ];
    }

    protected function calculateNextDueDate(InspectionSchedule $schedule): \Carbon\Carbon
    {
        return match ($schedule->frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'annually' => now()->addYear(),
            default => now()->addMonth(),
        };
    }
}
