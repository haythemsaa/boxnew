<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleReserveBooking;
use App\Models\GoogleReserveSettings;
use App\Models\GoogleReserveSlot;
use App\Models\GoogleReserveSyncLog;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Google Reserve Webhook Controller
 *
 * Handles incoming requests from Google Reserve API
 * Documentation: https://developers.google.com/maps-booking/reference/rest-api-v3
 */
class GoogleReserveWebhookController extends Controller
{
    /**
     * Verify webhook signature from Google
     */
    protected function verifySignature(Request $request, string $webhookSecret): bool
    {
        $signature = $request->header('X-Goog-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $webhookSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Health check endpoint for Google
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check availability for a given time range
     * Called by Google to show available slots to users
     *
     * POST /api/google-reserve/v3/CheckAvailability
     */
    public function checkAvailability(Request $request)
    {
        // Verify webhook signature (SECURITY)
        $merchantId = $request->input('slot.merchant_id');
        if ($merchantId) {
            $settings = GoogleReserveSettings::where('merchant_id', $merchantId)->first();
            if ($settings && $settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
                Log::warning('Google Reserve invalid signature', ['merchant_id' => $merchantId]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $data = $request->validate([
            'slot' => 'required|array',
            'slot.merchant_id' => 'required|string',
            'slot.service_id' => 'nullable|string',
            'slot.start_time' => 'required|string',
            'slot.duration' => 'nullable|string',
        ]);

        $merchantId = $data['slot']['merchant_id'];
        $startTime = Carbon::parse($data['slot']['start_time']);

        // Find settings by merchant_id
        $settings = GoogleReserveSettings::where('merchant_id', $merchantId)
            ->where('is_enabled', true)
            ->first();

        if (!$settings) {
            return response()->json([
                'slot' => $data['slot'],
                'count_available' => 0,
                'last_online_cancellable_time' => null,
                'duration_requirement' => 'DURATION_REQUIREMENT_UNSPECIFIED',
            ]);
        }

        // Check if slot exists and has availability
        $slot = GoogleReserveSlot::where('tenant_id', $settings->tenant_id)
            ->where('site_id', $settings->site_id)
            ->where('date', $startTime->toDateString())
            ->where('start_time', $startTime->format('H:i:s'))
            ->where('is_available', true)
            ->where('is_blocked', false)
            ->first();

        $available = $slot ? max(0, $slot->max_bookings - $slot->current_bookings) : 0;

        // Calculate last cancellable time (e.g., 2 hours before)
        $lastCancellableTime = $startTime->copy()->subHours($settings->min_advance_hours);

        return response()->json([
            'slot' => $data['slot'],
            'count_available' => $available,
            'last_online_cancellable_time' => $lastCancellableTime->toIso8601String(),
            'duration_requirement' => 'DO_NOT_SHOW_DURATION',
        ]);
    }

    /**
     * Batch availability lookup
     * Called by Google to get availability for multiple slots
     *
     * POST /api/google-reserve/v3/BatchAvailabilityLookup
     */
    public function batchAvailabilityLookup(Request $request)
    {
        // Verify webhook signature (SECURITY)
        $merchantId = $request->input('merchant_id');
        if ($merchantId) {
            $settings = GoogleReserveSettings::where('merchant_id', $merchantId)->first();
            if ($settings && $settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
                Log::warning('Google Reserve invalid signature', ['merchant_id' => $merchantId]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $data = $request->validate([
            'merchant_id' => 'required|string',
            'slot_time_range' => 'required|array',
            'slot_time_range.start_time' => 'required|string',
            'slot_time_range.end_time' => 'required|string',
        ]);

        $merchantId = $data['merchant_id'];
        $startTime = Carbon::parse($data['slot_time_range']['start_time']);
        $endTime = Carbon::parse($data['slot_time_range']['end_time']);

        $settings = GoogleReserveSettings::where('merchant_id', $merchantId)
            ->where('is_enabled', true)
            ->first();

        if (!$settings) {
            return response()->json([
                'slot_time_availability' => [],
            ]);
        }

        // Get all slots in the date range
        $slots = GoogleReserveSlot::where('tenant_id', $settings->tenant_id)
            ->where('site_id', $settings->site_id)
            ->whereBetween('date', [$startTime->toDateString(), $endTime->toDateString()])
            ->where('is_available', true)
            ->where('is_blocked', false)
            ->get();

        $availability = $slots->map(function ($slot) use ($merchantId, $settings) {
            $slotStart = Carbon::parse($slot->date->format('Y-m-d') . ' ' . $slot->start_time);
            $slotEnd = Carbon::parse($slot->date->format('Y-m-d') . ' ' . $slot->end_time);
            $available = max(0, $slot->max_bookings - $slot->current_bookings);

            return [
                'slot' => [
                    'merchant_id' => $merchantId,
                    'service_id' => 'visit',
                    'start_time' => $slotStart->toIso8601String(),
                    'end_time' => $slotEnd->toIso8601String(),
                ],
                'count_available' => $available,
            ];
        });

        return response()->json([
            'slot_time_availability' => $availability->toArray(),
        ]);
    }

    /**
     * Create a new booking
     * Called by Google when a user makes a reservation
     *
     * POST /api/google-reserve/v3/CreateBooking
     */
    public function createBooking(Request $request)
    {
        // Verify webhook signature (SECURITY - CRITICAL for booking creation)
        $merchantId = $request->input('slot.merchant_id');
        if ($merchantId) {
            $settings = GoogleReserveSettings::where('merchant_id', $merchantId)->first();
            if ($settings && $settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
                Log::warning('Google Reserve invalid signature on createBooking', ['merchant_id' => $merchantId]);
                return response()->json([
                    'booking_failure' => [
                        'cause' => 'BOOKING_SYSTEM_ERROR',
                        'description' => 'Authentication failed',
                    ],
                ], 401);
            }
        }

        $data = $request->validate([
            'slot' => 'required|array',
            'slot.merchant_id' => 'required|string',
            'slot.service_id' => 'nullable|string',
            'slot.start_time' => 'required|string',
            'slot.end_time' => 'nullable|string',
            'user_information' => 'required|array',
            'user_information.given_name' => 'required|string',
            'user_information.family_name' => 'required|string',
            'user_information.email' => 'required|email',
            'user_information.telephone' => 'nullable|string',
            'idempotency_token' => 'required|string',
            'additional_request' => 'nullable|string',
        ]);

        $merchantId = $data['slot']['merchant_id'];
        $idempotencyToken = $data['idempotency_token'];

        // Check for duplicate request (idempotency)
        $existingBooking = GoogleReserveBooking::where('google_booking_id', $idempotencyToken)->first();
        if ($existingBooking) {
            return $this->formatBookingResponse($existingBooking);
        }

        $settings = GoogleReserveSettings::where('merchant_id', $merchantId)
            ->where('is_enabled', true)
            ->first();

        if (!$settings) {
            return response()->json([
                'booking_failure' => [
                    'cause' => 'SLOT_UNAVAILABLE',
                    'description' => 'Merchant not found or disabled',
                ],
            ], 400);
        }

        $startTime = Carbon::parse($data['slot']['start_time']);
        $endTime = isset($data['slot']['end_time'])
            ? Carbon::parse($data['slot']['end_time'])
            : $startTime->copy()->addMinutes($settings->slot_duration_minutes);

        // Check slot availability
        $slot = GoogleReserveSlot::where('tenant_id', $settings->tenant_id)
            ->where('site_id', $settings->site_id)
            ->where('date', $startTime->toDateString())
            ->where('start_time', $startTime->format('H:i:s'))
            ->where('is_available', true)
            ->where('is_blocked', false)
            ->first();

        if (!$slot || $slot->current_bookings >= $slot->max_bookings) {
            return response()->json([
                'booking_failure' => [
                    'cause' => 'SLOT_UNAVAILABLE',
                    'description' => 'This time slot is no longer available',
                ],
            ], 400);
        }

        // Create the booking
        $booking = GoogleReserveBooking::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $settings->tenant_id,
            'site_id' => $settings->site_id,
            'google_booking_id' => $idempotencyToken,
            'google_merchant_id' => $merchantId,
            'customer_name' => $data['user_information']['given_name'] . ' ' . $data['user_information']['family_name'],
            'customer_email' => $data['user_information']['email'],
            'customer_phone' => $data['user_information']['telephone'] ?? null,
            'service_type' => $data['slot']['service_id'] ?? 'visit',
            'booking_date' => $startTime->toDateString(),
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
            'customer_notes' => $data['additional_request'] ?? null,
            'status' => $settings->auto_confirm ? 'confirmed' : 'pending',
            'confirmed_at' => $settings->auto_confirm ? now() : null,
            'last_synced_at' => now(),
            'google_raw_data' => $data,
        ]);

        // Increment slot bookings
        $slot->increment('current_bookings');

        // Log the sync
        GoogleReserveSyncLog::create([
            'tenant_id' => $settings->tenant_id,
            'sync_type' => 'bookings',
            'direction' => 'pull',
            'status' => 'success',
            'records_processed' => 1,
            'records_created' => 1,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // TODO: Send notification email to tenant
        // TODO: Send confirmation email to customer if enabled

        Log::info('Google Reserve booking created', [
            'booking_id' => $booking->id,
            'google_booking_id' => $idempotencyToken,
        ]);

        return $this->formatBookingResponse($booking);
    }

    /**
     * Update an existing booking
     * Called by Google when a user modifies or cancels a reservation
     *
     * POST /api/google-reserve/v3/UpdateBooking
     */
    public function updateBooking(Request $request)
    {
        $data = $request->validate([
            'booking' => 'required|array',
            'booking.booking_id' => 'required|string',
            'booking.status' => 'required|string',
            'update_mask' => 'nullable|string',
        ]);

        $googleBookingId = $data['booking']['booking_id'];
        $newStatus = $data['booking']['status'];

        // Find the booking first
        $booking = GoogleReserveBooking::where('google_booking_id', $googleBookingId)->first();

        if (!$booking) {
            return response()->json([
                'booking_failure' => [
                    'cause' => 'BOOKING_NOT_FOUND',
                    'description' => 'Booking not found',
                ],
            ], 404);
        }

        // Verify webhook signature (SECURITY)
        $settings = GoogleReserveSettings::where('tenant_id', $booking->tenant_id)
            ->where('site_id', $booking->site_id)
            ->first();
        if ($settings && $settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
            Log::warning('Google Reserve invalid signature on updateBooking', ['booking_id' => $googleBookingId]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Map Google status to our status
        $statusMap = [
            'CANCELED' => 'cancelled_by_customer',
            'CONFIRMED' => 'confirmed',
            'PENDING' => 'pending',
            'NO_SHOW' => 'no_show',
        ];

        $mappedStatus = $statusMap[$newStatus] ?? $booking->status;

        // If cancelling, decrement slot bookings
        if (in_array($newStatus, ['CANCELED']) && !in_array($booking->status, ['cancelled_by_customer', 'cancelled_by_merchant'])) {
            $slot = GoogleReserveSlot::where('tenant_id', $booking->tenant_id)
                ->where('site_id', $booking->site_id)
                ->where('date', $booking->booking_date)
                ->where('start_time', $booking->start_time)
                ->first();

            if ($slot && $slot->current_bookings > 0) {
                $slot->decrement('current_bookings');
            }

            $booking->cancelled_at = now();
        }

        $booking->update([
            'status' => $mappedStatus,
            'last_synced_at' => now(),
        ]);

        Log::info('Google Reserve booking updated', [
            'booking_id' => $booking->id,
            'google_booking_id' => $googleBookingId,
            'new_status' => $mappedStatus,
        ]);

        return $this->formatBookingResponse($booking);
    }

    /**
     * Get booking details
     * Called by Google to verify booking status
     *
     * POST /api/google-reserve/v3/GetBookingStatus
     */
    public function getBookingStatus(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|string',
        ]);

        $booking = GoogleReserveBooking::where('google_booking_id', $data['booking_id'])->first();

        if (!$booking) {
            return response()->json([
                'booking_failure' => [
                    'cause' => 'BOOKING_NOT_FOUND',
                    'description' => 'Booking not found',
                ],
            ], 404);
        }

        // Verify webhook signature (SECURITY)
        $settings = GoogleReserveSettings::where('tenant_id', $booking->tenant_id)
            ->where('site_id', $booking->site_id)
            ->first();
        if ($settings && $settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
            Log::warning('Google Reserve invalid signature on getBookingStatus', ['booking_id' => $data['booking_id']]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $this->formatBookingResponse($booking);
    }

    /**
     * List bookings (for reconciliation)
     *
     * POST /api/google-reserve/v3/ListBookings
     */
    public function listBookings(Request $request)
    {
        $data = $request->validate([
            'merchant_id' => 'required|string',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
        ]);

        $settings = GoogleReserveSettings::where('merchant_id', $data['merchant_id'])->first();

        if (!$settings) {
            return response()->json(['bookings' => []]);
        }

        // Verify webhook signature (SECURITY)
        if ($settings->webhook_secret && !$this->verifySignature($request, $settings->webhook_secret)) {
            Log::warning('Google Reserve invalid signature on listBookings', ['merchant_id' => $data['merchant_id']]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $query = GoogleReserveBooking::where('tenant_id', $settings->tenant_id)
            ->where('site_id', $settings->site_id);

        if (isset($data['start_time'])) {
            $query->where('booking_date', '>=', Carbon::parse($data['start_time'])->toDateString());
        }

        if (isset($data['end_time'])) {
            $query->where('booking_date', '<=', Carbon::parse($data['end_time'])->toDateString());
        }

        $bookings = $query->get()->map(fn($b) => $this->formatBookingData($b));

        return response()->json([
            'bookings' => $bookings->toArray(),
        ]);
    }

    /**
     * Format booking response for Google
     */
    protected function formatBookingResponse(GoogleReserveBooking $booking)
    {
        return response()->json([
            'booking' => $this->formatBookingData($booking),
        ]);
    }

    /**
     * Format booking data for Google API
     */
    protected function formatBookingData(GoogleReserveBooking $booking): array
    {
        $statusMap = [
            'pending' => 'PENDING',
            'confirmed' => 'CONFIRMED',
            'completed' => 'CONFIRMED',
            'converted' => 'CONFIRMED',
            'cancelled_by_customer' => 'CANCELED',
            'cancelled_by_merchant' => 'CANCELED',
            'no_show' => 'NO_SHOW',
        ];

        $startDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
        $endDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->end_time);

        return [
            'booking_id' => $booking->google_booking_id,
            'slot' => [
                'merchant_id' => $booking->google_merchant_id,
                'service_id' => $booking->service_type,
                'start_time' => $startDateTime->toIso8601String(),
                'end_time' => $endDateTime->toIso8601String(),
            ],
            'user_information' => [
                'given_name' => explode(' ', $booking->customer_name)[0] ?? '',
                'family_name' => explode(' ', $booking->customer_name)[1] ?? '',
                'email' => $booking->customer_email,
                'telephone' => $booking->customer_phone,
            ],
            'status' => $statusMap[$booking->status] ?? 'PENDING',
            'payment_information' => [
                'prepayment_status' => 'PREPAYMENT_NOT_PROVIDED',
            ],
        ];
    }
}
