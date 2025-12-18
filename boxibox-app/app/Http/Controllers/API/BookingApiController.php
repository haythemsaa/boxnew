<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingApiKey;
use App\Models\BookingPromoCode;
use App\Models\BookingSettings;
use App\Models\Box;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingApiController extends Controller
{
    protected $apiKey;

    public function __construct(Request $request)
    {
        $key = $request->header('X-API-Key');
        $secret = $request->header('X-API-Secret');

        if ($key && $secret) {
            $this->apiKey = BookingApiKey::validateCredentials($key, $secret);

            if ($this->apiKey) {
                if (!$this->apiKey->isIpAllowed($request->ip())) {
                    abort(403, 'IP address not allowed');
                }
                $this->apiKey->recordUsage();
            }
        }
    }

    protected function requireAuth()
    {
        if (!$this->apiKey) {
            abort(401, 'Invalid API credentials');
        }
    }

    protected function requirePermission(string $permission)
    {
        $this->requireAuth();
        if (!$this->apiKey->hasPermission($permission)) {
            abort(403, "Missing permission: {$permission}");
        }
    }

    /**
     * Get list of sites with available boxes
     */
    public function sites(Request $request)
    {
        $this->requireAuth();

        $sites = Site::where('tenant_id', $this->apiKey->tenant_id)
            ->where('is_active', true)
            ->withCount(['boxes' => function ($q) {
                $q->where('status', 'available');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'code' => $site->code,
                    'address' => $site->address,
                    'city' => $site->city,
                    'postal_code' => $site->postal_code,
                    'country' => $site->country,
                    'available_boxes_count' => $site->boxes_count,
                ];
            }),
        ]);
    }

    /**
     * Get available boxes for a site
     */
    public function boxes(Request $request, int $siteId)
    {
        $this->requireAuth();

        $site = Site::where('id', $siteId)
            ->where('tenant_id', $this->apiKey->tenant_id)
            ->firstOrFail();

        $boxes = Box::where('site_id', $siteId)
            ->where('status', 'available')
            ->orderBy('current_price')
            ->get();

        return response()->json([
            'success' => true,
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
            ],
            'data' => $boxes->map(function ($box) {
                return [
                    'id' => $box->id,
                    'name' => $box->name,
                    'code' => $box->number,
                    'volume' => (float) $box->volume,
                    'length' => (float) $box->length,
                    'width' => (float) $box->width,
                    'height' => (float) $box->height,
                    'price' => (float) $box->current_price,
                    'features' => [
                        'climate_controlled' => $box->climate_controlled,
                        'has_electricity' => $box->has_electricity,
                        'has_24_7_access' => $box->has_24_7_access,
                        'has_alarm' => $box->has_alarm,
                        'is_ground_floor' => $box->is_ground_floor,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Check box availability
     */
    public function checkAvailability(Request $request, int $boxId)
    {
        $this->requireAuth();

        $box = Box::where('id', $boxId)
            ->whereHas('site', function ($q) {
                $q->where('tenant_id', $this->apiKey->tenant_id);
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'box_id' => $box->id,
                'name' => $box->name,
                'status' => $box->status,
                'is_available' => $box->status === 'available',
                'price' => (float) $box->current_price,
            ],
        ]);
    }

    /**
     * Create a booking via API
     */
    public function createBooking(Request $request)
    {
        $this->requirePermission('bookings.create');

        $validator = Validator::make($request->all(), [
            'site_id' => 'required|integer',
            'box_id' => 'required|integer',
            'customer' => 'required|array',
            'customer.first_name' => 'required|string|max:100',
            'customer.last_name' => 'required|string|max:100',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'nullable|string|max:20',
            'customer.address' => 'nullable|string|max:500',
            'customer.city' => 'nullable|string|max:100',
            'customer.postal_code' => 'nullable|string|max:20',
            'customer.country' => 'nullable|string|max:2',
            'customer.company' => 'nullable|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'promo_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify site belongs to tenant
        $site = Site::where('id', $request->site_id)
            ->where('tenant_id', $this->apiKey->tenant_id)
            ->firstOrFail();

        // Verify box belongs to site and is available
        $box = Box::where('id', $request->box_id)
            ->where('site_id', $site->id)
            ->firstOrFail();

        if ($box->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Box is not available',
            ], 422);
        }

        // Calculate discount
        $discountAmount = 0;
        if ($request->promo_code) {
            $promo = BookingPromoCode::findValidCode(
                $request->promo_code,
                $this->apiKey->tenant_id,
                $site->id
            );
            if ($promo) {
                $discountAmount = $promo->calculateDiscount($box->current_price);
                $promo->incrementUses();
            }
        }

        // Get deposit settings
        $settings = BookingSettings::getForTenant($this->apiKey->tenant_id, $site->id);
        $depositAmount = $settings ? $settings->getDepositForBox($box) : 0;

        DB::beginTransaction();
        try {
            $customer = $request->customer;

            $booking = Booking::create([
                'tenant_id' => $this->apiKey->tenant_id,
                'site_id' => $site->id,
                'box_id' => $box->id,
                'customer_first_name' => $customer['first_name'],
                'customer_last_name' => $customer['last_name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? null,
                'customer_address' => $customer['address'] ?? null,
                'customer_city' => $customer['city'] ?? null,
                'customer_postal_code' => $customer['postal_code'] ?? null,
                'customer_country' => $customer['country'] ?? 'FR',
                'customer_company' => $customer['company'] ?? null,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'rental_type' => $request->end_date ? 'fixed' : 'month_to_month',
                'monthly_price' => $box->current_price - $discountAmount,
                'deposit_amount' => $depositAmount,
                'status' => 'pending',
                'source' => 'api',
                'promo_code' => $request->promo_code,
                'discount_amount' => $discountAmount,
                'notes' => $request->notes,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            $booking->statusHistory()->create([
                'from_status' => null,
                'to_status' => 'pending',
                'notes' => 'Created via API',
            ]);

            $box->update(['status' => 'reserved']);

            if ($settings && $settings->auto_confirm) {
                $booking->confirm(null, 'Auto-confirmed');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'monthly_price' => (float) $booking->monthly_price,
                    'deposit_amount' => (float) $booking->deposit_amount,
                    'start_date' => $booking->start_date->format('Y-m-d'),
                    'status_url' => url("/booking/status/{$booking->uuid}"),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
            ], 500);
        }
    }

    /**
     * Get booking details
     */
    public function getBooking(Request $request, string $uuid)
    {
        $this->requirePermission('bookings.read');

        $booking = Booking::where('uuid', $uuid)
            ->where('tenant_id', $this->apiKey->tenant_id)
            ->with(['box', 'site'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'customer' => [
                    'first_name' => $booking->customer_first_name,
                    'last_name' => $booking->customer_last_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                ],
                'site' => [
                    'id' => $booking->site->id,
                    'name' => $booking->site->name,
                ],
                'box' => [
                    'id' => $booking->box->id,
                    'name' => $booking->box->name,
                    'code' => $booking->box->number,
                ],
                'dates' => [
                    'start_date' => $booking->start_date->format('Y-m-d'),
                    'end_date' => $booking->end_date?->format('Y-m-d'),
                ],
                'pricing' => [
                    'monthly_price' => (float) $booking->monthly_price,
                    'deposit_amount' => (float) $booking->deposit_amount,
                    'discount_amount' => (float) $booking->discount_amount,
                ],
                'created_at' => $booking->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * List bookings
     */
    public function listBookings(Request $request)
    {
        $this->requirePermission('bookings.read');

        $query = Booking::where('tenant_id', $this->apiKey->tenant_id)
            ->with(['box', 'site']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->from_date) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'uuid' => $booking->uuid,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'customer_name' => $booking->customer_full_name,
                    'customer_email' => $booking->customer_email,
                    'site_name' => $booking->site->name,
                    'box_name' => $booking->box->name,
                    'start_date' => $booking->start_date->format('Y-m-d'),
                    'monthly_price' => (float) $booking->monthly_price,
                    'created_at' => $booking->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, string $uuid)
    {
        $this->requirePermission('bookings.update');

        $request->validate([
            'status' => 'required|in:confirmed,rejected,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $booking = Booking::where('uuid', $uuid)
            ->where('tenant_id', $this->apiKey->tenant_id)
            ->firstOrFail();

        switch ($request->status) {
            case 'confirmed':
                $booking->confirm(null, $request->notes ?? 'Confirmed via API');
                break;
            case 'rejected':
                $booking->reject(null, $request->notes ?? 'Rejected via API');
                break;
            case 'cancelled':
                $booking->cancel(null, $request->notes ?? 'Cancelled via API');
                break;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'status' => $booking->fresh()->status,
            ],
        ]);
    }

    /**
     * Validate promo code
     */
    public function validatePromoCode(Request $request)
    {
        $this->requireAuth();

        $request->validate([
            'code' => 'required|string',
            'site_id' => 'nullable|integer',
            'price' => 'required|numeric',
        ]);

        $promo = BookingPromoCode::findValidCode(
            $request->code,
            $this->apiKey->tenant_id,
            $request->site_id
        );

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired promo code',
            ], 422);
        }

        $discount = $promo->calculateDiscount($request->price);

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $promo->code,
                'name' => $promo->name,
                'discount_type' => $promo->discount_type,
                'discount_value' => (float) $promo->discount_value,
                'calculated_discount' => $discount,
            ],
        ]);
    }
}
