<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Site;
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
     * Show waitlist signup form
     */
    public function create(Request $request, $siteSlug)
    {
        $site = Site::where('slug', $siteSlug)
            ->orWhere('id', $siteSlug)
            ->firstOrFail();

        $settings = WaitlistSettings::getForSite($site->tenant_id, $site->id);

        if (!$settings?->is_enabled) {
            abort(404, 'Liste d\'attente non disponible.');
        }

        // Get box if specified
        $box = null;
        if ($request->has('box_id')) {
            $box = Box::where('id', $request->get('box_id'))
                ->where('site_id', $site->id)
                ->first();
        }

        // Get available size ranges
        $sizeRanges = Box::where('site_id', $site->id)
            ->selectRaw('MIN(length * width) as min_size, MAX(length * width) as max_size')
            ->first();

        return Inertia::render('Public/Waitlist/Create', [
            'site' => $site->only(['id', 'name', 'slug', 'address', 'city']),
            'box' => $box?->only(['id', 'name', 'size_m2', 'current_price']),
            'sizeRanges' => $sizeRanges,
            'tenant' => $site->tenant->only(['id', 'name']),
        ]);
    }

    /**
     * Store waitlist entry
     */
    public function store(Request $request, $siteSlug)
    {
        $site = Site::where('slug', $siteSlug)
            ->orWhere('id', $siteSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_email' => 'required|email',
            'customer_first_name' => 'required|string|max:255',
            'customer_last_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'box_id' => 'nullable|exists:boxes,id',
            'min_size' => 'nullable|numeric|min:0',
            'max_size' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'needs_climate_control' => 'boolean',
            'needs_ground_floor' => 'boolean',
            'needs_drive_up' => 'boolean',
            'desired_start_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['tenant_id'] = $site->tenant_id;
        $validated['site_id'] = $site->id;
        $validated['source'] = 'website';

        try {
            $entry = $this->waitlistService->addToWaitlist($validated);

            return redirect()->route('waitlist.confirmation', ['uuid' => $entry->uuid])
                ->with('success', 'Vous êtes inscrit sur la liste d\'attente !');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show confirmation page
     */
    public function confirmation($uuid)
    {
        $entry = WaitlistEntry::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Public/Waitlist/Confirmation', [
            'entry' => $entry->only([
                'uuid',
                'customer_first_name',
                'customer_email',
                'position',
                'status',
            ]),
            'site' => $entry->site->only(['name', 'address', 'city']),
        ]);
    }

    /**
     * Track notification click
     */
    public function trackClick(Request $request, $uuid)
    {
        $entry = WaitlistEntry::where('uuid', $uuid)->firstOrFail();

        // Update notification if exists
        $notification = $entry->notifications()
            ->where('status', 'sent')
            ->latest()
            ->first();

        if ($notification) {
            $notification->markClicked();
        }

        // Redirect to booking with waitlist reference
        $box = $notification?->box ?? $entry->box;

        if ($box) {
            return redirect()->route('booking.checkout', [
                'site' => $entry->site->slug ?? $entry->site_id,
                'box' => $box->id,
                'waitlist' => $entry->uuid,
            ]);
        }

        return redirect()->route('booking.index', [
            'site' => $entry->site->slug ?? $entry->site_id,
        ]);
    }

    /**
     * Unsubscribe from waitlist
     */
    public function unsubscribe($uuid)
    {
        $entry = WaitlistEntry::where('uuid', $uuid)->first();

        if ($entry && in_array($entry->status, ['active', 'notified'])) {
            $entry->cancel();
        }

        return Inertia::render('Public/Waitlist/Unsubscribed', [
            'success' => (bool) $entry,
        ]);
    }

    /**
     * Check waitlist status
     */
    public function status($uuid)
    {
        $entry = WaitlistEntry::where('uuid', $uuid)
            ->with(['site', 'box'])
            ->firstOrFail();

        return Inertia::render('Public/Waitlist/Status', [
            'entry' => [
                'uuid' => $entry->uuid,
                'status' => $entry->status,
                'status_label' => $entry->status_label,
                'position' => $entry->position,
                'customer_first_name' => $entry->customer_first_name,
                'created_at' => $entry->created_at->format('d/m/Y'),
                'notified_at' => $entry->notified_at?->format('d/m/Y H:i'),
                'expires_at' => $entry->expires_at?->format('d/m/Y H:i'),
            ],
            'site' => $entry->site->only(['name', 'address', 'city']),
            'box' => $entry->box?->only(['name', 'size_m2', 'current_price']),
        ]);
    }
}
