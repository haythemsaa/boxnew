<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GocardlessEvent;
use App\Services\GoCardlessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoCardlessWebhookController extends Controller
{
    public function __construct(
        protected GoCardlessService $gocardless
    ) {}

    /**
     * Handle GoCardless webhook
     */
    public function handle(Request $request): JsonResponse
    {
        // Get signature from header
        $signature = $request->header('Webhook-Signature');

        if (!$signature) {
            Log::warning('GoCardless webhook missing signature');
            return response()->json(['error' => 'Missing signature'], 401);
        }

        // Get raw body
        $body = $request->getContent();

        // Verify signature
        if (!$this->gocardless->verifyWebhookSignature($body, $signature)) {
            Log::warning('GoCardless webhook invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse events
        $payload = json_decode($body, true);
        $events = $payload['events'] ?? [];

        if (empty($events)) {
            Log::info('GoCardless webhook received with no events');
            return response()->json(['success' => true]);
        }

        // Process each event
        foreach ($events as $event) {
            $this->processEvent($event);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Process a single event
     */
    protected function processEvent(array $event): void
    {
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            Log::warning('GoCardless event missing ID', $event);
            return;
        }

        // Check if already processed (idempotency)
        if (GocardlessEvent::where('event_id', $eventId)->exists()) {
            Log::info('GoCardless event already processed', ['event_id' => $eventId]);
            return;
        }

        // Store event
        $gcEvent = GocardlessEvent::create([
            'event_id' => $eventId,
            'action' => $event['action'] ?? null,
            'resource_type' => $event['resource_type'] ?? null,
            'resource_id' => $event['links'][$event['resource_type']] ?? null,
            'links' => $event['links'] ?? null,
            'details' => $event['details'] ?? null,
            'metadata' => $event['metadata'] ?? null,
        ]);

        try {
            // Process the event
            $this->gocardless->processWebhook($event);

            // Mark as processed
            $gcEvent->update([
                'processed' => true,
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('GoCardless webhook processing error', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
