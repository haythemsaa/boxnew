<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\ReviewRequestSettings;
use App\Models\ReviewOptOut;
use App\Services\ReviewRequestService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewRequestController extends Controller
{
    public function __construct(
        protected ReviewRequestService $reviewService
    ) {}

    /**
     * Display review requests dashboard
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = ReviewRequest::where('tenant_id', $tenantId)
            ->with(['site', 'customer', 'contract'])
            ->orderBy('created_at', 'desc');

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->get('site_id')) {
            $query->where('site_id', $request->get('site_id'));
        }

        $requests = $query->paginate(20);
        $stats = $this->reviewService->getStats($tenantId, $request->get('site_id'));

        return Inertia::render('Tenant/Reviews/Index', [
            'requests' => $requests,
            'stats' => $stats,
            'filters' => $request->only(['status', 'site_id']),
        ]);
    }

    /**
     * Show review request details
     */
    public function show(ReviewRequest $reviewRequest)
    {
        $this->authorize('view', $reviewRequest);

        $reviewRequest->load(['site', 'customer', 'contract', 'booking']);

        return Inertia::render('Tenant/Reviews/Show', [
            'request' => $reviewRequest,
        ]);
    }

    /**
     * Manually create a review request
     */
    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'contract_id' => ['nullable', 'exists:contracts,id', new \App\Rules\SameTenantResource(\App\Models\Contract::class, $tenantId)],
            'customer_email' => 'required_without:customer_id|email',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'delay_days' => 'integer|min:0|max:30',
        ]);

        // Get customer data
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['customer_email'] = $customer->email;
            $validated['customer_name'] = $customer->full_name;
            $validated['customer_phone'] = $customer->phone;
        }

        // Check opt-out
        if (ReviewOptOut::isOptedOut($tenantId, $validated['customer_email'])) {
            return back()->withErrors(['error' => 'Ce client s\'est désinscrit des demandes d\'avis.']);
        }

        $reviewRequest = ReviewRequest::create([
            'tenant_id' => $tenantId,
            'site_id' => $validated['site_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'contract_id' => $validated['contract_id'] ?? null,
            'customer_email' => $validated['customer_email'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'trigger' => 'manual',
            'delay_days' => $validated['delay_days'] ?? 0,
            'scheduled_at' => now()->addDays($validated['delay_days'] ?? 0),
            'status' => 'pending',
            'channel' => 'email',
            'max_attempts' => 3,
        ]);

        return redirect()->route('tenant.reviews.show', $reviewRequest)
            ->with('success', 'Demande d\'avis créée.');
    }

    /**
     * Send review request immediately
     */
    public function send(ReviewRequest $reviewRequest)
    {
        $this->authorize('update', $reviewRequest);

        if (!in_array($reviewRequest->status, ['pending', 'failed'])) {
            return back()->withErrors(['error' => 'Cette demande ne peut pas être envoyée.']);
        }

        try {
            $this->reviewService->sendRequest($reviewRequest);

            return back()->with('success', 'Demande d\'avis envoyée.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel review request
     */
    public function cancel(ReviewRequest $reviewRequest)
    {
        $this->authorize('update', $reviewRequest);

        if ($reviewRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'Seules les demandes en attente peuvent être annulées.']);
        }

        $reviewRequest->update(['status' => 'skipped']);

        return back()->with('success', 'Demande annulée.');
    }

    /**
     * Bulk send review requests
     */
    public function bulkSend(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => ['exists:review_requests,id', new \App\Rules\SameTenantResource(\App\Models\ReviewRequest::class, $tenantId)],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $sent = 0;
        $failed = 0;

        foreach ($validated['ids'] as $id) {
            $reviewRequest = ReviewRequest::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->first();

            if (!$reviewRequest) {
                continue;
            }

            try {
                $this->reviewService->sendRequest($reviewRequest);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return back()->with('success', "{$sent} demandes envoyées, {$failed} échecs.");
    }

    /**
     * Review request settings page
     */
    public function settings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $siteId = $request->get('site_id');

        $settings = ReviewRequestSettings::getForSite($tenantId, $siteId)
            ?? new ReviewRequestSettings(['tenant_id' => $tenantId, 'site_id' => $siteId]);

        return Inertia::render('Tenant/Reviews/Settings', [
            'settings' => $settings,
            'site_id' => $siteId,
        ]);
    }

    /**
     * Update review request settings
     */
    public function updateSettings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'is_enabled' => 'boolean',
            'trigger_on_move_in' => 'boolean',
            'move_in_delay_days' => 'integer|min:1|max:30',
            'trigger_on_renewal' => 'boolean',
            'renewal_delay_days' => 'integer|min:1|max:30',
            'trigger_on_support_resolved' => 'boolean',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'google_place_id' => 'nullable|string|max:255',
            'google_review_url' => 'nullable|url|max:500',
            'trustpilot_url' => 'nullable|url|max:500',
            'facebook_page_url' => 'nullable|url|max:500',
            'primary_platform' => 'in:google,trustpilot,facebook',
            'email_subject' => 'nullable|string|max:255',
            'email_template' => 'nullable|string|max:10000',
            'sms_template' => 'nullable|string|max:500',
            'max_requests_per_customer' => 'integer|min:1|max:10',
            'min_days_between_requests' => 'integer|min:30|max:365',
            'skip_if_negative_interaction' => 'boolean',
            'offer_incentive' => 'boolean',
            'incentive_type' => 'nullable|string|max:50',
            'incentive_value' => 'nullable|numeric|min:0',
            'incentive_description' => 'nullable|string|max:500',
        ]);

        $tenantId = auth()->user()->tenant_id;

        ReviewRequestSettings::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'site_id' => $validated['site_id'] ?? null,
            ],
            $validated
        );

        return back()->with('success', 'Paramètres mis à jour.');
    }

    /**
     * View opt-out list
     */
    public function optOuts()
    {
        $tenantId = auth()->user()->tenant_id;

        $optOuts = ReviewOptOut::where('tenant_id', $tenantId)
            ->with('customer')
            ->orderBy('opted_out_at', 'desc')
            ->paginate(50);

        return Inertia::render('Tenant/Reviews/OptOuts', [
            'optOuts' => $optOuts,
        ]);
    }

    /**
     * Remove from opt-out list (allow reviews again)
     */
    public function removeOptOut(ReviewOptOut $optOut)
    {
        $this->authorize('delete', $optOut);

        $optOut->delete();

        return back()->with('success', 'Client retiré de la liste de désinscription.');
    }
}
