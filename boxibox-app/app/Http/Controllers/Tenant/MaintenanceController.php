<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketComment;
use App\Models\MaintenanceTicketHistory;
use App\Models\Box;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MaintenanceTicket::with(['site', 'box', 'assignee', 'creator', 'customer'])
            ->where('tenant_id', $tenantId);

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => MaintenanceTicket::where('tenant_id', $tenantId)->count(),
            'open' => MaintenanceTicket::where('tenant_id', $tenantId)->open()->count(),
            'urgent' => MaintenanceTicket::where('tenant_id', $tenantId)->where('priority', 'urgent')->open()->count(),
            'overdue' => MaintenanceTicket::where('tenant_id', $tenantId)->overdue()->count(),
        ];

        return Inertia::render('Tenant/Maintenance/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'types' => ['repair', 'cleaning', 'inspection', 'installation', 'other'],
            'filters' => $request->only(['status', 'priority', 'site_id', 'type', 'search']),
        ]);
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Tenant/Maintenance/Create', [
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'boxes' => Box::where('tenant_id', $tenantId)->get(['id', 'site_id', 'number', 'name']),
            'types' => ['repair', 'cleaning', 'inspection', 'installation', 'other'],
            'users' => User::where('tenant_id', $tenantId)->get(['id', 'name']),
            'customers' => \App\Models\Customer::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'nullable|exists:boxes,id',
            'customer_id' => 'nullable|exists:customers,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:repair,cleaning,inspection,installation,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|integer|min:0',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $validated['tenant_id'] = $tenantId;
        $validated['created_by'] = Auth::id();
        $validated['ticket_number'] = MaintenanceTicket::generateTicketNumber();
        $validated['status'] = 'open';

        $ticket = MaintenanceTicket::create($validated);

        // Historique
        MaintenanceTicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'field_changed' => 'status',
            'old_value' => null,
            'new_value' => 'open',
        ]);

        return redirect()->route('tenant.maintenance.show', $ticket)
            ->with('success', 'Ticket de maintenance créé avec succès.');
    }

    public function show(MaintenanceTicket $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load([
            'site',
            'box',
            'assignee',
            'creator',
            'customer',
            'comments.user',
            'history.user',
        ]);

        return Inertia::render('Tenant/Maintenance/Show', [
            'ticket' => $maintenance,
            'users' => User::where('tenant_id', Auth::user()->tenant_id)->get(['id', 'name']),
        ]);
    }

    public function edit(MaintenanceTicket $maintenance)
    {
        $this->authorize('update', $maintenance);
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Tenant/Maintenance/Edit', [
            'ticket' => $maintenance->load(['site', 'box']),
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
            'boxes' => Box::where('tenant_id', $tenantId)->get(['id', 'site_id', 'number', 'name']),
            'types' => ['repair', 'cleaning', 'inspection', 'installation', 'other'],
            'users' => User::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, MaintenanceTicket $maintenance)
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'nullable|exists:boxes,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:repair,cleaning,inspection,installation,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,pending_parts,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|integer|min:0',
            'actual_hours' => 'nullable|integer|min:0',
            'resolution_notes' => 'nullable|string',
        ]);

        $oldValues = $maintenance->only(['status', 'priority', 'assigned_to']);
        $maintenance->update($validated);

        // Historique des changements
        foreach (['status', 'priority', 'assigned_to'] as $field) {
            if ($oldValues[$field] !== $maintenance->$field) {
                MaintenanceTicketHistory::create([
                    'ticket_id' => $maintenance->id,
                    'user_id' => Auth::id(),
                    'field_changed' => $field,
                    'old_value' => $oldValues[$field],
                    'new_value' => $maintenance->$field,
                ]);
            }
        }

        if ($validated['status'] === 'completed' && !$maintenance->completed_at) {
            $maintenance->update(['completed_at' => now()]);
        }

        return redirect()->route('tenant.maintenance.show', $maintenance)
            ->with('success', 'Ticket mis à jour avec succès.');
    }

    public function destroy(MaintenanceTicket $maintenance)
    {
        $this->authorize('delete', $maintenance);
        $maintenance->delete();

        return redirect()->route('tenant.maintenance.index')
            ->with('success', 'Ticket supprimé avec succès.');
    }

    public function addComment(Request $request, MaintenanceTicket $maintenance)
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array',
        ]);

        $comment = MaintenanceTicketComment::create([
            'ticket_id' => $maintenance->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
            'attachments' => $validated['attachments'] ?? null,
        ]);

        MaintenanceTicketHistory::create([
            'ticket_id' => $maintenance->id,
            'user_id' => Auth::id(),
            'field_changed' => 'comment',
            'old_value' => null,
            'new_value' => 'Commentaire ajouté',
        ]);

        return back()->with('success', 'Commentaire ajouté.');
    }

    public function updateStatus(Request $request, MaintenanceTicket $maintenance)
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending_parts,completed,cancelled',
            'resolution_notes' => 'nullable|required_if:status,completed|string',
        ]);

        $oldStatus = $maintenance->status;
        $maintenance->status = $validated['status'];

        if (isset($validated['resolution_notes'])) {
            $maintenance->resolution_notes = $validated['resolution_notes'];
        }

        if ($validated['status'] === 'completed' && !$maintenance->completed_at) {
            $maintenance->completed_at = now();
        }

        $maintenance->save();

        MaintenanceTicketHistory::create([
            'ticket_id' => $maintenance->id,
            'user_id' => Auth::id(),
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $validated['status'],
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }
}
