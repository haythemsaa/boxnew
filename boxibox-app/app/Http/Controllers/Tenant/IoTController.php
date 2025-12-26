<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\IotSensor;
use App\Models\IotAlert;
use App\Models\SmartLock;
use App\Models\AccessLog;
use App\Services\IoTService;
use App\Services\SmartLockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class IoTController extends Controller
{
    protected IoTService $iotService;
    protected SmartLockService $smartLockService;

    public function __construct(IoTService $iotService, SmartLockService $smartLockService)
    {
        $this->iotService = $iotService;
        $this->smartLockService = $smartLockService;
    }

    /**
     * Display IoT Dashboard
     */
    public function dashboard(Request $request)
    {
        $tenant = $request->user()->tenant;
        $siteId = $request->input('site_id');

        // Get sites for selector
        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        // Use first site if none selected
        if (!$siteId && $sites->isNotEmpty()) {
            $siteId = $sites->first()->id;
        }

        $site = $siteId ? Site::find($siteId) : null;

        // Get sensors for site
        $sensors = collect();
        if ($site) {
            try {
                $sensors = IotSensor::where('site_id', $siteId)
                    ->with(['sensorType', 'box'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn($sensor) => [
                        'id' => $sensor->id,
                        'name' => $sensor->name,
                        'type' => $sensor->sensorType->name ?? 'Unknown',
                        'type_slug' => $sensor->sensorType->slug ?? 'unknown',
                        'unit' => $sensor->sensorType->unit ?? '',
                        'icon' => $sensor->sensorType->icon ?? 'cpu-chip',
                        'status' => $sensor->status,
                        'battery_level' => $sensor->battery_level,
                        'last_value' => $sensor->last_value,
                        'last_reading_at' => $sensor->last_reading_at?->toIso8601String(),
                        'box' => $sensor->box ? [
                            'id' => $sensor->box->id,
                            'code' => $sensor->box->number,
                            'display_name' => $sensor->box->display_name ?? $sensor->box->number,
                        ] : null,
                        'alerts_enabled' => $sensor->alert_enabled ?? false,
                        'alert_min' => $sensor->min_threshold,
                        'alert_max' => $sensor->max_threshold,
                    ]);
            } catch (\Exception $e) {
                $sensors = collect();
            }
        }

        // Get smart locks for site (simplified query)
        $smartLocks = collect();
        if ($site) {
            try {
                $smartLocks = SmartLock::where('tenant_id', $tenant->id)
                    ->with(['box'])
                    ->get()
                    ->map(fn($lock) => [
                        'id' => $lock->id,
                        'name' => $lock->device_name ?? 'Serrure ' . ($lock->box?->number ?? $lock->id),
                        'serial_number' => $lock->device_id,
                        'status' => $lock->status,
                        'is_locked' => true,
                        'battery_level' => $lock->battery_level,
                        'last_unlocked_at' => null,
                        'last_locked_at' => null,
                        'provider' => $lock->provider ?? 'Generic',
                        'box' => $lock->box ? [
                            'id' => $lock->box->id,
                            'code' => $lock->box->number,
                            'display_name' => $lock->box->display_name ?? $lock->box->number,
                        ] : null,
                    ]);
            } catch (\Exception $e) {
                $smartLocks = collect();
            }
        }

        // Get active alerts
        $alerts = collect();
        if ($site) {
            try {
                $alerts = IotAlert::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->with(['sensor', 'box'])
                    ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(fn($alert) => [
                        'id' => $alert->id,
                        'title' => $alert->title,
                        'message' => $alert->message,
                        'severity' => $alert->severity,
                        'alert_type' => $alert->alert_type,
                        'status' => $alert->status,
                        'trigger_value' => $alert->trigger_value,
                        'threshold_value' => $alert->threshold_value,
                        'created_at' => $alert->created_at->toIso8601String(),
                        'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
                        'sensor' => $alert->sensor ? [
                            'id' => $alert->sensor->id,
                            'name' => $alert->sensor->name,
                        ] : null,
                        'box' => $alert->box ? [
                            'id' => $alert->box->id,
                            'code' => $alert->box->number,
                        ] : null,
                    ]);
            } catch (\Exception $e) {
                $alerts = collect();
            }
        }

        // Stats summary
        $stats = [
            'total_sensors' => $sensors->count(),
            'online_sensors' => $sensors->where('status', 'online')->count(),
            'offline_sensors' => $sensors->where('status', 'offline')->count(),
            'error_sensors' => $sensors->where('status', 'alert')->count(),
            'total_locks' => $smartLocks->count(),
            'locked_count' => $smartLocks->where('is_locked', true)->count(),
            'unlocked_count' => $smartLocks->where('is_locked', false)->count(),
            'low_battery_count' => $smartLocks->filter(fn($l) => ($l['battery_level'] ?? 100) <= 20)->count()
                + $sensors->filter(fn($s) => ($s['battery_level'] ?? 100) <= 20)->count(),
            'active_alerts' => $alerts->count(),
            'critical_alerts' => $alerts->where('severity', 'critical')->count(),
        ];

        // Recent activity (empty for now - needs AccessLog model)
        $recentActivity = collect();

        return Inertia::render('Tenant/IoT/Dashboard', [
            'sites' => $sites,
            'currentSiteId' => $siteId,
            'site' => $site ? [
                'id' => $site->id,
                'name' => $site->name,
                'code' => $site->code,
            ] : null,
            'sensors' => $sensors,
            'smartLocks' => $smartLocks,
            'alerts' => $alerts,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Get dashboard data via API (for auto-refresh)
     */
    public function dashboardApi(Request $request, int $siteId)
    {
        $tenant = $request->user()->tenant;

        // Verify site belongs to tenant
        $site = Site::where('id', $siteId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return response()->json(
            $this->iotService->getDashboardData($siteId)
        );
    }

    /**
     * Unlock a smart lock
     */
    public function unlockLock(Request $request, SmartLock $lock)
    {
        // Verify lock belongs to tenant
        $tenant = $request->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $result = $this->smartLockService->unlock($lock, $request->user()->id);

        if ($result['success']) {
            return back()->with('success', 'Serrure déverrouillée avec succès');
        }

        return back()->with('error', $result['error'] ?? 'Échec du déverrouillage');
    }

    /**
     * Lock a smart lock
     */
    public function lockLock(Request $request, SmartLock $lock)
    {
        $tenant = $request->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $result = $this->smartLockService->lock($lock);

        if ($result['success']) {
            return back()->with('success', 'Serrure verrouillée avec succès');
        }

        return back()->with('error', $result['error'] ?? 'Échec du verrouillage');
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(Request $request, IotAlert $alert)
    {
        $tenant = $request->user()->tenant;
        if ($alert->tenant_id !== $tenant->id) {
            abort(403);
        }

        $this->iotService->acknowledgeAlert($alert, $request->user()->id);

        return back()->with('success', 'Alerte acquittée');
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(Request $request, IotAlert $alert)
    {
        $tenant = $request->user()->tenant;
        if ($alert->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->iotService->resolveAlert($alert, $request->user()->id, $validated['notes'] ?? null);

        return back()->with('success', 'Alerte résolue');
    }

    /**
     * Get sensor details and history
     */
    public function sensorDetails(Request $request, IotSensor $sensor)
    {
        $tenant = $request->user()->tenant;
        if ($sensor->site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $period = $request->input('period', '24h');
        $from = match ($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };

        $aggregation = $period === '30d' ? 'daily' : ($period === '7d' ? 'hourly' : 'raw');

        $history = $this->iotService->getSensorHistory($sensor, $from, now(), $aggregation);

        $sensorData = [
            'id' => $sensor->id,
            'name' => $sensor->name,
            'type' => $sensor->sensorType->name ?? 'Unknown',
            'unit' => $sensor->sensorType->unit ?? '',
            'status' => $sensor->status,
            'battery_level' => $sensor->battery_level,
            'last_value' => $sensor->last_value,
            'last_reading_at' => $sensor->last_reading_at?->toIso8601String(),
            'alert_min' => $sensor->alert_min,
            'alert_max' => $sensor->alert_max,
            'alerts_enabled' => $sensor->alerts_enabled,
            'box' => $sensor->box ? [
                'id' => $sensor->box->id,
                'code' => $sensor->box->number,
                'display_name' => $sensor->box->display_name,
            ] : null,
        ];

        return Inertia::render('Tenant/IoT/SensorDetails', [
            'sensor' => $sensorData,
            'history' => $history->map(fn($r) => [
                'value' => $r->value ?? $r->avg_value,
                'min' => $r->min_value ?? null,
                'max' => $r->max_value ?? null,
                'timestamp' => ($r->recorded_at ?? $r->period_start)->toIso8601String(),
                'is_anomaly' => $r->is_anomaly ?? false,
            ]),
            'period' => $period,
        ]);
    }

    /**
     * Update sensor settings
     */
    public function updateSensor(Request $request, IotSensor $sensor)
    {
        $tenant = $request->user()->tenant;
        if ($sensor->site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'alerts_enabled' => 'sometimes|boolean',
            'alert_min' => 'nullable|numeric',
            'alert_max' => 'nullable|numeric',
        ]);

        $sensor->update($validated);

        return back()->with('success', 'Capteur mis à jour');
    }

    /**
     * Get alerts list
     */
    public function alerts(Request $request)
    {
        $tenant = $request->user()->tenant;
        $siteId = $request->input('site_id');
        $status = $request->input('status', 'active');
        $severity = $request->input('severity');

        $alerts = IotAlert::where('tenant_id', $tenant->id)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->when($severity, fn($q) => $q->where('severity', $severity))
            ->with(['sensor', 'box', 'site'])
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/IoT/Alerts', [
            'alerts' => $alerts,
            'sites' => $sites,
            'filters' => [
                'site_id' => $siteId,
                'status' => $status,
                'severity' => $severity,
            ],
        ]);
    }

    /**
     * Get access logs
     */
    public function accessLogs(Request $request)
    {
        $tenant = $request->user()->tenant;
        $siteId = $request->input('site_id');
        $status = $request->input('status');
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subWeek();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now();

        $logs = AccessLog::where('tenant_id', $tenant->id)
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('box', fn($b) => $b->where('site_id', $siteId));
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->whereBetween('accessed_at', [$from, $to])
            ->with(['box', 'smartLock', 'customer'])
            ->orderBy('accessed_at', 'desc')
            ->paginate(100);

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/IoT/AccessLogs', [
            'logs' => $logs,
            'sites' => $sites,
            'filters' => [
                'site_id' => $siteId,
                'status' => $status,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * Generate insurance report
     */
    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $tenant = $request->user()->tenant;
        $site = Site::where('id', $validated['site_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $report = $this->iotService->generateInsuranceReport(
            $site->id,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end'])
        );

        return back()->with('success', 'Rapport généré avec succès');
    }

    /**
     * List insurance reports
     */
    public function reports(Request $request)
    {
        $tenant = $request->user()->tenant;

        $reports = \App\Models\IotInsuranceReport::where('tenant_id', $tenant->id)
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/IoT/Reports', [
            'reports' => $reports,
            'sites' => $sites,
        ]);
    }

    /**
     * List all smart locks
     */
    public function locks(Request $request)
    {
        $tenant = $request->user()->tenant;
        $siteId = $request->input('site_id');

        $locks = SmartLock::where('tenant_id', $tenant->id)
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('box', fn($b) => $b->where('site_id', $siteId));
            })
            ->with(['box', 'box.site'])
            ->orderBy('device_name')
            ->paginate(50);

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/IoT/Locks/Index', [
            'locks' => $locks,
            'sites' => $sites,
            'filters' => ['site_id' => $siteId],
        ]);
    }

    /**
     * Show form to create a new smart lock
     */
    public function createLock(Request $request)
    {
        $tenant = $request->user()->tenant;

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $boxes = \App\Models\Box::whereIn('site_id', $sites->pluck('id'))
            ->whereDoesntHave('smartLock')
            ->select('id', 'site_id', 'number', 'name')
            ->orderBy('number')
            ->get()
            ->map(fn($box) => [
                'id' => $box->id,
                'site_id' => $box->site_id,
                'box_number' => $box->number,
                'display_name' => $box->name ?? $box->number,
            ]);

        $providers = [
            'noke' => 'Noke',
            'salto' => 'Salto KS',
            'kisi' => 'Kisi',
            'pti' => 'PTI',
            'generic' => 'Generique',
        ];

        return Inertia::render('Tenant/IoT/Locks/Create', [
            'sites' => $sites,
            'boxes' => $boxes,
            'providers' => $providers,
        ]);
    }

    /**
     * Store a new smart lock
     */
    public function storeLock(Request $request)
    {
        $validated = $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'provider' => 'required|in:noke,salto,kisi,pti,generic',
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:100',
        ]);

        $tenant = $request->user()->tenant;

        // Verify box belongs to tenant
        $box = \App\Models\Box::findOrFail($validated['box_id']);
        $site = Site::findOrFail($box->site_id);
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        SmartLock::create([
            'tenant_id' => $tenant->id,
            'box_id' => $validated['box_id'],
            'provider' => $validated['provider'],
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'] ?? 'Serrure ' . $box->box_number,
            'status' => 'active',
            'battery_level' => 100,
            'last_seen_at' => now(),
        ]);

        return redirect()->route('tenant.iot.locks.index')
            ->with('success', 'Serrure ajoutee avec succes');
    }

    /**
     * Show smart lock details
     */
    public function showLock(SmartLock $lock)
    {
        $tenant = request()->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $lock->load('box', 'box.site');

        // Get access logs for this lock
        $accessLogs = AccessLog::where('smart_lock_id', $lock->id)
            ->with('customer')
            ->orderBy('accessed_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'status' => $log->status,
                'accessed_at' => $log->accessed_at?->toIso8601String(),
                'customer' => $log->customer ? [
                    'id' => $log->customer->id,
                    'name' => $log->customer->full_name,
                ] : null,
            ]);

        $providers = [
            'noke' => 'Noke',
            'salto' => 'Salto KS',
            'kisi' => 'Kisi',
            'pti' => 'PTI',
            'generic' => 'Generique',
        ];

        return Inertia::render('Tenant/IoT/Locks/Show', [
            'lock' => $lock,
            'accessLogs' => $accessLogs,
            'providers' => $providers,
        ]);
    }

    /**
     * Show form to edit a smart lock
     */
    public function editLock(SmartLock $lock)
    {
        $tenant = request()->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $lock->load('box', 'box.site');

        $providers = [
            'noke' => 'Noke',
            'salto' => 'Salto KS',
            'kisi' => 'Kisi',
            'pti' => 'PTI',
            'generic' => 'Generique',
        ];

        return Inertia::render('Tenant/IoT/Locks/Edit', [
            'lock' => $lock,
            'providers' => $providers,
        ]);
    }

    /**
     * Update a smart lock
     */
    public function updateLock(Request $request, SmartLock $lock)
    {
        $tenant = $request->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'provider' => 'required|in:noke,salto,kisi,pti,generic',
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:100',
            'status' => 'in:active,inactive,offline',
        ]);

        $lock->update($validated);

        return redirect()->route('tenant.iot.locks.index')
            ->with('success', 'Serrure mise a jour');
    }

    /**
     * Delete a smart lock
     */
    public function destroyLock(SmartLock $lock)
    {
        $tenant = request()->user()->tenant;
        if ($lock->tenant_id !== $tenant->id) {
            abort(403);
        }

        $lock->delete();

        return redirect()->route('tenant.iot.locks.index')
            ->with('success', 'Serrure supprimee');
    }
}
