<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Customer;
use App\Models\CustomerAccessCode;
use App\Models\GuestAccessCode;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PortalAccessController extends Controller
{
    /**
     * Get authenticated customer from session
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        $customerId = session('customer_portal_id');

        if ($customerId) {
            return Customer::findOrFail($customerId);
        }

        // Demo fallback
        return Customer::first() ?? abort(403, 'No customer authenticated');
    }

    /**
     * Display access codes for the customer
     */
    public function index()
    {
        $customer = $this->getAuthenticatedCustomer();
        $tenant = $customer->tenant;

        // Get customer's access codes
        $accessCodes = CustomerAccessCode::where('customer_id', $customer->id)
            ->with('site')
            ->get();

        // Get customer's guest codes
        $guestCodes = GuestAccessCode::where('customer_id', $customer->id)
            ->with('site')
            ->orderByDesc('created_at')
            ->get();

        // Check if customer can create guest codes
        $settings = $tenant->self_service_settings ?? [];
        $canCreateGuestCodes = $customer->allow_guest_access
            && ($settings['allow_guest_access'] ?? false)
            && $tenant->self_service_enabled;

        $maxGuests = $settings['max_guests_per_customer'] ?? 2;

        // Get sites where customer has contracts
        $sites = Site::where('tenant_id', $tenant->id)
            ->where('self_service_enabled', true)
            ->whereHas('boxes.contracts', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->where('status', 'active');
            })
            ->get(['id', 'name']);

        // Get access history for the customer
        $accessHistory = AccessLog::where('customer_id', $customer->id)
            ->where('tenant_id', $customer->tenant_id)
            ->with(['box.site'])
            ->orderByDesc('accessed_at')
            ->limit(50)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'site_name' => $log->box?->site?->name ?? $log->gate_name ?? 'Site inconnu',
                'box_name' => $log->box?->name ?? $log->box?->number ?? '-',
                'access_method' => $log->access_method,
                'status' => $log->status,
                'accessed_at' => $log->accessed_at?->format('d/m/Y H:i'),
                'gate_name' => $log->gate_name,
            ]);

        return Inertia::render('Customer/Portal/Access/Index', [
            'accessCodes' => $accessCodes,
            'guestCodes' => $guestCodes,
            'canCreateGuestCodes' => $canCreateGuestCodes,
            'maxGuests' => $maxGuests,
            'sites' => $sites,
            'accessHistory' => $accessHistory,
        ]);
    }

    /**
     * Create a guest access code
     */
    public function storeGuestCode(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();
        $tenant = $customer->tenant;

        // Check permission
        $settings = $tenant->self_service_settings ?? [];
        if (!$customer->allow_guest_access || !($settings['allow_guest_access'] ?? false)) {
            abort(403, 'Vous n\'êtes pas autorisé à créer des codes invités.');
        }

        // Check max guests
        $maxGuests = $settings['max_guests_per_customer'] ?? 2;
        $currentActiveGuests = GuestAccessCode::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'active'])
            ->count();

        if ($currentActiveGuests >= $maxGuests) {
            return back()->withErrors(['error' => "Vous avez atteint le nombre maximum de codes invités actifs ({$maxGuests})."]);
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'guest_phone' => 'nullable|string|max:20',
            'valid_from' => 'required|date|after_or_equal:now',
            'valid_until' => 'required|date|after:valid_from',
            'purpose' => 'nullable|string|max:255',
        ]);

        // Verify site belongs to tenant
        $site = Site::where('id', $validated['site_id'])
            ->where('tenant_id', $tenant->id)
            ->where('self_service_enabled', true)
            ->firstOrFail();

        GuestAccessCode::createForGuest(
            $customer,
            $site,
            $validated['guest_name'],
            new \DateTime($validated['valid_from']),
            new \DateTime($validated['valid_until']),
            [
                'guest_email' => $validated['guest_email'],
                'guest_phone' => $validated['guest_phone'],
                'purpose' => $validated['purpose'],
                'max_uses' => 1,
            ]
        );

        return back()->with('success', 'Code invité créé avec succès.');
    }

    /**
     * Cancel a guest access code
     */
    public function cancelGuestCode(GuestAccessCode $guestCode)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($guestCode->customer_id !== $customer->id) {
            abort(403);
        }

        $guestCode->cancel();

        return back()->with('success', 'Code invité annulé.');
    }
}
