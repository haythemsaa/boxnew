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

        $locks = SmartLock::where('tenant_id', $tenantId)
            ->with(['box', 'box.site'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('low_battery'), fn($q) => $q->where('battery_level', '<=', 20))
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/AccessControl/Locks', [
            'locks' => $locks,
        ]);
    }

    /**
     * View access logs
     */
    public function logs(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $logs = AccessLog::where('tenant_id', $tenantId)
            ->with(['box', 'customer', 'smartLock'])
            ->when($request->input('box_id'), fn($q, $boxId) => $q->where('box_id', $boxId))
            ->when($request->input('customer_id'), fn($q, $customerId) => $q->where('customer_id', $customerId))
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('suspicious'), fn($q) => $q->suspicious())
            ->latest('accessed_at')
            ->paginate(50);

        return Inertia::render('Tenant/AccessControl/Logs', [
            'logs' => $logs,
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
}
