<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingPromoCode;
use App\Models\BookingSettings;
use App\Models\BookingWidget;
use App\Models\Box;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BookingController extends Controller
{
    /**
     * Show booking page by tenant slug
     */
    public function show(string $slug)
    {
        $settings = BookingSettings::getBySlug($slug);

        if (!$settings || !$settings->is_enabled) {
            abort(404, 'Page de réservation non trouvée');
        }

        $tenant = $settings->tenant;
        $sites = Site::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['boxes' => function ($q) {
                $q->where('status', 'available')
                    ->orderBy('current_price');
            }])
            ->get();

        return Inertia::render('Public/Booking/Index', [
            'settings' => $settings,
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'sites' => $sites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'address' => $site->address,
                    'city' => $site->city,
                    'postal_code' => $site->postal_code,
                    'available_boxes_count' => $site->boxes->count(),
                    'boxes' => $site->boxes->map(function ($box) {
                        return [
                            'id' => $box->id,
                            'name' => $box->name,
                            'code' => $box->code,
                            'volume' => $box->volume,
                            'formatted_volume' => $box->formatted_volume,
                            'dimensions' => $box->formatted_dimensions,
                            'current_price' => $box->current_price,
                            'climate_controlled' => $box->climate_controlled,
                            'has_electricity' => $box->has_electricity,
                            'has_24_7_access' => $box->has_24_7_access,
                            'is_ground_floor' => $box->is_ground_floor,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Show booking page by tenant ID (fallback)
     */
    public function showByTenant(int $tenantId)
    {
        $settings = BookingSettings::getForTenant($tenantId);

        if (!$settings || !$settings->is_enabled) {
            abort(404, 'Page de réservation non trouvée');
        }

        return redirect()->route('booking.show', $settings->booking_url_slug ?? "tenant/{$tenantId}");
    }

    /**
     * Get available boxes for a site
     */
    public function getAvailableBoxes(Request $request, int $siteId)
    {
        $site = Site::findOrFail($siteId);

        $boxes = Box::where('site_id', $siteId)
            ->where('status', 'available')
            ->orderBy('current_price')
            ->get()
            ->map(function ($box) {
                return [
                    'id' => $box->id,
                    'name' => $box->name,
                    'code' => $box->code,
                    'volume' => $box->volume,
                    'formatted_volume' => $box->formatted_volume,
                    'dimensions' => $box->formatted_dimensions,
                    'length' => $box->length,
                    'width' => $box->width,
                    'height' => $box->height,
                    'current_price' => $box->current_price,
                    'climate_controlled' => $box->climate_controlled,
                    'has_electricity' => $box->has_electricity,
                    'has_24_7_access' => $box->has_24_7_access,
                    'has_alarm' => $box->has_alarm,
                    'has_wifi' => $box->has_wifi,
                    'has_shelving' => $box->has_shelving,
                    'is_ground_floor' => $box->is_ground_floor,
                ];
            });

        return response()->json([
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'address' => $site->address,
                'city' => $site->city,
            ],
            'boxes' => $boxes,
        ]);
    }

    /**
     * Validate promo code
     */
    public function validatePromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'tenant_id' => 'required|integer',
            'site_id' => 'nullable|integer',
            'monthly_price' => 'required|numeric',
        ]);

        $promo = BookingPromoCode::findValidCode(
            $request->code,
            $request->tenant_id,
            $request->site_id
        );

        if (!$promo) {
            return response()->json([
                'valid' => false,
                'message' => 'Code promo invalide ou expiré',
            ], 422);
        }

        $discount = $promo->calculateDiscount($request->monthly_price);

        return response()->json([
            'valid' => true,
            'promo' => [
                'code' => $promo->code,
                'name' => $promo->name,
                'discount_type' => $promo->discount_type,
                'discount_value' => $promo->discount_value,
                'discount_label' => $promo->discount_label,
                'calculated_discount' => $discount,
            ],
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'required|exists:boxes,id',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'customer_city' => 'nullable|string|max:100',
            'customer_postal_code' => 'nullable|string|max:20',
            'customer_country' => 'nullable|string|max:2',
            'customer_company' => 'nullable|string|max:255',
            'customer_vat_number' => 'nullable|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'rental_type' => 'nullable|in:fixed,month_to_month',
            'promo_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'terms_accepted' => 'required|accepted',
            'source' => 'nullable|in:website,widget,api',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if box is still available
        $box = Box::findOrFail($request->box_id);
        if ($box->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Ce box n\'est plus disponible',
            ], 422);
        }

        // Get settings for deposit calculation
        $settings = BookingSettings::getForTenant($request->tenant_id, $request->site_id);

        // Calculate discount if promo code
        $discountAmount = 0;
        if ($request->promo_code) {
            $promo = BookingPromoCode::findValidCode(
                $request->promo_code,
                $request->tenant_id,
                $request->site_id
            );
            if ($promo) {
                $discountAmount = $promo->calculateDiscount($box->current_price);
                $promo->incrementUses();
            }
        }

        // Calculate deposit
        $depositAmount = $settings ? $settings->getDepositForBox($box) : 0;

        DB::beginTransaction();
        try {
            // Create booking
            $booking = Booking::create([
                'tenant_id' => $request->tenant_id,
                'site_id' => $request->site_id,
                'box_id' => $box->id,
                'customer_first_name' => $request->customer_first_name,
                'customer_last_name' => $request->customer_last_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'customer_city' => $request->customer_city,
                'customer_postal_code' => $request->customer_postal_code,
                'customer_country' => $request->customer_country ?? 'FR',
                'customer_company' => $request->customer_company,
                'customer_vat_number' => $request->customer_vat_number,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'rental_type' => $request->rental_type ?? 'month_to_month',
                'monthly_price' => $box->current_price - $discountAmount,
                'deposit_amount' => $depositAmount,
                'status' => 'pending',
                'source' => $request->source ?? 'website',
                'source_url' => $request->header('Referer'),
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'promo_code' => $request->promo_code,
                'discount_amount' => $discountAmount,
                'notes' => $request->notes,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Create status history
            $booking->statusHistory()->create([
                'from_status' => null,
                'to_status' => 'pending',
                'notes' => 'Réservation créée depuis ' . ($request->source ?? 'website'),
            ]);

            // Mark box as reserved
            $box->update(['status' => 'reserved']);

            // Auto-confirm if enabled
            if ($settings && $settings->auto_confirm) {
                $booking->confirm(null, 'Confirmation automatique');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'status_label' => $booking->status_label,
                    'monthly_price' => $booking->monthly_price,
                    'deposit_amount' => $booking->deposit_amount,
                    'discount_amount' => $booking->discount_amount,
                ],
                'message' => 'Votre réservation a été enregistrée avec succès !',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la réservation',
            ], 500);
        }
    }

    /**
     * Check booking status
     */
    public function status(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Public/Booking/Status', [
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'booking_number' => $booking->booking_number,
                'customer_name' => $booking->customer_full_name,
                'customer_email' => $booking->customer_email,
                'box_name' => $booking->box->name,
                'site_name' => $booking->site->name,
                'start_date' => $booking->start_date->format('d/m/Y'),
                'monthly_price' => $booking->monthly_price,
                'deposit_amount' => $booking->deposit_amount,
                'status' => $booking->status,
                'status_label' => $booking->status_label,
                'status_color' => $booking->status_color,
                'created_at' => $booking->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Widget endpoint - returns embeddable booking interface
     */
    public function widget(string $widgetKey)
    {
        $widget = BookingWidget::findByKey($widgetKey);

        if (!$widget) {
            abort(404, 'Widget non trouvé');
        }

        $widget->incrementViews();

        $settings = BookingSettings::getForTenant($widget->tenant_id, $widget->site_id);
        $tenant = $widget->tenant;

        $sitesQuery = Site::where('tenant_id', $tenant->id)
            ->where('is_active', true);

        if ($widget->site_id) {
            $sitesQuery->where('id', $widget->site_id);
        }

        $sites = $sitesQuery->with(['boxes' => function ($q) {
            $q->where('status', 'available')->orderBy('current_price');
        }])->get();

        return Inertia::render('Public/Booking/Widget', [
            'widget' => $widget->only(['widget_key', 'widget_type', 'style_config']),
            'settings' => $settings,
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'sites' => $sites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'address' => $site->address,
                    'city' => $site->city,
                    'available_boxes_count' => $site->boxes->count(),
                    'boxes' => $site->boxes->map(function ($box) {
                        return [
                            'id' => $box->id,
                            'name' => $box->name,
                            'volume' => $box->volume,
                            'current_price' => $box->current_price,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
