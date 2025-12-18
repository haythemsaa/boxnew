<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Dispatch an event to all subscribed webhooks
     */
    public function dispatch(int $tenantId, string $event, array $data): void
    {
        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $data);
        }
    }

    /**
     * Send webhook to a specific endpoint
     */
    public function sendWebhook(Webhook $webhook, string $event, array $data): WebhookDelivery
    {
        $eventId = Str::uuid()->toString();
        $timestamp = now()->toIso8601String();

        $payload = [
            'event' => $event,
            'event_id' => $eventId,
            'timestamp' => $timestamp,
            'tenant_id' => $webhook->tenant_id,
            'data' => $data,
        ];

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $event,
            'event_id' => $eventId,
            'payload' => $payload,
            'attempt' => 1,
            'status' => 'pending',
        ]);

        // Send the webhook
        $this->executeDelivery($webhook, $delivery, $payload);

        return $delivery;
    }

    /**
     * Execute the webhook delivery
     */
    protected function executeDelivery(Webhook $webhook, WebhookDelivery $delivery, array $payload): void
    {
        $startTime = microtime(true);
        $jsonPayload = json_encode($payload);

        try {
            $headers = array_merge(
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Boxibox-Webhook/1.0',
                    'X-Boxibox-Event' => $delivery->event_type,
                    'X-Boxibox-Delivery' => $delivery->event_id,
                    'X-Boxibox-Timestamp' => $payload['timestamp'],
                ],
                $webhook->headers ?? []
            );

            // Add signature if secret is set
            if ($webhook->secret_key) {
                $headers['X-Boxibox-Signature'] = $webhook->generateSignature($jsonPayload);
            }

            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->withOptions([
                    'verify' => $webhook->verify_ssl,
                ])
                ->post($webhook->url, $payload);

            $duration = microtime(true) - $startTime;

            if ($response->successful()) {
                $delivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $duration
                );
                $webhook->recordSuccess();
            } else {
                $error = "HTTP {$response->status()}: " . substr($response->body(), 0, 500);
                $delivery->markAsFailed($error, $response->status(), $response->body());
                $webhook->recordFailure($error);

                // Schedule retry if applicable
                if ($delivery->isRetryable()) {
                    $this->scheduleRetry($webhook, $delivery);
                }
            }
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $error = $e->getMessage();

            $delivery->markAsFailed($error);
            $webhook->recordFailure($error);

            Log::error("Webhook delivery failed", [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'error' => $error,
            ]);

            // Schedule retry if applicable
            if ($delivery->isRetryable()) {
                $this->scheduleRetry($webhook, $delivery);
            }
        }
    }

    /**
     * Schedule a retry for failed delivery
     */
    protected function scheduleRetry(Webhook $webhook, WebhookDelivery $delivery): void
    {
        // Create a new delivery attempt
        $retryDelivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $delivery->event_type,
            'event_id' => $delivery->event_id,
            'payload' => $delivery->payload,
            'attempt' => $delivery->attempt + 1,
            'status' => 'pending',
        ]);

        // In production, this would be dispatched to a queue with delay
        // For now, we'll just execute it directly with exponential backoff
        $delay = pow(2, $delivery->attempt) * 60; // 2min, 4min, 8min...

        // Here you would dispatch to queue:
        // dispatch(new ProcessWebhookDelivery($webhook, $retryDelivery, $delivery->payload))
        //     ->delay(now()->addSeconds($delay));
    }

    /**
     * Test a webhook endpoint
     */
    public function testWebhook(Webhook $webhook): array
    {
        $testPayload = [
            'event' => 'test.ping',
            'event_id' => Str::uuid()->toString(),
            'timestamp' => now()->toIso8601String(),
            'tenant_id' => $webhook->tenant_id,
            'data' => [
                'message' => 'This is a test webhook from Boxibox',
                'webhook_id' => $webhook->id,
                'webhook_name' => $webhook->name,
            ],
        ];

        $jsonPayload = json_encode($testPayload);
        $startTime = microtime(true);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Boxibox-Webhook/1.0',
                'X-Boxibox-Event' => 'test.ping',
                'X-Boxibox-Delivery' => $testPayload['event_id'],
                'X-Boxibox-Timestamp' => $testPayload['timestamp'],
            ];

            if ($webhook->secret_key) {
                $headers['X-Boxibox-Signature'] = $webhook->generateSignature($jsonPayload);
            }

            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->withOptions(['verify' => $webhook->verify_ssl])
                ->post($webhook->url, $testPayload);

            $duration = round((microtime(true) - $startTime) * 1000);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'duration_ms' => $duration,
                'response' => substr($response->body(), 0, 1000),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Get available events for UI
     */
    public static function getAvailableEvents(): array
    {
        return Webhook::EVENTS;
    }

    /**
     * Retry a failed webhook delivery
     */
    public function retryDelivery(Webhook $webhook, WebhookDelivery $originalDelivery): array
    {
        // Create a new delivery attempt
        $retryDelivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $originalDelivery->event_type,
            'event_id' => $originalDelivery->event_id,
            'payload' => $originalDelivery->payload,
            'attempt' => $originalDelivery->attempt + 1,
            'status' => 'pending',
        ]);

        $startTime = microtime(true);
        $payload = $originalDelivery->payload;
        $jsonPayload = json_encode($payload);

        try {
            $headers = array_merge(
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Boxibox-Webhook/1.0',
                    'X-Boxibox-Event' => $retryDelivery->event_type,
                    'X-Boxibox-Delivery' => $retryDelivery->event_id,
                    'X-Boxibox-Timestamp' => $payload['timestamp'] ?? now()->toIso8601String(),
                    'X-Boxibox-Retry' => (string) $retryDelivery->attempt,
                ],
                $webhook->headers ?? []
            );

            // Add signature if secret is set
            if ($webhook->secret_key) {
                $headers['X-Boxibox-Signature'] = $webhook->generateSignature($jsonPayload);
            }

            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->withOptions([
                    'verify' => $webhook->verify_ssl,
                ])
                ->post($webhook->url, $payload);

            $duration = microtime(true) - $startTime;

            if ($response->successful()) {
                $retryDelivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $duration
                );
                $webhook->recordSuccess();

                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'duration_ms' => round($duration * 1000),
                ];
            } else {
                $error = "HTTP {$response->status()}: " . substr($response->body(), 0, 500);
                $retryDelivery->markAsFailed($error, $response->status(), $response->body());
                $webhook->recordFailure($error);

                return [
                    'success' => false,
                    'error' => $error,
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $retryDelivery->markAsFailed($error);
            $webhook->recordFailure($error);

            Log::error("Manual webhook retry failed", [
                'webhook_id' => $webhook->id,
                'delivery_id' => $retryDelivery->id,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }
}
