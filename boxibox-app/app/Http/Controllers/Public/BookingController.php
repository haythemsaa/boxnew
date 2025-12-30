<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingPromoCode;
use App\Models\BookingSettings;
use App\Models\BookingWidget;
use App\Models\Box;
use App\Models\Lead;
use App\Models\Site;
use App\Models\Tenant;
use App\Services\AILeadScoringService;
use App\Services\NotificationService;
use App\Services\PricingService;
use Carbon\Carbon;
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
                            'name' => $box->display_name,
                            'number' => $box->number,
                            'volume' => $box->volume,
                            'formatted_volume' => $box->formatted_volume,
                            'dimensions' => $box->formatted_dimensions,
                            'current_price' => $box->current_price,
                            'climate_controlled' => $box->climate_controlled,
                            'has_electricity' => $box->has_electricity,
                            'has_24_7_access' => $box->has_24_7_access ?? false,
                            'is_ground_floor' => $box->ground_floor ?? false,
                            'images' => $box->images ?? [],
                            'photo_url' => $box->photo_url ?? null,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Show kiosk mode for unmanned facilities
     */
    public function kiosk(string $slug)
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

        return Inertia::render('Public/Kiosk/Index', [
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
                            'name' => $box->display_name,
                            'number' => $box->number,
                            'volume' => $box->volume,
                            'formatted_volume' => $box->formatted_volume,
                            'dimensions' => $box->formatted_dimensions,
                            'current_price' => $box->current_price,
                            'climate_controlled' => $box->climate_controlled,
                            'has_electricity' => $box->has_electricity,
                            'has_24_7_access' => $box->has_24_7_access ?? false,
                            'is_ground_floor' => $box->ground_floor ?? false,
                            'status' => $box->status,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Show optimized one-page checkout
     */
    public function checkout(string $slug)
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

        return Inertia::render('Public/Booking/Checkout', [
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
                            'name' => $box->display_name,
                            'number' => $box->number,
                            'volume' => $box->volume,
                            'formatted_volume' => $box->formatted_volume,
                            'dimensions' => $box->formatted_dimensions,
                            'current_price' => $box->current_price,
                            'climate_controlled' => $box->climate_controlled,
                            'has_electricity' => $box->has_electricity,
                            'has_24_7_access' => $box->has_24_7_access ?? false,
                            'is_ground_floor' => $box->ground_floor ?? false,
                            'status' => $box->status,
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
        $tenant = Tenant::findOrFail($tenantId);
        $settings = BookingSettings::getForTenant($tenantId);

        // If settings exist and have a slug, redirect to the slug URL
        if ($settings && $settings->booking_url_slug) {
            return redirect()->route('public.booking.show', $settings->booking_url_slug);
        }

        // Otherwise render directly with or without settings
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
                            'name' => $box->display_name,
                            'number' => $box->number,
                            'volume' => $box->volume,
                            'formatted_volume' => $box->formatted_volume,
                            'dimensions' => $box->formatted_dimensions,
                            'current_price' => $box->current_price,
                            'climate_controlled' => $box->climate_controlled,
                            'has_electricity' => $box->has_electricity,
                            'has_24_7_access' => $box->has_24_7_access ?? false,
                            'is_ground_floor' => $box->ground_floor ?? false,
                            'images' => $box->images ?? [],
                            'photo_url' => $box->photo_url ?? null,
                        ];
                    }),
                ];
            }),
        ]);
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
                    'name' => $box->display_name,
                    'number' => $box->number,
                    'volume' => $box->volume,
                    'formatted_volume' => $box->formatted_volume,
                    'dimensions' => $box->formatted_dimensions,
                    'length' => $box->length,
                    'width' => $box->width,
                    'height' => $box->height,
                    'current_price' => $box->current_price,
                    'climate_controlled' => $box->climate_controlled,
                    'has_electricity' => $box->has_electricity,
                    'has_24_7_access' => $box->has_24_7_access ?? false,
                    'has_alarm' => $box->has_alarm,
                    'has_wifi' => $box->has_wifi ?? false,
                    'has_shelving' => $box->has_shelving ?? false,
                    'is_ground_floor' => $box->ground_floor ?? false,
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
     * Calculate pricing with prorata, VAT, discounts
     * This is the main API for accurate pricing calculation
     */
    public function calculatePricing(Request $request, PricingService $pricingService)
    {
        $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'duration_months' => 'nullable|integer|min:1|max:60',
            'promo_code' => 'nullable|string',
            'include_insurance' => 'nullable|boolean',
        ]);

        $box = Box::findOrFail($request->box_id);
        $startDate = Carbon::parse($request->start_date);
        $settings = BookingSettings::getForTenant($request->tenant_id, $box->site_id);

        // Calculate complete pricing
        $pricing = $pricingService->calculateBookingTotal(
            box: $box,
            startDate: $startDate,
            durationMonths: $request->duration_months,
            promoCode: $request->promo_code,
            settings: $settings,
            includeInsurance: $request->boolean('include_insurance'),
        );

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
            'box' => [
                'id' => $box->id,
                'name' => $box->display_name,
                'base_price' => $box->current_price,
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
            'secondary_contact_name' => 'nullable|string|max:255',
            'secondary_contact_phone' => 'nullable|string|max:20',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'rental_type' => 'nullable|in:fixed,month_to_month',
            'duration_type' => 'nullable|in:month_to_month,fixed_term',
            'planned_duration_months' => 'nullable|integer|min:1|max:60',
            'promo_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'special_requests' => 'nullable|string|max:2000',
            'needs_24h_access' => 'nullable|boolean',
            'needs_climate_control' => 'nullable|boolean',
            'needs_electricity' => 'nullable|boolean',
            'needs_insurance' => 'nullable|boolean',
            'needs_moving_help' => 'nullable|boolean',
            'preferred_time_slot' => 'nullable|in:morning,afternoon,evening,flexible',
            'storage_contents' => 'nullable|string|max:1000',
            'estimated_value' => 'nullable|in:under_1000,1000_5000,5000_10000,over_10000',
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
            // Calculate planned end date if duration specified
            $plannedEndDate = null;
            if ($request->duration_type === 'fixed_term' && $request->planned_duration_months) {
                $plannedEndDate = \Carbon\Carbon::parse($request->start_date)
                    ->addMonths($request->planned_duration_months);
            }

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
                'secondary_contact_name' => $request->secondary_contact_name,
                'secondary_contact_phone' => $request->secondary_contact_phone,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'rental_type' => $request->rental_type ?? 'month_to_month',
                'duration_type' => $request->duration_type ?? 'month_to_month',
                'planned_duration_months' => $request->planned_duration_months,
                'planned_end_date' => $plannedEndDate,
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
                'special_requests' => $request->special_requests,
                'needs_24h_access' => $request->boolean('needs_24h_access'),
                'needs_climate_control' => $request->boolean('needs_climate_control'),
                'needs_electricity' => $request->boolean('needs_electricity'),
                'needs_insurance' => $request->boolean('needs_insurance'),
                'needs_moving_help' => $request->boolean('needs_moving_help'),
                'preferred_time_slot' => $request->preferred_time_slot,
                'storage_contents' => $request->storage_contents,
                'estimated_value' => $request->estimated_value,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Create status history
            $initialStatus = 'pending';
            $statusNotes = 'Réservation créée depuis ' . ($request->source ?? 'website');

            // If paid online, update status and create payment record
            if ($request->payment_intent_id && $request->amount_paid > 0) {
                $booking->update([
                    'payment_method' => 'card_now',
                ]);

                // Create payment record
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'type' => 'full_payment',
                    'amount' => $request->amount_paid,
                    'payment_method' => 'stripe',
                    'transaction_id' => $request->payment_intent_id,
                    'status' => 'completed',
                    'notes' => 'Paiement en ligne via Stripe',
                    'payment_data' => json_encode([
                        'payment_intent_id' => $request->payment_intent_id,
                        'paid_at' => now()->toIso8601String(),
                    ]),
                ]);

                $initialStatus = 'confirmed';
                $statusNotes = 'Réservation payée en ligne - ' . number_format($request->amount_paid, 2) . ' €';

                // Auto-confirm since paid
                $booking->update(['status' => 'confirmed']);
            } else {
                // Mark payment method
                $booking->update([
                    'payment_method' => $request->payment_method ?? 'at_signing',
                ]);
            }

            $booking->statusHistory()->create([
                'from_status' => null,
                'to_status' => $initialStatus,
                'notes' => $statusNotes,
            ]);

            // Mark box as reserved
            $box->update(['status' => 'reserved']);

            // Auto-confirm if enabled and not already confirmed
            if ($settings && $settings->auto_confirm && $booking->status !== 'confirmed') {
                $booking->confirm(null, 'Confirmation automatique');
            }

            // ===== CREATE LEAD IN CRM =====
            // This ensures every booking is tracked as a lead for follow-up and conversion tracking
            try {
                $lead = $this->createLeadFromBooking($booking, $request);
                \Illuminate\Support\Facades\Log::info('Lead created from booking', [
                    'booking_id' => $booking->id,
                    'lead_id' => $lead->id,
                ]);
            } catch (\Exception $e) {
                // Log but don't fail the booking
                \Illuminate\Support\Facades\Log::error('Failed to create lead from booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            // Send notifications after successful commit
            try {
                $notificationService = app(NotificationService::class);

                // Notify tenant users about new booking (in-app + email)
                $notificationService->notifyNewBooking($booking);

                // Send confirmation email to customer
                $notificationService->sendBookingConfirmationToCustomer($booking);
            } catch (\Exception $e) {
                // Log error but don't fail the booking creation
                \Illuminate\Support\Facades\Log::error('Failed to send booking notifications: ' . $e->getMessage(), [
                    'booking_id' => $booking->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->fresh()->status,
                    'status_label' => $booking->fresh()->status_label,
                    'monthly_price' => $booking->monthly_price,
                    'deposit_amount' => $booking->deposit_amount,
                    'discount_amount' => $booking->discount_amount,
                    'amount_paid' => $request->amount_paid ?? 0,
                    'payment_method' => $booking->payment_method,
                ],
                'message' => $request->payment_intent_id
                    ? 'Votre réservation a été payée et confirmée !'
                    : 'Votre réservation a été enregistrée avec succès !',
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
     * Create Stripe PaymentIntent for online payment
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'amount' => 'required|numeric|min:1',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
        ]);

        $settings = BookingSettings::getForTenant($request->tenant_id);

        if (!$settings || !$settings->stripe_secret_key) {
            return response()->json([
                'error' => 'Payment not configured',
            ], 400);
        }

        try {
            // Set Stripe API key
            \Stripe\Stripe::setApiKey($settings->stripe_secret_key);

            // Create PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int) ($request->amount * 100), // Amount in cents
                'currency' => 'eur',
                'description' => 'Reservation de box - ' . $settings->company_name,
                'receipt_email' => $request->customer_email,
                'metadata' => [
                    'tenant_id' => $request->tenant_id,
                    'customer_name' => $request->customer_name,
                    'customer_email' => $request->customer_email,
                ],
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la création du paiement',
            ], 500);
        }
    }

    /**
     * Check availability for a specific box on a given date
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $box = Box::find($request->box_id);

        if (!$box) {
            return response()->json(['available' => false]);
        }

        // Check if box is currently available
        if ($box->status !== 'available') {
            return response()->json(['available' => false]);
        }

        // Check for conflicting bookings at the given start date
        $conflictingBooking = Booking::where('box_id', $box->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function ($query) use ($request) {
                $query->where('start_date', '<=', $request->start_date)
                    ->where(function ($q) use ($request) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $request->start_date);
                    });
            })
            ->exists();

        return response()->json([
            'available' => !$conflictingBooking,
            'box_id' => $box->id,
            'date' => $request->start_date,
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
                            'name' => $box->display_name,
                            'volume' => $box->volume,
                            'current_price' => $box->current_price,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Create a Lead in CRM from a booking
     * This ensures all bookings are tracked for follow-up and conversion analytics
     */
    protected function createLeadFromBooking(Booking $booking, Request $request): Lead
    {
        // Check for existing lead with same email to avoid duplicates
        $existingLead = Lead::where('tenant_id', $booking->tenant_id)
            ->where('email', $booking->customer_email)
            ->whereNull('converted_at')
            ->first();

        if ($existingLead) {
            // Update existing lead with new booking info
            $existingLead->update([
                'last_activity_at' => now(),
                'metadata' => array_merge($existingLead->metadata ?? [], [
                    'latest_booking_id' => $booking->id,
                    'booking_date' => $booking->created_at->toDateTimeString(),
                    'booking_status' => $booking->status,
                ]),
            ]);
            return $existingLead;
        }

        // Determine lead source from booking
        $source = match ($booking->source) {
            'widget' => 'widget',
            'api' => 'api',
            default => 'website',
        };

        // Build metadata for AI scoring
        $metadata = [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'booking_status' => $booking->status,
            'booking_source' => $booking->source,
            'box_id' => $booking->box_id,
            'box_price' => $booking->monthly_price,
            'requested_quote' => true,
            'visited_pricing' => true,
            'utm_source' => $booking->utm_source,
            'utm_medium' => $booking->utm_medium,
            'utm_campaign' => $booking->utm_campaign,
            'ip_address' => $booking->ip_address,
            'user_agent' => $booking->user_agent,
            'needs_24h_access' => $booking->needs_24h_access,
            'needs_climate_control' => $booking->needs_climate_control,
            'needs_insurance' => $booking->needs_insurance,
        ];

        // Determine customer type
        $type = !empty($booking->customer_company) ? 'company' : 'individual';

        // Create the lead
        $lead = Lead::create([
            'tenant_id' => $booking->tenant_id,
            'site_id' => $booking->site_id,
            'first_name' => $booking->customer_first_name,
            'last_name' => $booking->customer_last_name,
            'email' => $booking->customer_email,
            'phone' => $booking->customer_phone,
            'company' => $booking->customer_company,
            'type' => $type,
            'status' => $booking->status === 'confirmed' ? 'qualified' : 'new',
            'source' => $source,
            'budget_min' => $booking->monthly_price,
            'budget_max' => $booking->monthly_price * 1.5,
            'move_in_date' => $booking->start_date,
            'box_type_interest' => $booking->box?->type ?? 'standard',
            'notes' => "Réservation automatique #{$booking->booking_number}\n" .
                      ($booking->notes ? "Notes client: {$booking->notes}\n" : '') .
                      ($booking->special_requests ? "Demandes spéciales: {$booking->special_requests}" : ''),
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);

        // Calculate AI score
        try {
            $scoringService = app(AILeadScoringService::class);
            $scoreResult = $scoringService->calculateScore($lead, 'lead');

            $lead->update([
                'score' => $scoreResult['score'],
                'priority' => $scoreResult['priority'],
                'conversion_probability' => $scoreResult['conversion_probability'],
                'score_breakdown' => $scoreResult['breakdown'],
                'score_factors' => $scoreResult['factors'],
                'score_calculated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Fallback to basic scoring
            $lead->updateScore();
        }

        return $lead;
    }
}
