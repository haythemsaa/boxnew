<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use App\Models\SmartLock;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccessControlController extends Controller
{
    public function __construct(
        protected AccessControlService $accessControlService
    ) {}

    /**
     * Display access control dashboard
     */
    public function dashboard(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');
        $period = $request->input('period', 'week');

        $analytics = $this->accessControlService->getAccessAnalytics($tenantId, $siteId, $period);
        $locksStatus = $this->accessControlService->getLocksStatus($tenantId, $siteId);
        $suspicious = $this->accessControlService->getSuspiciousActivity($tenantId, 24);

        return Inertia::render('Tenant/AccessControl/Dashboard', [
            'analytics' => $analytics,
            'locks_status' => $locksStatus,
            'suspicious_activity' => $suspicious,
        ]);
    }

    /**
     * List smart locks
     */
    public function locks(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $locksQuery = SmartLock::where('tenant_id', $tenantId)
            ->with(['box', 'box.site'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('low_battery'), fn($q) => $q->where('battery_level', '<=', 20));

        // Get stats
        $totalLocks = SmartLock::where('tenant_id', $tenantId)->count();
        $onlineLocks = SmartLock::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $offlineLocks = SmartLock::where('tenant_id', $tenantId)->where('status', 'offline')->count();
        $lowBatteryLocks = SmartLock::where('tenant_id', $tenantId)->where('battery_level', '<=', 20)->count();

        $locks = $locksQuery->latest()->get()->map(function ($lock) {
            return [
                'id' => $lock->id,
                'box_number' => $lock->box?->number ?? $lock->box?->name ?? 'N/A',
                'site_id' => $lock->box?->site_id,
                'site_name' => $lock->box?->site?->name ?? 'N/A',
                'serial_number' => $lock->serial_number,
                'model' => $lock->model ?? 'Standard',
                'status' => $lock->status,
                'battery_level' => $lock->battery_level ?? 100,
                'signal_strength' => $lock->signal_strength,
                'firmware_version' => $lock->firmware_version ?? '1.0.0',
                'update_available' => $lock->update_available ?? false,
                'last_activity' => $lock->last_access_at?->diffForHumans() ?? null,
                'installed_at' => $lock->created_at?->format('d/m/Y'),
            ];
        });

        $sites = \App\Models\Site::where('tenant_id', $tenantId)->select('id', 'name')->get();

        return Inertia::render('Tenant/AccessControl/Locks', [
            'locks' => $locks,
            'sites' => $sites,
            'stats' => [
                'total' => $totalLocks,
                'online' => $onlineLocks,
                'offline' => $offlineLocks,
                'low_battery' => $lowBatteryLocks,
                'needs_attention' => $offlineLocks + $lowBatteryLocks,
            ],
        ]);
    }

    /**
     * View access logs
     */
    public function logs(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Build query with filters
        $query = AccessLog::where('tenant_id', $tenantId)
            ->with(['box', 'box.site', 'customer', 'smartLock'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('customer', fn($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                          ->orWhereHas('box', fn($q) => $q->where('number', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('site'), fn($q, $siteId) => $q->whereHas('box', fn($q) => $q->where('site_id', $siteId)))
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('method'), fn($q, $method) => $q->where('method', $method))
            ->when($request->input('suspicious_only'), fn($q) => $q->where('is_suspicious', true))
            ->when($request->input('period'), function ($q, $period) {
                $dates = match($period) {
                    'today' => [now()->startOfDay(), now()],
                    'week' => [now()->subDays(7), now()],
                    'month' => [now()->subDays(30), now()],
                    default => null,
                };
                if ($dates) {
                    $q->whereBetween('accessed_at', $dates);
                }
            });

        // Get stats
        $baseQuery = AccessLog::where('tenant_id', $tenantId);
        $totalLogs = (clone $baseQuery)->count();
        $grantedLogs = (clone $baseQuery)->where('status', 'granted')->count();
        $deniedLogs = (clone $baseQuery)->where('status', 'denied')->count();
        $suspiciousLogs = (clone $baseQuery)->where('is_suspicious', true)->count();

        $logs = $query->latest('accessed_at')->paginate(50);

        // Transform logs for frontend
        $logs->through(function ($log) {
            return [
                'id' => $log->id,
                'customer_name' => $log->customer ? $log->customer->first_name . ' ' . $log->customer->last_name : null,
                'customer_email' => $log->customer?->email,
                'box_number' => $log->box?->number ?? $log->box?->name,
                'site_name' => $log->box?->site?->name,
                'method' => $log->method,
                'status' => $log->status,
                'reason' => $log->failure_reason,
                'is_suspicious' => $log->is_suspicious ?? false,
                'created_at' => $log->accessed_at ?? $log->created_at,
            ];
        });

        $sites = \App\Models\Site::where('tenant_id', $tenantId)->select('id', 'name')->get();

        return Inertia::render('Tenant/AccessControl/Logs', [
            'logs' => $logs,
            'sites' => $sites,
            'stats' => [
                'total' => $totalLogs,
                'granted' => $grantedLogs,
                'denied' => $deniedLogs,
                'suspicious' => $suspiciousLogs,
            ],
            'filters' => $request->only(['search', 'site', 'status', 'method', 'period', 'suspicious_only']),
        ]);
    }

    /**
     * Update smart lock status
     */
    public function updateLock(Request $request, SmartLock $lock)
    {
        $this->authorize('update', $lock);

        $validated = $request->validate([
            'status' => 'sometimes|in:active,inactive,offline',
            'device_name' => 'sometimes|string|max:255',
        ]);

        $lock->update($validated);

        return redirect()->back()->with('success', 'Lock updated successfully!');
    }

    /**
     * Perform action on smart lock (unlock/lock)
     */
    public function lockAction(Request $request, SmartLock $lock)
    {
        $this->authorize('update', $lock);

        $validated = $request->validate([
            'action' => 'required|in:lock,unlock',
        ]);

        $action = $validated['action'];

        // In a real implementation, this would call the smart lock API
        // For now, we'll simulate the action
        try {
            if ($action === 'unlock') {
                // Simulate unlock
                $lock->update(['last_access_at' => now()]);
                $message = 'Serrure deverrouillee avec succes';
            } else {
                $message = 'Serrure verrouillee avec succes';
            }

            // Log the action
            \App\Models\AccessLog::create([
                'tenant_id' => $lock->tenant_id,
                'box_id' => $lock->box_id,
                'smart_lock_id' => $lock->id,
                'method' => 'remote',
                'status' => 'granted',
                'accessed_at' => now(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'action: ' . $e->getMessage());
        }
    }

    /**
     * Export access logs to CSV
     */
    public function exportLogs(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $logs = AccessLog::where('tenant_id', $tenantId)
            ->with(['box', 'box.site', 'customer'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('method'), fn($q, $method) => $q->where('method', $method))
            ->when($request->input('period'), function ($q, $period) {
                $dates = match($period) {
                    'today' => [now()->startOfDay(), now()],
                    'week' => [now()->subDays(7), now()],
                    'month' => [now()->subDays(30), now()],
                    default => null,
                };
                if ($dates) {
                    $q->whereBetween('accessed_at', $dates);
                }
            })
            ->latest('accessed_at')
            ->get();

        $filename = 'access_logs_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, ['Date', 'Heure', 'Client', 'Email', 'Box', 'Site', 'Méthode', 'Statut', 'Raison', 'Suspect']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->accessed_at?->format('d/m/Y') ?? '',
                    $log->accessed_at?->format('H:i:s') ?? '',
                    $log->customer ? $log->customer->first_name . ' ' . $log->customer->last_name : 'Inconnu',
                    $log->customer?->email ?? '',
                    $log->box?->number ?? $log->box?->name ?? '',
                    $log->box?->site?->name ?? '',
                    $log->method ?? '',
                    $log->status === 'granted' ? 'Autorisé' : 'Refusé',
                    $log->failure_reason ?? '',
                    $log->is_suspicious ? 'Oui' : 'Non',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
