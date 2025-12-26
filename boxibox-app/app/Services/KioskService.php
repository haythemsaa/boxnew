<?php

namespace App\Services;

use App\Models\KioskDevice;
use App\Models\KioskSession;
use App\Models\KioskActionLog;
use App\Models\KioskAnalytics;
use App\Models\KioskIssue;
use App\Models\Box;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KioskService
{
    /**
     * Get all kiosks for a tenant
     */
    public function getKiosks(int $tenantId): Collection
    {
        return KioskDevice::where('tenant_id', $tenantId)
            ->with('site:id,name')
            ->withCount('sessions')
            ->get()
            ->map(function ($kiosk) {
                $kiosk->computed_status = $kiosk->status;
                $kiosk->today_stats = $kiosk->getTodayStats();
                return $kiosk;
            });
    }

    /**
     * Create a new kiosk
     */
    public function createKiosk(int $tenantId, array $data): KioskDevice
    {
        return KioskDevice::create(array_merge($data, [
            'tenant_id' => $tenantId,
        ]));
    }

    /**
     * Update kiosk configuration
     */
    public function updateKiosk(KioskDevice $kiosk, array $data): KioskDevice
    {
        $kiosk->update($data);
        return $kiosk->fresh();
    }

    /**
     * Pair a kiosk device using code
     */
    public function pairDevice(string $deviceCode, array $deviceInfo): ?KioskDevice
    {
        $kiosk = KioskDevice::where('device_code', $deviceCode)->first();

        if (!$kiosk) {
            return null;
        }

        $kiosk->update([
            'device_type' => $deviceInfo['device_type'] ?? 'tablet',
            'os' => $deviceInfo['os'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'screen_resolution' => $deviceInfo['screen_resolution'] ?? null,
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'current_ip' => $deviceInfo['ip'] ?? null,
        ]);

        // Generate new code for security
        $kiosk->generateNewCode();

        return $kiosk;
    }

    /**
     * Start a new kiosk session
     */
    public function startSession(KioskDevice $kiosk, string $purpose = 'browse', ?array $metadata = null): KioskSession
    {
        return KioskSession::create([
            'kiosk_id' => $kiosk->id,
            'tenant_id' => $kiosk->tenant_id,
            'site_id' => $kiosk->site_id,
            'session_token' => Str::random(64),
            'started_at' => now(),
            'purpose' => $purpose,
            'ip_address' => $metadata['ip'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
        ]);
    }

    /**
     * End a kiosk session
     */
    public function endSession(KioskSession $session, string $outcome = 'completed'): KioskSession
    {
        $session->update([
            'ended_at' => now(),
            'duration_seconds' => $session->started_at->diffInSeconds(now()),
            'outcome' => $outcome,
        ]);

        return $session->fresh();
    }

    /**
     * Log an action in the session
     */
    public function logAction(KioskSession $session, string $action, ?string $page = null, ?array $data = null): KioskActionLog
    {
        // Calculate time on previous page
        $lastAction = KioskActionLog::where('session_id', $session->id)
            ->orderByDesc('created_at')
            ->first();

        $timeOnPrevious = $lastAction
            ? $lastAction->created_at->diffInSeconds(now())
            : null;

        return KioskActionLog::create([
            'session_id' => $session->id,
            'kiosk_id' => $session->kiosk_id,
            'action' => $action,
            'page' => $page,
            'data' => $data,
            'time_on_previous_page_seconds' => $timeOnPrevious,
        ]);
    }

    /**
     * Update session with transaction info
     */
    public function recordTransaction(KioskSession $session, string $type, int $entityId, float $amount): void
    {
        $updateData = ['transaction_amount' => $amount];

        switch ($type) {
            case 'booking':
                $updateData['started_rental'] = true;
                $updateData['created_booking_id'] = $entityId;
                break;
            case 'contract':
                $updateData['completed_rental'] = true;
                $updateData['created_contract_id'] = $entityId;
                break;
            case 'payment':
                $updateData['made_payment'] = true;
                $updateData['created_payment_id'] = $entityId;
                break;
        }

        $session->update($updateData);
    }

    /**
     * Get available boxes for kiosk display
     */
    public function getAvailableBoxes(int $siteId, ?array $filters = null): Collection
    {
        return Box::where('site_id', $siteId)
            ->where('status', 'available')
            ->when($filters['min_volume'] ?? null, fn($q, $v) => $q->where('volume', '>=', $v))
            ->when($filters['max_volume'] ?? null, fn($q, $v) => $q->where('volume', '<=', $v))
            ->when($filters['max_price'] ?? null, fn($q, $p) => $q->where('current_price', '<=', $p))
            ->orderBy('volume')
            ->get(['id', 'number', 'volume', 'length', 'width', 'height', 'current_price', 'base_price', 'features']);
    }

    /**
     * Report a kiosk issue
     */
    public function reportIssue(KioskDevice $kiosk, array $data): KioskIssue
    {
        return KioskIssue::create([
            'kiosk_id' => $kiosk->id,
            'tenant_id' => $kiosk->tenant_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'open',
            'reported_by' => $data['reported_by'] ?? null,
        ]);
    }

    /**
     * Get open issues
     */
    public function getOpenIssues(int $tenantId): Collection
    {
        return KioskIssue::where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'in_progress'])
            ->with('kiosk:id,name,site_id')
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Resolve an issue
     */
    public function resolveIssue(KioskIssue $issue, int $resolvedBy, ?string $notes = null): KioskIssue
    {
        $issue->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $notes,
        ]);

        return $issue->fresh();
    }

    /**
     * Get kiosk statistics
     */
    public function getStatistics(int $tenantId, ?int $kioskId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = KioskSession::where('tenant_id', $tenantId)
            ->when($kioskId, fn($q, $k) => $q->where('kiosk_id', $k))
            ->when($startDate, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('started_at', '<=', $d));

        $totalSessions = $query->count();
        $completedSessions = (clone $query)->where('outcome', 'completed')->count();
        $abandonedSessions = (clone $query)->where('outcome', 'abandoned')->count();

        $rentalSessions = (clone $query)->where('purpose', 'new_rental')->count();
        $completedRentals = (clone $query)->where('completed_rental', true)->count();
        $payments = (clone $query)->where('made_payment', true)->count();
        $accessCodes = (clone $query)->where('generated_access_code', true)->count();

        $avgDuration = (clone $query)->whereNotNull('duration_seconds')->avg('duration_seconds');
        $totalRevenue = (clone $query)->sum('transaction_amount');

        return [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'abandoned_sessions' => $abandonedSessions,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 2) : 0,
            'rental_sessions' => $rentalSessions,
            'completed_rentals' => $completedRentals,
            'rental_conversion_rate' => $rentalSessions > 0 ? round(($completedRentals / $rentalSessions) * 100, 2) : 0,
            'payments' => $payments,
            'access_codes' => $accessCodes,
            'avg_duration' => round($avgDuration ?? 0),
            'total_revenue' => $totalRevenue ?? 0,
        ];
    }

    /**
     * Get session journey/funnel
     */
    public function getSessionFunnel(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = KioskSession::where('tenant_id', $tenantId)
            ->where('purpose', 'new_rental')
            ->when($startDate, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('started_at', '<=', $d));

        $total = $query->count();
        $viewedBoxes = (clone $query)->where('viewed_boxes', true)->count();
        $startedRental = (clone $query)->where('started_rental', true)->count();
        $completedRental = (clone $query)->where('completed_rental', true)->count();

        return [
            ['step' => 'Sessions', 'count' => $total, 'rate' => 100],
            ['step' => 'Viewed Boxes', 'count' => $viewedBoxes, 'rate' => $total > 0 ? round(($viewedBoxes / $total) * 100, 1) : 0],
            ['step' => 'Started Rental', 'count' => $startedRental, 'rate' => $total > 0 ? round(($startedRental / $total) * 100, 1) : 0],
            ['step' => 'Completed Rental', 'count' => $completedRental, 'rate' => $total > 0 ? round(($completedRental / $total) * 100, 1) : 0],
        ];
    }

    /**
     * Record daily analytics
     */
    public function recordDailyAnalytics(KioskDevice $kiosk, Carbon $date): KioskAnalytics
    {
        $stats = $this->getStatistics($kiosk->tenant_id, $kiosk->id, $date, $date);

        // Get hourly distribution
        $hourlyData = KioskSession::where('kiosk_id', $kiosk->id)
            ->whereDate('started_at', $date)
            ->selectRaw('HOUR(started_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourlyDistribution = array_fill(0, 24, 0);
        foreach ($hourlyData as $hour => $count) {
            $hourlyDistribution[$hour] = $count;
        }

        return KioskAnalytics::updateOrCreate(
            [
                'kiosk_id' => $kiosk->id,
                'date' => $date->toDateString(),
            ],
            [
                'tenant_id' => $kiosk->tenant_id,
                'total_sessions' => $stats['total_sessions'],
                'completed_sessions' => $stats['completed_sessions'],
                'abandoned_sessions' => $stats['abandoned_sessions'],
                'timeout_sessions' => 0, // Would need to track separately
                'browse_sessions' => 0,
                'rental_sessions' => $stats['rental_sessions'],
                'payment_sessions' => $stats['payments'],
                'access_code_sessions' => $stats['access_codes'],
                'rentals_started' => $stats['rental_sessions'],
                'rentals_completed' => $stats['completed_rentals'],
                'rental_conversion_rate' => $stats['rental_conversion_rate'],
                'payments_completed' => $stats['payments'],
                'access_codes_generated' => $stats['access_codes'],
                'total_revenue' => $stats['total_revenue'],
                'avg_session_duration_seconds' => $stats['avg_duration'],
                'hourly_sessions' => $hourlyDistribution,
            ]
        );
    }

    /**
     * Get kiosk configuration for frontend
     */
    public function getKioskConfig(KioskDevice $kiosk): array
    {
        return [
            'id' => $kiosk->id,
            'uuid' => $kiosk->uuid,
            'name' => $kiosk->name,
            'site_id' => $kiosk->site_id,
            'site_name' => $kiosk->site->name,
            'language' => $kiosk->language,
            'features' => [
                'new_rentals' => $kiosk->allow_new_rentals,
                'payments' => $kiosk->allow_payments,
                'access_codes' => $kiosk->allow_access_code_generation,
                'show_prices' => $kiosk->show_prices,
                'id_verification' => $kiosk->require_id_verification,
            ],
            'branding' => [
                'logo' => $kiosk->logo_path,
                'background' => $kiosk->background_image_path,
                'primary_color' => $kiosk->primary_color,
                'secondary_color' => $kiosk->secondary_color,
                'welcome_message' => $kiosk->welcome_message,
            ],
            'idle' => [
                'timeout' => $kiosk->idle_timeout_seconds,
                'screensaver' => $kiosk->enable_screensaver,
                'images' => $kiosk->screensaver_images,
            ],
        ];
    }
}
