<?php

namespace App\Services;

use App\Models\GoogleReserveBooking;
use App\Models\GoogleReserveSettings;
use App\Models\GoogleReserveSlot;
use App\Models\Site;
use App\Models\Customer;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleReserveService
{
    /**
     * Get or create settings for a tenant/site
     */
    public function getSettings(int $tenantId, ?int $siteId = null): GoogleReserveSettings
    {
        return GoogleReserveSettings::firstOrCreate(
            ['tenant_id' => $tenantId, 'site_id' => $siteId],
            [
                'is_enabled' => false,
                'available_days' => [1, 2, 3, 4, 5, 6], // Mon-Sat
                'opening_time' => '09:00',
                'closing_time' => '18:00',
            ]
        );
    }

    /**
     * Update settings
     */
    public function updateSettings(int $tenantId, ?int $siteId, array $data): GoogleReserveSettings
    {
        $settings = $this->getSettings($tenantId, $siteId);
        $settings->update($data);
        return $settings->fresh();
    }

    /**
     * Generate availability slots for a date range
     */
    public function generateSlots(int $tenantId, int $siteId, Carbon $startDate, Carbon $endDate): int
    {
        $settings = $this->getSettings($tenantId, $siteId);

        if (!$settings->is_enabled) {
            return 0;
        }

        $availableDays = $settings->available_days ?? [1, 2, 3, 4, 5, 6];
        $openingTime = Carbon::parse($settings->opening_time);
        $closingTime = Carbon::parse($settings->closing_time);
        $slotDuration = $settings->slot_duration_minutes ?? 30;

        $slotsCreated = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Check if day is available
            if (in_array($currentDate->dayOfWeek, $availableDays)) {
                $slotStart = $currentDate->copy()->setTimeFrom($openingTime);
                $dayEnd = $currentDate->copy()->setTimeFrom($closingTime);

                while ($slotStart->copy()->addMinutes($slotDuration) <= $dayEnd) {
                    $slotEnd = $slotStart->copy()->addMinutes($slotDuration);

                    GoogleReserveSlot::firstOrCreate(
                        [
                            'site_id' => $siteId,
                            'date' => $currentDate->toDateString(),
                            'start_time' => $slotStart->format('H:i:s'),
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'end_time' => $slotEnd->format('H:i:s'),
                            'max_bookings' => 1,
                            'is_available' => true,
                        ]
                    );

                    $slotsCreated++;
                    $slotStart = $slotEnd;
                }
            }

            $currentDate->addDay();
        }

        return $slotsCreated;
    }

    /**
     * Get available slots for a date
     */
    public function getAvailableSlots(int $siteId, Carbon $date): Collection
    {
        return GoogleReserveSlot::where('site_id', $siteId)
            ->where('date', $date->toDateString())
            ->where('is_available', true)
            ->where('is_blocked', false)
            ->where('current_bookings', '<', DB::raw('max_bookings'))
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Create a booking from Google Reserve webhook
     */
    public function createBooking(array $data): GoogleReserveBooking
    {
        return DB::transaction(function () use ($data) {
            $booking = GoogleReserveBooking::create([
                'tenant_id' => $data['tenant_id'],
                'site_id' => $data['site_id'],
                'google_booking_id' => $data['google_booking_id'],
                'google_merchant_id' => $data['google_merchant_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'service_type' => $data['service_type'] ?? 'visit',
                'box_size_requested' => $data['box_size_requested'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'status' => 'pending',
                'google_raw_data' => $data['raw_data'] ?? null,
            ]);

            // Update slot availability
            GoogleReserveSlot::where('site_id', $data['site_id'])
                ->where('date', $data['booking_date'])
                ->where('start_time', $data['start_time'])
                ->increment('current_bookings');

            // Try to find or create customer
            $customer = Customer::where('email', $data['customer_email'])
                ->where('tenant_id', $data['tenant_id'])
                ->first();

            if ($customer) {
                $booking->update(['customer_id' => $customer->id]);
            }

            // Auto-confirm if enabled
            $settings = $this->getSettings($data['tenant_id'], $data['site_id']);
            if ($settings->auto_confirm) {
                $booking->confirm();
            }

            return $booking;
        });
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(GoogleReserveBooking $booking, string $reason = null, bool $byCustomer = true): bool
    {
        return DB::transaction(function () use ($booking, $reason, $byCustomer) {
            $booking->cancel($reason, $byCustomer);

            // Free up the slot
            GoogleReserveSlot::where('site_id', $booking->site_id)
                ->where('date', $booking->booking_date)
                ->where('start_time', $booking->start_time)
                ->where('current_bookings', '>', 0)
                ->decrement('current_bookings');

            return true;
        });
    }

    /**
     * Convert booking to internal booking/contract
     */
    public function convertToBooking(GoogleReserveBooking $grBooking): ?Booking
    {
        return DB::transaction(function () use ($grBooking) {
            // Find or create customer
            $customer = Customer::firstOrCreate(
                [
                    'email' => $grBooking->customer_email,
                    'tenant_id' => $grBooking->tenant_id,
                ],
                [
                    'first_name' => explode(' ', $grBooking->customer_name)[0] ?? '',
                    'last_name' => explode(' ', $grBooking->customer_name, 2)[1] ?? '',
                    'phone' => $grBooking->customer_phone,
                ]
            );

            // Create internal booking
            $booking = Booking::create([
                'tenant_id' => $grBooking->tenant_id,
                'site_id' => $grBooking->site_id,
                'customer_id' => $customer->id,
                'box_id' => $grBooking->box_id,
                'status' => 'confirmed',
                'source' => 'google_reserve',
                'start_date' => $grBooking->booking_date,
                'notes' => "Google Reserve: {$grBooking->google_booking_id}\n{$grBooking->customer_notes}",
            ]);

            // Link back
            $grBooking->update([
                'customer_id' => $customer->id,
                'booking_id' => $booking->id,
            ]);

            return $booking;
        });
    }

    /**
     * Get bookings for calendar view
     */
    public function getBookingsForCalendar(int $tenantId, ?int $siteId, Carbon $startDate, Carbon $endDate): Collection
    {
        return GoogleReserveBooking::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled_by_customer', 'cancelled_by_merchant'])
            ->with(['site:id,name', 'customer:id,first_name,last_name'])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $tenantId, ?int $siteId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = GoogleReserveBooking::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->when($startDate, fn($q) => $q->where('booking_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('booking_date', '<=', $endDate));

        $total = $query->count();
        $confirmed = (clone $query)->where('status', 'confirmed')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $converted = (clone $query)->where('status', 'converted')->count();
        $cancelled = (clone $query)->whereIn('status', ['cancelled_by_customer', 'cancelled_by_merchant'])->count();
        $noShow = (clone $query)->where('status', 'no_show')->count();

        $totalValue = (clone $query)->where('status', 'converted')->sum('converted_value');

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'completed' => $completed,
            'converted' => $converted,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
            'conversion_rate' => $completed > 0 ? round(($converted / $completed) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
            'total_value' => $totalValue,
        ];
    }

    /**
     * Get upcoming bookings
     */
    public function getUpcomingBookings(int $tenantId, ?int $siteId = null, int $limit = 10): Collection
    {
        return GoogleReserveBooking::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->upcoming()
            ->with(['site:id,name', 'customer:id,first_name,last_name'])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Get today's bookings
     */
    public function getTodayBookings(int $tenantId, ?int $siteId = null): Collection
    {
        return GoogleReserveBooking::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->today()
            ->with(['site:id,name', 'customer:id,first_name,last_name'])
            ->orderBy('start_time')
            ->get();
    }
}
