<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Booking;
use App\Models\WaitlistEntry;
use App\Models\WaitlistNotification;
use App\Models\WaitlistSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WaitlistService
{
    /**
     * Add a customer to the waitlist
     */
    public function addToWaitlist(array $data): WaitlistEntry
    {
        $tenantId = $data['tenant_id'];
        $siteId = $data['site_id'];

        // Check if waitlist is enabled
        if (!WaitlistSettings::isEnabled($tenantId, $siteId)) {
            throw new \Exception('La liste d\'attente n\'est pas activée pour ce site.');
        }

        // Check for existing active entry
        $existingEntry = WaitlistEntry::where('tenant_id', $tenantId)
            ->where('site_id', $siteId)
            ->where('customer_email', $data['customer_email'])
            ->whereIn('status', ['active', 'notified'])
            ->first();

        if ($existingEntry) {
            // Update existing entry
            $existingEntry->update($data);
            return $existingEntry;
        }

        // Check max entries per box if specific box requested
        if (!empty($data['box_id'])) {
            $settings = WaitlistSettings::getForSite($tenantId, $siteId);
            $maxEntries = $settings?->max_entries_per_box ?? 10;

            $currentCount = WaitlistEntry::where('box_id', $data['box_id'])
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $maxEntries) {
                throw new \Exception('La liste d\'attente pour ce box est complète.');
            }
        }

        return WaitlistEntry::create($data);
    }

    /**
     * Process waitlist when a box becomes available
     */
    public function processBoxAvailable(Box $box): array
    {
        $results = [
            'notified' => 0,
            'entries' => [],
        ];

        $settings = WaitlistSettings::getForSite($box->tenant_id, $box->site_id);

        if (!$settings?->is_enabled || !$settings?->auto_notify) {
            return $results;
        }

        // Find matching waitlist entries
        $entries = WaitlistEntry::matchingBox($box)
            ->take($settings->max_entries_per_box)
            ->get();

        foreach ($entries as $entry) {
            try {
                $this->notifyEntry($entry, $box, $settings);
                $results['notified']++;
                $results['entries'][] = $entry->id;
            } catch (\Exception $e) {
                Log::error('Failed to notify waitlist entry', [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Notify a waitlist entry about an available box
     */
    public function notifyEntry(WaitlistEntry $entry, Box $box, ?WaitlistSettings $settings = null): WaitlistNotification
    {
        $settings = $settings ?? WaitlistSettings::getForSite($entry->tenant_id, $entry->site_id);

        // Check max notifications
        $notificationCount = $entry->notifications()->count();
        if ($notificationCount >= ($settings?->max_notifications_per_entry ?? 3)) {
            throw new \Exception('Maximum de notifications atteint pour cette entrée.');
        }

        // Create notification record
        $notification = WaitlistNotification::create([
            'waitlist_entry_id' => $entry->id,
            'box_id' => $box->id,
            'channel' => 'email',
            'status' => 'pending',
            'expires_at' => now()->addHours($settings?->notification_expiry_hours ?? 48),
        ]);

        // Send email notification
        try {
            $this->sendNotificationEmail($entry, $box, $notification, $settings);
            $notification->markSent();
            $entry->notify();
        } catch (\Exception $e) {
            $notification->markFailed();
            throw $e;
        }

        return $notification;
    }

    /**
     * Send notification email
     */
    protected function sendNotificationEmail(
        WaitlistEntry $entry,
        Box $box,
        WaitlistNotification $notification,
        ?WaitlistSettings $settings
    ): void {
        $site = $box->site;
        $tenant = $entry->tenant;

        $data = [
            'customer_name' => $entry->customer_full_name,
            'box_name' => $box->display_name,
            'box_size' => $box->size_m2,
            'box_price' => number_format($box->current_price, 2, ',', ' '),
            'site_name' => $site->name,
            'company_name' => $tenant->name ?? 'BoxiBox',
            'expiry_hours' => $settings?->notification_expiry_hours ?? 48,
            'booking_url' => route('booking.checkout', [
                'site' => $site->slug ?? $site->id,
                'box' => $box->id,
                'waitlist' => $entry->uuid,
            ]),
            'unsubscribe_url' => route('waitlist.unsubscribe', ['token' => $entry->uuid]),
        ];

        // Use template from settings or default
        $template = $settings?->getDefaultEmailTemplate() ?? '';

        // Replace placeholders
        $content = preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $template);

        // Send email using notification service or Mail facade
        Mail::html($content, function ($message) use ($entry, $tenant) {
            $message->to($entry->customer_email, $entry->customer_full_name)
                ->subject("Un box est disponible - {$tenant->name}")
                ->from(config('mail.from.address'), $tenant->name ?? 'BoxiBox');
        });
    }

    /**
     * Convert waitlist entry to booking
     */
    public function convertToBooking(WaitlistEntry $entry, Box $box): Booking
    {
        // Create booking
        $booking = Booking::create([
            'tenant_id' => $entry->tenant_id,
            'site_id' => $entry->site_id,
            'box_id' => $box->id,
            'customer_id' => $entry->customer_id,
            'customer_first_name' => $entry->customer_first_name,
            'customer_last_name' => $entry->customer_last_name,
            'customer_email' => $entry->customer_email,
            'customer_phone' => $entry->customer_phone,
            'start_date' => $entry->desired_start_date ?? now()->addDays(1),
            'monthly_price' => $box->current_price,
            'status' => 'pending',
            'source' => 'waitlist',
            'notes' => "Converti depuis liste d'attente #{$entry->id}",
        ]);

        // Update entry
        $entry->convert($booking);

        // Update notification if exists
        $entry->notifications()
            ->where('box_id', $box->id)
            ->where('status', 'clicked')
            ->first()
            ?->markConverted();

        return $booking;
    }

    /**
     * Expire old notifications
     */
    public function expireOldNotifications(): int
    {
        $expired = 0;

        $entries = WaitlistEntry::where('status', 'notified')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($entries as $entry) {
            $entry->expire();
            $expired++;

            // Optionally move to next person in queue
            $nextEntry = WaitlistEntry::where('tenant_id', $entry->tenant_id)
                ->where('site_id', $entry->site_id)
                ->where('status', 'active')
                ->orderBy('priority', 'desc')
                ->orderBy('position', 'asc')
                ->first();

            if ($nextEntry && $entry->box_id) {
                $box = Box::find($entry->box_id);
                if ($box && $box->status === 'available') {
                    try {
                        $this->notifyEntry($nextEntry, $box);
                    } catch (\Exception $e) {
                        Log::error('Failed to notify next waitlist entry', [
                            'entry_id' => $nextEntry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $expired;
    }

    /**
     * Get waitlist statistics for a site
     */
    public function getStats(int $tenantId, ?int $siteId = null): array
    {
        $query = WaitlistEntry::where('tenant_id', $tenantId);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return [
            'total_active' => (clone $query)->where('status', 'active')->count(),
            'total_notified' => (clone $query)->where('status', 'notified')->count(),
            'total_converted' => (clone $query)->where('status', 'converted')->count(),
            'total_expired' => (clone $query)->where('status', 'expired')->count(),
            'conversion_rate' => $this->calculateConversionRate($tenantId, $siteId),
            'avg_wait_time_days' => $this->calculateAvgWaitTime($tenantId, $siteId),
            'popular_sizes' => $this->getPopularSizes($tenantId, $siteId),
        ];
    }

    protected function calculateConversionRate(int $tenantId, ?int $siteId): float
    {
        $query = WaitlistEntry::where('tenant_id', $tenantId);
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $total = (clone $query)->whereIn('status', ['converted', 'expired', 'cancelled'])->count();
        $converted = (clone $query)->where('status', 'converted')->count();

        return $total > 0 ? round(($converted / $total) * 100, 2) : 0;
    }

    protected function calculateAvgWaitTime(int $tenantId, ?int $siteId): float
    {
        $query = WaitlistEntry::where('tenant_id', $tenantId)
            ->where('status', 'converted')
            ->whereNotNull('converted_booking_id');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $totalDays = $entries->sum(function ($entry) {
            return $entry->created_at->diffInDays($entry->updated_at);
        });

        return round($totalDays / $entries->count(), 1);
    }

    protected function getPopularSizes(int $tenantId, ?int $siteId): Collection
    {
        $query = WaitlistEntry::where('tenant_id', $tenantId)
            ->where('status', 'active');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return $query->selectRaw('
                CASE
                    WHEN min_size <= 5 THEN "1-5 m²"
                    WHEN min_size <= 10 THEN "5-10 m²"
                    WHEN min_size <= 20 THEN "10-20 m²"
                    ELSE "20+ m²"
                END as size_range,
                COUNT(*) as count
            ')
            ->groupBy('size_range')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }
}
