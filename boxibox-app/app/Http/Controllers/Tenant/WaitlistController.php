<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\WaitlistEntry;
use App\Models\WaitlistSettings;
use App\Services\WaitlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaitlistController extends Controller
{
    public function __construct(
        protected WaitlistService $waitlistService
    ) {}

    /**
     * Display waitlist dashboard
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $siteId = $request->get('site_id');

        $query = WaitlistEntry::where('tenant_id', $tenantId)
            ->with(['site', 'box', 'customer'])
            ->orderBy('created_at', 'desc');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $entries = $query->paginate(20);
        $stats = $this->waitlistService->getStats($tenantId, $siteId);

        return Inertia::render('Tenant/Waitlist/Index', [
            'entries' => $entries,
            'stats' => $stats,
            'filters' => $request->only(['site_id', 'status']),
        ]);
    }

    /**
     * Show waitlist entry details
     */
    public function show(WaitlistEntry $entry)
    {
        $this->authorize('view', $entry);

        $entry->load(['site', 'box', 'customer', 'notifications.box', 'convertedBooking']);

        return Inertia::render('Tenant/Waitlist/Show', [
            'entry' => $entry,
        ]);
    }

    /**
     * Create manual waitlist entry
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'nullable|exists:boxes,id',
            'customer_email' => 'required|email',
            'customer_first_name' => 'required|string|max:255',
            'customer_last_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'min_size' => 'nullable|numeric|min:0',
            'max_size' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'needs_climate_control' => 'boolean',
            'needs_ground_floor' => 'boolean',
            'needs_drive_up' => 'boolean',
            'desired_start_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'integer|min:0|max:100',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['source'] = 'manual';

        try {
            $entry = $this->waitlistService->addToWaitlist($validated);

            return redirect()->route('tenant.waitlist.show', $entry)
                ->with('success', 'Entrée ajoutée à la liste d\'attente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update waitlist entry
     */
    public function update(Request $request, WaitlistEntry $entry)
    {
        $this->authorize('update', $entry);

        $validated = $request->validate([
            'priority' => 'integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'status' => 'in:active,cancelled',
        ]);

        $entry->update($validated);

        return back()->with('success', 'Entrée mise à jour.');
    }

    /**
     * Cancel waitlist entry
     */
    public function cancel(WaitlistEntry $entry)
    {
        $this->authorize('update', $entry);

        $entry->cancel();

        return back()->with('success', 'Entrée annulée.');
    }

    /**
     * Manually notify a waitlist entry
     */
    public function notify(Request $request, WaitlistEntry $entry)
    {
        $this->authorize('update', $entry);

        $validated = $request->validate([
            'box_id' => 'required|exists:boxes,id',
        ]);

        $box = Box::findOrFail($validated['box_id']);

        try {
            $this->waitlistService->notifyEntry($entry, $box);

            return back()->with('success', 'Notification envoyée.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Waitlist settings page
     */
    public function settings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $siteId = $request->get('site_id');

        $settings = WaitlistSettings::getForSite($tenantId, $siteId)
            ?? new WaitlistSettings(['tenant_id' => $tenantId, 'site_id' => $siteId]);

        return Inertia::render('Tenant/Waitlist/Settings', [
            'settings' => $settings,
            'site_id' => $siteId,
        ]);
    }

    /**
     * Update waitlist settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'is_enabled' => 'boolean',
            'max_entries_per_box' => 'integer|min:1|max:100',
            'notification_expiry_hours' => 'integer|min:1|max:168',
            'max_notifications_per_entry' => 'integer|min:1|max:10',
            'auto_notify' => 'boolean',
            'priority_by_date' => 'boolean',
            'notification_email_template' => 'nullable|string|max:10000',
            'notification_sms_template' => 'nullable|string|max:500',
        ]);

        $tenantId = auth()->user()->tenant_id;

        WaitlistSettings::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'site_id' => $validated['site_id'] ?? null,
            ],
            $validated
        );

        return back()->with('success', 'Paramètres mis à jour.');
    }
}
