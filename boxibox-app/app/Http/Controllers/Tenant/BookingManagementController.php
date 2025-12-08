<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingApiKey;
use App\Models\BookingPromoCode;
use App\Models\BookingSettings;
use App\Models\BookingWidget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BookingManagementController extends Controller
{
    protected function getTenantId()
    {
        return Auth::user()->tenant_id;
    }

    /**
     * List all bookings
     */
    public function index(Request $request)
    {
        $query = Booking::where('tenant_id', $this->getTenantId())
            ->with(['site', 'box', 'customer']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by site
        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by source
        if ($request->source) {
            $query->where('source', $request->source);
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('customer_first_name', 'like', "%{$search}%")
                    ->orWhere('customer_last_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($booking) {
                return [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => $booking->customer_full_name,
                    'customer_email' => $booking->customer_email,
                    'customer_phone' => $booking->customer_phone,
                    'site_name' => $booking->site->name,
                    'box_name' => $booking->box->name,
                    'start_date' => $booking->start_date->format('d/m/Y'),
                    'monthly_price' => $booking->monthly_price,
                    'deposit_amount' => $booking->deposit_amount,
                    'status' => $booking->status,
                    'status_label' => $booking->status_label,
                    'status_color' => $booking->status_color,
                    'source' => $booking->source,
                    'source_label' => $booking->source_label,
                    'created_at' => $booking->created_at->format('d/m/Y H:i'),
                ];
            });

        // Get statistics
        $stats = [
            'total' => Booking::where('tenant_id', $this->getTenantId())->count(),
            'pending' => Booking::where('tenant_id', $this->getTenantId())->where('status', 'pending')->count(),
            'confirmed' => Booking::where('tenant_id', $this->getTenantId())->whereIn('status', ['confirmed', 'deposit_paid'])->count(),
            'active' => Booking::where('tenant_id', $this->getTenantId())->where('status', 'active')->count(),
            'this_month' => Booking::where('tenant_id', $this->getTenantId())->whereMonth('created_at', now()->month)->count(),
        ];

        return Inertia::render('Tenant/Bookings/Index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'filters' => $request->only(['status', 'site_id', 'source', 'search']),
        ]);
    }

    /**
     * Show booking details
     */
    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load(['site', 'box', 'customer', 'contract', 'statusHistory.user', 'payments']);

        return Inertia::render('Tenant/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'booking_number' => $booking->booking_number,
                'customer' => [
                    'full_name' => $booking->customer_full_name,
                    'first_name' => $booking->customer_first_name,
                    'last_name' => $booking->customer_last_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                    'address' => $booking->customer_address,
                    'city' => $booking->customer_city,
                    'postal_code' => $booking->customer_postal_code,
                    'country' => $booking->customer_country,
                    'company' => $booking->customer_company,
                    'vat_number' => $booking->customer_vat_number,
                    'secondary_contact_name' => $booking->secondary_contact_name,
                    'secondary_contact_phone' => $booking->secondary_contact_phone,
                ],
                'site' => [
                    'id' => $booking->site->id,
                    'name' => $booking->site->name,
                    'address' => $booking->site->address,
                ],
                'box' => [
                    'id' => $booking->box->id,
                    'name' => $booking->box->name,
                    'code' => $booking->box->code,
                    'volume' => $booking->box->formatted_volume,
                    'dimensions' => $booking->box->formatted_dimensions,
                ],
                'start_date' => $booking->start_date->format('d/m/Y'),
                'end_date' => $booking->end_date?->format('d/m/Y'),
                'rental_type' => $booking->rental_type,
                'duration_type' => $booking->duration_type,
                'planned_duration_months' => $booking->planned_duration_months,
                'planned_end_date' => $booking->planned_end_date?->format('d/m/Y'),
                'monthly_price' => $booking->monthly_price,
                'deposit_amount' => $booking->deposit_amount,
                'discount_amount' => $booking->discount_amount,
                'promo_code' => $booking->promo_code,
                'status' => $booking->status,
                'status_label' => $booking->status_label,
                'status_color' => $booking->status_color,
                'source' => $booking->source,
                'source_label' => $booking->source_label,
                'source_url' => $booking->source_url,
                'utm' => [
                    'source' => $booking->utm_source,
                    'medium' => $booking->utm_medium,
                    'campaign' => $booking->utm_campaign,
                ],
                // Special needs
                'special_needs' => [
                    'needs_24h_access' => $booking->needs_24h_access,
                    'needs_climate_control' => $booking->needs_climate_control,
                    'needs_electricity' => $booking->needs_electricity,
                    'needs_insurance' => $booking->needs_insurance,
                    'needs_moving_help' => $booking->needs_moving_help,
                    'preferred_time_slot' => $booking->preferred_time_slot,
                ],
                'storage_contents' => $booking->storage_contents,
                'estimated_value' => $booking->estimated_value,
                'notes' => $booking->notes,
                'special_requests' => $booking->special_requests,
                'internal_notes' => $booking->internal_notes,
                'terms_accepted' => $booking->terms_accepted,
                'terms_accepted_at' => $booking->terms_accepted_at?->format('d/m/Y H:i'),
                'ip_address' => $booking->ip_address,
                'created_at' => $booking->created_at->format('d/m/Y H:i'),
                'contract' => $booking->contract ? [
                    'id' => $booking->contract->id,
                    'contract_number' => $booking->contract->contract_number,
                ] : null,
                'converted_at' => $booking->converted_at?->format('d/m/Y H:i'),
                'history' => $booking->statusHistory->map(function ($h) {
                    return [
                        'from_status' => $h->from_status,
                        'to_status' => $h->to_status,
                        'notes' => $h->notes,
                        'user' => $h->user?->name,
                        'created_at' => $h->created_at->format('d/m/Y H:i'),
                    ];
                }),
                'payments' => $booking->payments->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'type' => $p->type,
                        'type_label' => $p->type_label,
                        'amount' => $p->amount,
                        'status' => $p->status,
                        'status_label' => $p->status_label,
                        'status_color' => $p->status_color,
                        'created_at' => $p->created_at->format('d/m/Y H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Confirm a booking
     */
    public function confirm(Request $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $booking->confirm(Auth::id(), $request->notes);

        return back()->with('success', 'Réservation confirmée avec succès');
    }

    /**
     * Reject a booking
     */
    public function reject(Request $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking->reject(Auth::id(), $request->reason);

        return back()->with('success', 'Réservation refusée');
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $booking->cancel(Auth::id(), $request->reason);

        return back()->with('success', 'Réservation annulée');
    }

    /**
     * Convert booking to contract
     */
    public function convertToContract(Booking $booking)
    {
        $this->authorize('update', $booking);

        if ($booking->contract_id) {
            return back()->with('error', 'Cette réservation a déjà été convertie en contrat');
        }

        $contract = $booking->convertToContract();

        if ($contract) {
            return redirect()->route('tenant.contracts.show', $contract->id)
                ->with('success', 'Réservation convertie en contrat avec succès');
        }

        return back()->with('error', 'Erreur lors de la conversion');
    }

    /**
     * Booking settings page
     */
    public function settings()
    {
        $settings = BookingSettings::getForTenant($this->getTenantId());

        if (!$settings) {
            $settings = BookingSettings::create([
                'tenant_id' => $this->getTenantId(),
                'is_enabled' => false,
            ]);
        }

        return Inertia::render('Tenant/Bookings/Settings', [
            'settings' => $settings,
            'booking_url' => $settings->booking_url,
        ]);
    }

    /**
     * Update booking settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'is_enabled' => 'boolean',
            'booking_url_slug' => 'nullable|string|max:50|unique:booking_settings,booking_url_slug,' . $request->settings_id,
            'company_name' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:10',
            'secondary_color' => 'nullable|string|max:10',
            'welcome_message' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string',
            'require_deposit' => 'boolean',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'min_rental_days' => 'integer|min:1',
            'max_advance_booking_days' => 'integer|min:1',
            'auto_confirm' => 'boolean',
            'require_id_verification' => 'boolean',
            'allow_promo_codes' => 'boolean',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        $settings = BookingSettings::getForTenant($this->getTenantId());

        if (!$settings) {
            $settings = new BookingSettings(['tenant_id' => $this->getTenantId()]);
        }

        $settings->fill($request->all());
        $settings->save();

        return back()->with('success', 'Paramètres enregistrés avec succès');
    }

    /**
     * List widgets
     */
    public function widgets()
    {
        $widgets = BookingWidget::where('tenant_id', $this->getTenantId())
            ->with('site')
            ->get()
            ->map(function ($widget) {
                return [
                    'id' => $widget->id,
                    'widget_key' => $widget->widget_key,
                    'name' => $widget->name,
                    'site' => $widget->site?->name ?? 'Tous les sites',
                    'widget_type' => $widget->widget_type,
                    'is_active' => $widget->is_active,
                    'views_count' => $widget->views_count,
                    'bookings_count' => $widget->bookings_count,
                    'embed_code' => $widget->embed_code,
                    'iframe_code' => $widget->iframe_code,
                    'created_at' => $widget->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Tenant/Bookings/Widgets', [
            'widgets' => $widgets,
        ]);
    }

    /**
     * Create a widget
     */
    public function createWidget(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'nullable|exists:sites,id',
            'widget_type' => 'required|in:full,compact,button,inline',
            'allowed_domains' => 'nullable|array',
        ]);

        BookingWidget::create([
            'tenant_id' => $this->getTenantId(),
            'site_id' => $request->site_id,
            'name' => $request->name,
            'widget_type' => $request->widget_type,
            'allowed_domains' => $request->allowed_domains,
            'is_active' => true,
        ]);

        return back()->with('success', 'Widget créé avec succès');
    }

    /**
     * Update a widget
     */
    public function updateWidget(Request $request, BookingWidget $widget)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'widget_type' => 'required|in:full,compact,button,inline',
            'allowed_domains' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $widget->update($request->only(['name', 'widget_type', 'allowed_domains', 'is_active']));

        return back()->with('success', 'Widget mis à jour');
    }

    /**
     * Delete a widget
     */
    public function deleteWidget(BookingWidget $widget)
    {
        $widget->delete();

        return back()->with('success', 'Widget supprimé');
    }

    /**
     * List API keys
     */
    public function apiKeys()
    {
        $apiKeys = BookingApiKey::where('tenant_id', $this->getTenantId())
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'api_key' => $key->api_key,
                    'permissions' => $key->permissions,
                    'ip_whitelist' => $key->ip_whitelist,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->format('d/m/Y H:i'),
                    'requests_count' => $key->requests_count,
                    'created_at' => $key->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Tenant/Bookings/ApiKeys', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Create API key
     */
    public function createApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'ip_whitelist' => 'nullable|array',
        ]);

        $apiKey = BookingApiKey::create([
            'tenant_id' => $this->getTenantId(),
            'name' => $request->name,
            'permissions' => $request->permissions,
            'ip_whitelist' => $request->ip_whitelist,
            'is_active' => true,
        ]);

        return back()->with('success', 'Clé API créée avec succès')->with('new_api_secret', $apiKey->api_secret);
    }

    /**
     * Update API key
     */
    public function updateApiKey(Request $request, BookingApiKey $apiKey)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'ip_whitelist' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $apiKey->update($request->only(['name', 'permissions', 'ip_whitelist', 'is_active']));

        return back()->with('success', 'Clé API mise à jour');
    }

    /**
     * Delete API key
     */
    public function deleteApiKey(BookingApiKey $apiKey)
    {
        $apiKey->delete();

        return back()->with('success', 'Clé API supprimée');
    }

    /**
     * Regenerate API secret
     */
    public function regenerateApiKey(BookingApiKey $apiKey)
    {
        $newSecret = $apiKey->regenerateSecret();

        return back()->with('success', 'Secret API régénéré')->with('new_api_secret', $newSecret);
    }

    /**
     * List promo codes
     */
    public function promoCodes()
    {
        $promoCodes = BookingPromoCode::where('tenant_id', $this->getTenantId())
            ->with('site')
            ->get()
            ->map(function ($code) {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'name' => $code->name,
                    'description' => $code->description,
                    'discount_type' => $code->discount_type,
                    'discount_value' => $code->discount_value,
                    'discount_label' => $code->discount_label,
                    'site' => $code->site?->name ?? 'Tous les sites',
                    'uses_count' => $code->uses_count,
                    'max_uses' => $code->max_uses,
                    'valid_from' => $code->valid_from?->format('d/m/Y'),
                    'valid_until' => $code->valid_until?->format('d/m/Y'),
                    'is_active' => $code->is_active,
                    'is_valid' => $code->isValid(),
                ];
            });

        return Inertia::render('Tenant/Bookings/PromoCodes', [
            'promoCodes' => $promoCodes,
        ]);
    }

    /**
     * Create promo code
     */
    public function createPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:booking_promo_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_months',
            'discount_value' => 'required|numeric|min:0',
            'site_id' => 'nullable|exists:sites,id',
            'min_rental_amount' => 'nullable|numeric|min:0',
            'min_rental_months' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'first_time_only' => 'boolean',
        ]);

        BookingPromoCode::create([
            'tenant_id' => $this->getTenantId(),
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'site_id' => $request->site_id,
            'min_rental_amount' => $request->min_rental_amount,
            'min_rental_months' => $request->min_rental_months,
            'max_uses' => $request->max_uses,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'first_time_only' => $request->first_time_only ?? false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Code promo créé avec succès');
    }

    /**
     * Update promo code
     */
    public function updatePromoCode(Request $request, BookingPromoCode $promoCode)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_months',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
        ]);

        $promoCode->update($request->only([
            'name', 'description', 'discount_type', 'discount_value',
            'max_uses', 'valid_from', 'valid_until', 'is_active'
        ]));

        return back()->with('success', 'Code promo mis à jour');
    }

    /**
     * Delete promo code
     */
    public function deletePromoCode(BookingPromoCode $promoCode)
    {
        $promoCode->delete();

        return back()->with('success', 'Code promo supprimé');
    }
}
