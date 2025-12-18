<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityController extends Controller
{
    /**
     * Get availability calendar for a site
     * Returns availability data for each day in the requested period
     */
    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'box_type' => 'nullable|string',
            'size_min' => 'nullable|numeric|min:0',
            'size_max' => 'nullable|numeric|min:0',
        ]);

        $site = Site::findOrFail($validated['site_id']);
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Get all boxes for this site with optional filters
        $boxesQuery = Box::where('site_id', $site->id)
            ->where('status', '!=', 'maintenance');

        if (!empty($validated['box_type'])) {
            $boxesQuery->where('type', $validated['box_type']);
        }

        if (!empty($validated['size_min'])) {
            $boxesQuery->where('size', '>=', $validated['size_min']);
        }

        if (!empty($validated['size_max'])) {
            $boxesQuery->where('size', '<=', $validated['size_max']);
        }

        $boxes = $boxesQuery->get();
        $totalBoxes = $boxes->count();

        if ($totalBoxes === 0) {
            return response()->json([
                'calendar' => [],
                'summary' => [
                    'total_boxes' => 0,
                    'average_availability' => 0,
                ],
            ]);
        }

        // Get all active contracts and bookings for this period
        $contracts = Contract::whereIn('box_id', $boxes->pluck('id'))
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where(function ($q2) use ($startDate) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $startDate);
                      });
                });
            })
            ->get();

        $bookings = Booking::whereIn('box_id', $boxes->pluck('id'))
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        // Build calendar data
        $period = CarbonPeriod::create($startDate, $endDate);
        $calendar = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            // Count occupied boxes on this date
            $occupiedByContract = $contracts->filter(function ($contract) use ($date) {
                $start = Carbon::parse($contract->start_date);
                $end = $contract->end_date ? Carbon::parse($contract->end_date) : null;
                return $start <= $date && (!$end || $end >= $date);
            })->pluck('box_id')->unique();

            $occupiedByBooking = $bookings->filter(function ($booking) use ($date) {
                $start = Carbon::parse($booking->start_date);
                $end = Carbon::parse($booking->end_date);
                return $start <= $date && $end >= $date;
            })->pluck('box_id')->unique();

            $occupiedBoxIds = $occupiedByContract->merge($occupiedByBooking)->unique();
            $occupiedCount = $occupiedBoxIds->count();
            $availableCount = $totalBoxes - $occupiedCount;
            $occupancyRate = $totalBoxes > 0 ? round(($occupiedCount / $totalBoxes) * 100) : 0;

            // Determine availability status
            $status = 'available';
            if ($availableCount === 0) {
                $status = 'full';
            } elseif ($availableCount <= 3) {
                $status = 'limited';
            }

            $calendar[] = [
                'date' => $dateStr,
                'day' => $date->format('j'),
                'day_name' => $date->translatedFormat('D'),
                'is_weekend' => $date->isWeekend(),
                'is_today' => $date->isToday(),
                'available' => $availableCount,
                'occupied' => $occupiedCount,
                'total' => $totalBoxes,
                'occupancy_rate' => $occupancyRate,
                'status' => $status,
            ];
        }

        // Calculate summary
        $avgAvailability = collect($calendar)->avg('available');

        return response()->json([
            'calendar' => $calendar,
            'summary' => [
                'site_name' => $site->name,
                'total_boxes' => $totalBoxes,
                'average_availability' => round($avgAvailability, 1),
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Get available boxes for a specific date
     */
    public function availableBoxes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'date' => 'required|date',
            'duration_months' => 'nullable|integer|min:1|max:36',
            'box_type' => 'nullable|string',
            'size_min' => 'nullable|numeric|min:0',
            'size_max' => 'nullable|numeric|min:0',
        ]);

        $site = Site::findOrFail($validated['site_id']);
        $startDate = Carbon::parse($validated['date']);
        $endDate = $startDate->copy()->addMonths($validated['duration_months'] ?? 1);

        // Get boxes with filters
        $boxesQuery = Box::where('site_id', $site->id)
            ->where('status', 'available');

        if (!empty($validated['box_type'])) {
            $boxesQuery->where('type', $validated['box_type']);
        }

        if (!empty($validated['size_min'])) {
            $boxesQuery->where('size', '>=', $validated['size_min']);
        }

        if (!empty($validated['size_max'])) {
            $boxesQuery->where('size', '<=', $validated['size_max']);
        }

        $boxes = $boxesQuery->get();

        // Get occupied box IDs for the period
        $contractOccupiedIds = Contract::whereIn('box_id', $boxes->pluck('id'))
            ->where('status', 'active')
            ->where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $startDate);
            })
            ->pluck('box_id');

        $bookingOccupiedIds = Booking::whereIn('box_id', $boxes->pluck('id'))
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->pluck('box_id');

        $occupiedIds = $contractOccupiedIds->merge($bookingOccupiedIds)->unique();

        // Filter available boxes
        $availableBoxes = $boxes->filter(function ($box) use ($occupiedIds) {
            return !$occupiedIds->contains($box->id);
        })->map(function ($box) {
            return [
                'id' => $box->id,
                'code' => $box->number,
                'name' => $box->name,
                'type' => $box->type,
                'size' => $box->size,
                'price' => $box->price,
                'floor' => $box->floor?->name,
                'building' => $box->floor?->building?->name,
                'features' => $box->features ?? [],
                'images' => $box->images ?? [],
            ];
        })->values();

        return response()->json([
            'boxes' => $availableBoxes,
            'count' => $availableBoxes->count(),
            'filters_applied' => [
                'date' => $validated['date'],
                'duration_months' => $validated['duration_months'] ?? 1,
                'box_type' => $validated['box_type'] ?? null,
                'size_range' => [
                    'min' => $validated['size_min'] ?? null,
                    'max' => $validated['size_max'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Get box types and sizes available at a site
     */
    public function boxTypes(Request $request, $siteId): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        $types = Box::where('site_id', $site->id)
            ->where('status', '!=', 'maintenance')
            ->selectRaw('type, MIN(size) as min_size, MAX(size) as max_size, MIN(price) as min_price, MAX(price) as max_price, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'label' => $this->getTypeLabel($item->type),
                    'size_range' => "{$item->min_size} - {$item->max_size} m²",
                    'price_range' => "{$item->min_price}€ - {$item->max_price}€",
                    'count' => $item->count,
                ];
            });

        $sizes = Box::where('site_id', $site->id)
            ->where('status', '!=', 'maintenance')
            ->selectRaw('size, COUNT(*) as count, MIN(price) as price')
            ->groupBy('size')
            ->orderBy('size')
            ->get()
            ->map(function ($item) {
                return [
                    'size' => $item->size,
                    'label' => "{$item->size} m²",
                    'count' => $item->count,
                    'starting_price' => $item->price,
                ];
            });

        return response()->json([
            'types' => $types,
            'sizes' => $sizes,
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
            ],
        ]);
    }

    /**
     * Get type label in French
     */
    private function getTypeLabel(string $type): string
    {
        return match($type) {
            'small' => 'Petit (1-4 m²)',
            'medium' => 'Moyen (5-10 m²)',
            'large' => 'Grand (11-20 m²)',
            'extra_large' => 'Très grand (20+ m²)',
            'locker' => 'Casier',
            'parking' => 'Parking',
            'wine' => 'Cave à vin',
            default => ucfirst($type),
        };
    }
}
