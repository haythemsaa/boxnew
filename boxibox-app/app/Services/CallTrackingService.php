<?php

namespace App\Services;

use App\Models\CallTrackingSettings;
use App\Models\TrackingNumber;
use App\Models\CallRecord;
use App\Models\SmsRecord;
use App\Models\CallAnalytics;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallTrackingService
{
    /**
     * Get or create settings for a tenant
     */
    public function getSettings(int $tenantId): CallTrackingSettings
    {
        return CallTrackingSettings::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'is_enabled' => false,
                'provider' => 'twilio',
                'record_calls' => true,
                'notify_missed_calls' => true,
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['09:00', '13:00'],
                    'sunday' => null,
                ],
            ]
        );
    }

    /**
     * Update settings
     */
    public function updateSettings(int $tenantId, array $data): CallTrackingSettings
    {
        $settings = $this->getSettings($tenantId);
        $settings->update($data);
        return $settings->fresh();
    }

    /**
     * Get tracking numbers for a tenant
     */
    public function getTrackingNumbers(int $tenantId): Collection
    {
        return TrackingNumber::where('tenant_id', $tenantId)
            ->with('site:id,name')
            ->withCount('calls')
            ->orderBy('source')
            ->get();
    }

    /**
     * Create a tracking number
     */
    public function createTrackingNumber(int $tenantId, array $data): TrackingNumber
    {
        return TrackingNumber::create(array_merge($data, [
            'tenant_id' => $tenantId,
        ]));
    }

    /**
     * Record an incoming call
     */
    public function recordCall(array $data): CallRecord
    {
        return DB::transaction(function () use ($data) {
            // Find tracking number
            $trackingNumber = TrackingNumber::where('phone_number', $data['to_number'])->first();

            if (!$trackingNumber) {
                throw new \Exception("Unknown tracking number: {$data['to_number']}");
            }

            $call = CallRecord::create([
                'tenant_id' => $trackingNumber->tenant_id,
                'tracking_number_id' => $trackingNumber->id,
                'site_id' => $trackingNumber->site_id,
                'call_sid' => $data['call_sid'] ?? null,
                'from_number' => $data['from_number'],
                'to_number' => $data['to_number'],
                'direction' => $data['direction'] ?? 'inbound',
                'started_at' => $data['started_at'] ?? now(),
                'status' => 'ringing',
                'source' => $trackingNumber->source,
                'medium' => $trackingNumber->medium,
                'campaign' => $trackingNumber->campaign,
                'caller_city' => $data['caller_city'] ?? null,
                'caller_region' => $data['caller_region'] ?? null,
                'caller_country' => $data['caller_country'] ?? null,
                'raw_data' => $data['raw_data'] ?? null,
            ]);

            // Try to match caller to existing customer
            $customer = Customer::where('tenant_id', $trackingNumber->tenant_id)
                ->where('phone', 'LIKE', '%' . substr($data['from_number'], -9))
                ->first();

            if ($customer) {
                $call->update(['customer_id' => $customer->id]);
            }

            // Update tracking number stats
            $trackingNumber->incrementCallCount();

            return $call;
        });
    }

    /**
     * Update call status (answered, completed, etc.)
     */
    public function updateCallStatus(string $callSid, array $data): ?CallRecord
    {
        $call = CallRecord::where('call_sid', $callSid)->first();

        if (!$call) {
            return null;
        }

        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['answered_at'])) {
            $updateData['answered_at'] = $data['answered_at'];
            $updateData['ring_duration_seconds'] = $call->started_at->diffInSeconds($data['answered_at']);
        }

        if (isset($data['ended_at'])) {
            $updateData['ended_at'] = $data['ended_at'];
            $updateData['total_duration_seconds'] = $call->started_at->diffInSeconds($data['ended_at']);

            if ($call->answered_at) {
                $updateData['talk_duration_seconds'] = Carbon::parse($call->answered_at)->diffInSeconds($data['ended_at']);
            }
        }

        if (isset($data['recording_url'])) {
            $updateData['was_recorded'] = true;
            $updateData['recording_url'] = $data['recording_url'];
            $updateData['recording_duration_seconds'] = $data['recording_duration'] ?? null;
        }

        if (isset($data['answered_by'])) {
            $updateData['answered_by'] = $data['answered_by'];
        }

        $call->update($updateData);

        return $call->fresh();
    }

    /**
     * Record an SMS
     */
    public function recordSms(array $data): SmsRecord
    {
        $trackingNumber = null;

        if (isset($data['to_number'])) {
            $trackingNumber = TrackingNumber::where('phone_number', $data['to_number'])->first();
        } elseif (isset($data['from_number'])) {
            $trackingNumber = TrackingNumber::where('phone_number', $data['from_number'])->first();
        }

        $sms = SmsRecord::create([
            'tenant_id' => $data['tenant_id'] ?? $trackingNumber?->tenant_id,
            'tracking_number_id' => $trackingNumber?->id,
            'message_sid' => $data['message_sid'] ?? null,
            'from_number' => $data['from_number'],
            'to_number' => $data['to_number'],
            'direction' => $data['direction'] ?? 'inbound',
            'body' => $data['body'],
            'media_urls' => $data['media_urls'] ?? null,
            'num_segments' => $data['num_segments'] ?? 1,
            'status' => $data['status'] ?? 'received',
            'source' => $trackingNumber?->source,
            'campaign' => $trackingNumber?->campaign,
        ]);

        if ($trackingNumber) {
            $trackingNumber->incrementSmsCount();
        }

        return $sms;
    }

    /**
     * Get call history
     */
    public function getCallHistory(int $tenantId, array $filters = []): Collection
    {
        return CallRecord::where('tenant_id', $tenantId)
            ->when($filters['site_id'] ?? null, fn($q, $s) => $q->where('site_id', $s))
            ->when($filters['source'] ?? null, fn($q, $s) => $q->where('source', $s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['direction'] ?? null, fn($q, $d) => $q->where('direction', $d))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('started_at', '<=', $d))
            ->with(['trackingNumber:id,phone_number,source', 'site:id,name', 'customer:id,first_name,last_name'])
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * Get calls requiring callback
     */
    public function getCallbacksRequired(int $tenantId): Collection
    {
        return CallRecord::where('tenant_id', $tenantId)
            ->requiresCallback()
            ->with(['trackingNumber:id,phone_number,source', 'customer:id,first_name,last_name,phone'])
            ->orderBy('callback_scheduled_at')
            ->get();
    }

    /**
     * Get missed calls
     */
    public function getMissedCalls(int $tenantId, ?Carbon $since = null): Collection
    {
        return CallRecord::where('tenant_id', $tenantId)
            ->missed()
            ->when($since, fn($q, $d) => $q->where('started_at', '>=', $d))
            ->with(['trackingNumber:id,phone_number,source', 'customer:id,first_name,last_name'])
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $source = null): array
    {
        $query = CallRecord::where('tenant_id', $tenantId)
            ->when($startDate, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('started_at', '<=', $d))
            ->when($source, fn($q, $s) => $q->where('source', $s));

        $totalCalls = $query->count();
        $answeredCalls = (clone $query)->answered()->count();
        $missedCalls = (clone $query)->missed()->count();
        $voicemailCalls = (clone $query)->where('status', 'voicemail')->count();

        $totalTalkTime = (clone $query)->sum('talk_duration_seconds');
        $avgTalkTime = (clone $query)->whereNotNull('talk_duration_seconds')->avg('talk_duration_seconds');
        $avgRingTime = (clone $query)->whereNotNull('ring_duration_seconds')->avg('ring_duration_seconds');

        $convertedCalls = (clone $query)->where('converted', true)->count();
        $totalRevenue = (clone $query)->where('converted', true)->sum('converted_value');

        $uniqueCallers = (clone $query)->distinct('from_number')->count('from_number');

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'missed_calls' => $missedCalls,
            'voicemail_calls' => $voicemailCalls,
            'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : 0,
            'total_talk_time' => $totalTalkTime,
            'avg_talk_time' => round($avgTalkTime ?? 0),
            'avg_ring_time' => round($avgRingTime ?? 0),
            'converted_calls' => $convertedCalls,
            'conversion_rate' => $answeredCalls > 0 ? round(($convertedCalls / $answeredCalls) * 100, 2) : 0,
            'total_revenue' => $totalRevenue ?? 0,
            'unique_callers' => $uniqueCallers,
        ];
    }

    /**
     * Get statistics by source
     */
    public function getStatsBySource(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        return CallRecord::where('tenant_id', $tenantId)
            ->when($startDate, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('started_at', '<=', $d))
            ->whereNotNull('source')
            ->select('source')
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('SUM(CASE WHEN status = "completed" AND answered_at IS NOT NULL THEN 1 ELSE 0 END) as answered')
            ->selectRaw('SUM(CASE WHEN status IN ("no_answer", "busy", "voicemail") THEN 1 ELSE 0 END) as missed')
            ->selectRaw('SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as converted')
            ->selectRaw('SUM(CASE WHEN converted = 1 THEN converted_value ELSE 0 END) as revenue')
            ->selectRaw('AVG(talk_duration_seconds) as avg_duration')
            ->groupBy('source')
            ->orderByDesc('total_calls')
            ->get();
    }

    /**
     * Record daily analytics
     */
    public function recordDailyAnalytics(int $tenantId, Carbon $date): void
    {
        $sites = \App\Models\Site::where('tenant_id', $tenantId)->pluck('id');
        $sources = TrackingNumber::where('tenant_id', $tenantId)->distinct()->pluck('source');

        foreach ($sites as $siteId) {
            foreach ($sources as $source) {
                $stats = $this->getStatistics($tenantId, $date, $date, $source);

                CallAnalytics::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'site_id' => $siteId,
                        'date' => $date->toDateString(),
                        'source' => $source,
                    ],
                    [
                        'total_calls' => $stats['total_calls'],
                        'answered_calls' => $stats['answered_calls'],
                        'missed_calls' => $stats['missed_calls'],
                        'voicemail_calls' => $stats['voicemail_calls'],
                        'unique_callers' => $stats['unique_callers'],
                        'total_talk_seconds' => $stats['total_talk_time'],
                        'avg_talk_seconds' => $stats['avg_talk_time'],
                        'avg_ring_seconds' => $stats['avg_ring_time'],
                        'answer_rate' => $stats['answer_rate'],
                        'converted_calls' => $stats['converted_calls'],
                        'conversion_rate' => $stats['conversion_rate'],
                        'total_revenue' => $stats['total_revenue'],
                    ]
                );
            }
        }
    }
}
