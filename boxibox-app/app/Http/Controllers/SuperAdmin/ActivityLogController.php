<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user:id,name,email', 'tenant:id,name'])
            ->latest();

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        // Stats
        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'errors' => ActivityLog::where('severity', 'error')->whereDate('created_at', '>=', now()->subDays(7))->count(),
            'warnings' => ActivityLog::where('severity', 'warning')->whereDate('created_at', '>=', now()->subDays(7))->count(),
        ];

        // Get unique actions for filter
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();

        // Get tenants for filter
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/ActivityLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'actions' => $actions,
            'tenants' => $tenants,
            'filters' => $request->only(['tenant_id', 'action', 'severity', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load(['user:id,name,email', 'tenant:id,name']);

        return Inertia::render('SuperAdmin/ActivityLogs/Show', [
            'log' => $activityLog,
        ]);
    }

    public function destroy(ActivityLog $activityLog)
    {
        $activityLog->delete();

        return back()->with('success', 'Log supprimé.');
    }

    public function clearOld(Request $request)
    {
        $days = $request->input('days', 90);
        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "{$deleted} logs supprimés (plus de {$days} jours).");
    }
}
