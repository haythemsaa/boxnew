<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\ReviewOptOut;
use App\Models\ReviewRequest;
use App\Models\ReviewRequestSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReviewRequestService
{
    /**
     * Create a review request after move-in
     */
    public function createAfterMoveIn(Contract $contract): ?ReviewRequest
    {
        $settings = ReviewRequestSettings::getForSite($contract->tenant_id, $contract->site_id);

        if (!$settings?->is_enabled || !$settings?->trigger_on_move_in) {
            return null;
        }

        return $this->createRequest($contract, 'move_in', $settings->move_in_delay_days);
    }

    /**
     * Create a review request after booking conversion
     */
    public function createAfterBooking(Booking $booking): ?ReviewRequest
    {
        $settings = ReviewRequestSettings::getForSite($booking->tenant_id, $booking->site_id);

        if (!$settings?->is_enabled || !$settings?->trigger_on_move_in) {
            return null;
        }

        return $this->createRequestFromBooking($booking, 'move_in', $settings->move_in_delay_days);
    }

    /**
     * Create a review request after contract renewal
     */
    public function createAfterRenewal(Contract $contract): ?ReviewRequest
    {
        $settings = ReviewRequestSettings::getForSite($contract->tenant_id, $contract->site_id);

        if (!$settings?->is_enabled || !$settings?->trigger_on_renewal) {
            return null;
        }

        return $this->createRequest($contract, 'contract_renewal', $settings->renewal_delay_days);
    }

    /**
     * Create a review request
     */
    public function createRequest(Contract $contract, string $trigger, int $delayDays = 7): ?ReviewRequest
    {
        $customer = $contract->customer;

        if (!$customer || !$customer->email) {
            return null;
        }

        // Check if opted out
        if (ReviewOptOut::isOptedOut($contract->tenant_id, $customer->email)) {
            return null;
        }

        // Check max requests per customer
        $settings = ReviewRequestSettings::getForSite($contract->tenant_id, $contract->site_id);
        $existingCount = ReviewRequest::where('tenant_id', $contract->tenant_id)
            ->where('customer_email', $customer->email)
            ->whereIn('status', ['sent', 'clicked', 'reviewed'])
            ->count();

        if ($existingCount >= ($settings?->max_requests_per_customer ?? 2)) {
            return null;
        }

        // Check minimum days between requests
        $lastRequest = ReviewRequest::where('tenant_id', $contract->tenant_id)
            ->where('customer_email', $customer->email)
            ->latest()
            ->first();

        if ($lastRequest) {
            $daysSinceLast = $lastRequest->created_at->diffInDays(now());
            if ($daysSinceLast < ($settings?->min_days_between_requests ?? 90)) {
                return null;
            }
        }

        return ReviewRequest::create([
            'tenant_id' => $contract->tenant_id,
            'site_id' => $contract->site_id,
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'customer_email' => $customer->email,
            'customer_name' => $customer->full_name,
            'customer_phone' => $customer->phone,
            'trigger' => $trigger,
            'delay_days' => $delayDays,
            'scheduled_at' => now()->addDays($delayDays),
            'status' => 'pending',
            'channel' => $settings?->send_sms ? ($settings?->send_email ? 'both' : 'sms') : 'email',
            'max_attempts' => 3,
        ]);
    }

    /**
     * Create request from booking
     */
    public function createRequestFromBooking(Booking $booking, string $trigger, int $delayDays = 7): ?ReviewRequest
    {
        if (!$booking->customer_email) {
            return null;
        }

        // Check if opted out
        if (ReviewOptOut::isOptedOut($booking->tenant_id, $booking->customer_email)) {
            return null;
        }

        return ReviewRequest::create([
            'tenant_id' => $booking->tenant_id,
            'site_id' => $booking->site_id,
            'customer_id' => $booking->customer_id,
            'booking_id' => $booking->id,
            'contract_id' => $booking->contract_id,
            'customer_email' => $booking->customer_email,
            'customer_name' => $booking->customer_full_name,
            'customer_phone' => $booking->customer_phone,
            'trigger' => $trigger,
            'delay_days' => $delayDays,
            'scheduled_at' => now()->addDays($delayDays),
            'status' => 'pending',
            'channel' => 'email',
            'max_attempts' => 3,
        ]);
    }

    /**
     * Process pending review requests
     */
    public function processPendingRequests(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        $requests = ReviewRequest::readyToSend()->limit(100)->get();

        foreach ($requests as $request) {
            $results['processed']++;

            try {
                $this->sendRequest($request);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to send review request', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Send a review request
     */
    public function sendRequest(ReviewRequest $request): void
    {
        $settings = ReviewRequestSettings::getForSite($request->tenant_id, $request->site_id);

        if (!$settings) {
            throw new \Exception('Review settings not found');
        }

        $emailMessageId = null;
        $smsMessageId = null;

        // Send email
        if (in_array($request->channel, ['email', 'both'])) {
            $emailMessageId = $this->sendEmail($request, $settings);
        }

        // Send SMS
        if (in_array($request->channel, ['sms', 'both']) && $request->customer_phone) {
            $smsMessageId = $this->sendSms($request, $settings);
        }

        $request->markSent($emailMessageId, $smsMessageId);
    }

    /**
     * Send email for review request
     */
    protected function sendEmail(ReviewRequest $request, ReviewRequestSettings $settings): ?string
    {
        $tenant = $request->tenant;
        $site = $request->site;

        $data = [
            'customer_name' => $request->customer_name,
            'company_name' => $tenant?->name ?? 'BoxiBox',
            'site_name' => $site?->name ?? '',
            'review_url' => $this->getReviewUrl($request, $settings),
            'unsubscribe_url' => route('review.unsubscribe', ['token' => $request->tracking_token]),
        ];

        $template = $settings->getDefaultEmailTemplate();
        $content = $this->renderTemplate($template, $data);
        $subject = $this->renderTemplate($settings->getDefaultEmailSubject(), $data);

        Mail::html($content, function ($message) use ($request, $subject, $tenant) {
            $message->to($request->customer_email, $request->customer_name)
                ->subject($subject)
                ->from(config('mail.from.address'), $tenant->name ?? 'BoxiBox');
        });

        return null; // Would return message ID from mail service
    }

    /**
     * Send SMS for review request
     */
    protected function sendSms(ReviewRequest $request, ReviewRequestSettings $settings): ?string
    {
        // Shorten the review URL
        $shortUrl = $this->shortenUrl($this->getReviewUrl($request, $settings));

        $data = [
            'customer_name' => explode(' ', $request->customer_name)[0], // First name only
            'company_name' => $request->tenant?->name ?? 'BoxiBox',
            'short_url' => $shortUrl,
        ];

        $template = $settings->getDefaultSmsTemplate();
        $message = $this->renderTemplate($template, $data);

        // TODO: Use SMS service to send
        // $smsService = app(SMSService::class);
        // return $smsService->send($request->customer_phone, $message);

        return null;
    }

    /**
     * Get the review URL for the request
     */
    protected function getReviewUrl(ReviewRequest $request, ReviewRequestSettings $settings): string
    {
        // Add tracking to the review URL
        $baseUrl = match ($settings->primary_platform) {
            'google' => $settings->google_review_url ?? $settings->getGoogleReviewUrlFromPlaceId(),
            'trustpilot' => $settings->trustpilot_url ?? '',
            'facebook' => $settings->facebook_page_url ?? '',
            default => $settings->google_review_url ?? '',
        };

        // Create tracking redirect URL
        return route('review.redirect', [
            'token' => $request->tracking_token,
            'platform' => $settings->primary_platform,
        ]);
    }

    /**
     * Handle review link click
     */
    public function handleClick(string $token): ?string
    {
        $request = ReviewRequest::where('tracking_token', $token)->first();

        if (!$request) {
            return null;
        }

        $request->markClicked();

        $settings = ReviewRequestSettings::getForSite($request->tenant_id, $request->site_id);

        return match ($settings?->primary_platform) {
            'google' => $settings->google_review_url ?? $settings->getGoogleReviewUrlFromPlaceId(),
            'trustpilot' => $settings->trustpilot_url,
            'facebook' => $settings->facebook_page_url,
            default => $settings?->google_review_url ?? '',
        };
    }

    /**
     * Handle unsubscribe request
     */
    public function handleUnsubscribe(string $token): bool
    {
        $request = ReviewRequest::where('tracking_token', $token)->first();

        if (!$request) {
            return false;
        }

        $request->markUnsubscribed();

        return true;
    }

    /**
     * Render template with data
     */
    protected function renderTemplate(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $template);
    }

    /**
     * Shorten a URL for SMS
     */
    protected function shortenUrl(string $url): string
    {
        // TODO: Implement URL shortening with bit.ly or similar
        // For now, just return original URL
        return $url;
    }

    /**
     * Get review request statistics
     */
    public function getStats(int $tenantId, ?int $siteId = null): array
    {
        $query = ReviewRequest::where('tenant_id', $tenantId);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $total = (clone $query)->count();
        $sent = (clone $query)->whereIn('status', ['sent', 'clicked', 'reviewed'])->count();
        $clicked = (clone $query)->whereIn('status', ['clicked', 'reviewed'])->count();
        $reviewed = (clone $query)->where('status', 'reviewed')->count();

        return [
            'total_sent' => $sent,
            'total_clicked' => $clicked,
            'total_reviewed' => $reviewed,
            'click_rate' => $sent > 0 ? round(($clicked / $sent) * 100, 2) : 0,
            'conversion_rate' => $clicked > 0 ? round(($reviewed / $clicked) * 100, 2) : 0,
            'average_rating' => (clone $query)->where('status', 'reviewed')->avg('rating') ?? 0,
            'by_platform' => $this->getStatsByPlatform($tenantId, $siteId),
            'by_trigger' => $this->getStatsByTrigger($tenantId, $siteId),
        ];
    }

    protected function getStatsByPlatform(int $tenantId, ?int $siteId): array
    {
        $query = ReviewRequest::where('tenant_id', $tenantId)
            ->where('status', 'reviewed')
            ->whereNotNull('review_platform');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return $query->selectRaw('review_platform, COUNT(*) as count, AVG(rating) as avg_rating')
            ->groupBy('review_platform')
            ->get()
            ->keyBy('review_platform')
            ->toArray();
    }

    protected function getStatsByTrigger(int $tenantId, ?int $siteId): array
    {
        $query = ReviewRequest::where('tenant_id', $tenantId);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return $query->selectRaw('`trigger`, status, COUNT(*) as count')
            ->groupBy('trigger', 'status')
            ->get()
            ->groupBy('trigger')
            ->map(function ($items) {
                return $items->pluck('count', 'status');
            })
            ->toArray();
    }
}
