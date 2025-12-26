<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\GoogleReserveService;
use App\Models\GoogleReserveBooking;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoogleReserveController extends Controller
{
    public function __construct(
        protected GoogleReserveService $service
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        $bookings = $this->service->getBookingsForCalendar($tenantId, $siteId, $startDate, $endDate);
        $statistics = $this->service->getStatistics($tenantId, $siteId, $startDate, $endDate);
        $upcomingBookings = $this->service->getUpcomingBookings($tenantId, $siteId, 5);
        $todayBookings = $this->service->getTodayBookings($tenantId, $siteId);

        return Inertia::render('Tenant/GoogleReserve/Index', [
            'sites' => $sites,
            'selectedSiteId' => $siteId,
            'bookings' => $bookings,
            'statistics' => $statistics,
            'upcomingBookings' => $upcomingBookings,
            'todayBookings' => $todayBookings,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function show(GoogleReserveBooking $booking): Response
    {
        $booking->load(['site', 'customer', 'box', 'convertedContract']);

        return Inertia::render('Tenant/GoogleReserve/Show', [
            'booking' => $booking,
        ]);
    }

    public function settings(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);
        $settings = $this->service->getSettings($tenantId, $siteId);

        return Inertia::render('Tenant/GoogleReserve/Settings', [
            'sites' => $sites,
            'selectedSiteId' => $siteId,
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'is_enabled' => 'boolean',
            'merchant_id' => 'nullable|string',
            'place_id' => 'nullable|string',
            'available_days' => 'array',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'slot_duration_minutes' => 'integer|min:15|max:120',
            'max_advance_days' => 'integer|min:1|max:90',
            'min_advance_hours' => 'integer|min:0|max:72',
            'auto_confirm' => 'boolean',
            'require_deposit' => 'boolean',
            'deposit_amount' => 'nullable|numeric|min:0',
            'notify_on_booking' => 'boolean',
            'send_customer_confirmation' => 'boolean',
            'send_reminder' => 'boolean',
            'reminder_hours_before' => 'integer|min:1|max:72',
        ]);

        $tenantId = $request->user()->tenant_id;
        $this->service->updateSettings($tenantId, $validated['site_id'] ?? null, $validated);

        return back()->with('success', 'Paramètres Google Reserve mis à jour');
    }

    public function confirm(GoogleReserveBooking $booking)
    {
        $booking->confirm();
        return back()->with('success', 'Réservation confirmée');
    }

    public function cancel(Request $request, GoogleReserveBooking $booking)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->service->cancelBooking($booking, $validated['reason'] ?? null, false);
        return back()->with('success', 'Réservation annulée');
    }

    public function complete(GoogleReserveBooking $booking)
    {
        $booking->markCompleted();
        return back()->with('success', 'Visite marquée comme terminée');
    }

    public function convert(Request $request, GoogleReserveBooking $booking)
    {
        $internalBooking = $this->service->convertToBooking($booking);

        return redirect()->route('tenant.bookings.show', $internalBooking)
            ->with('success', 'Réservation convertie en booking interne');
    }

    public function generateSlots(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $count = $this->service->generateSlots(
            $tenantId,
            $validated['site_id'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        return back()->with('success', "{$count} créneaux générés");
    }
}
