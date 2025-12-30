<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IotSensor;
use App\Models\SmartLock;
use App\Models\AccessLog;
use App\Services\IoTService;
use App\Services\SmartLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * IoT Webhook Controller
 *
 * Handles incoming webhooks from IoT devices and smart lock providers
 * Supports: Noke, Salto, Kisi, PTI, Generic HTTP sensors
 */
class IoTWebhookController extends Controller
{
    protected IoTService $iotService;
    protected SmartLockService $smartLockService;

    public function __construct(IoTService $iotService, SmartLockService $smartLockService)
    {
        $this->iotService = $iotService;
        $this->smartLockService = $smartLockService;
    }

    /**
     * Generic sensor data webhook
     * Accepts data from any HTTP-enabled sensor
     *
     * POST /api/iot/sensor-data
     * {
     *   "sensor_id": "string",
     *   "value": float,
     *   "timestamp": "ISO8601 optional",
     *   "battery": int optional,
     *   "signal_strength": int optional
     * }
     */
    public function sensorData(Request $request)
    {
        $validated = $request->validate([
            'sensor_id' => 'required|string',
            'value' => 'required|numeric',
            'timestamp' => 'nullable|date',
            'battery' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer',
        ]);

        // Find sensor by external ID or serial number
        $sensor = IotSensor::where('external_id', $validated['sensor_id'])
            ->orWhere('serial_number', $validated['sensor_id'])
            ->first();

        if (!$sensor) {
            Log::warning('IoT webhook: Unknown sensor', ['sensor_id' => $validated['sensor_id']]);
            return response()->json(['error' => 'Unknown sensor'], 404);
        }

        // Verify API key if configured
        $apiKey = $request->header('X-Api-Key') ?? $request->input('api_key');
        if ($sensor->site->tenant->iot_api_key && $apiKey !== $sensor->site->tenant->iot_api_key) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Record the reading
        $timestamp = isset($validated['timestamp'])
            ? Carbon::parse($validated['timestamp'])
            : now();

        $reading = $this->iotService->recordReading($sensor, $validated['value'], $timestamp);

        // Update battery level if provided
        if (isset($validated['battery'])) {
            $sensor->update(['battery_level' => $validated['battery']]);

            // Alert if battery low
            if ($validated['battery'] < 20) {
                $this->iotService->createBatteryAlert($sensor, $validated['battery']);
            }
        }

        // Update signal strength
        if (isset($validated['signal_strength'])) {
            $sensor->update(['signal_strength' => $validated['signal_strength']]);
        }

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'alerts_triggered' => $reading->triggered_alert,
        ]);
    }

    /**
     * Batch sensor data upload
     * For sensors that buffer data and send in batches
     *
     * POST /api/iot/sensor-data/batch
     * {
     *   "sensor_id": "string",
     *   "readings": [
     *     { "value": float, "timestamp": "ISO8601" },
     *     ...
     *   ]
     * }
     */
    public function sensorDataBatch(Request $request)
    {
        $validated = $request->validate([
            'sensor_id' => 'required|string',
            'readings' => 'required|array|min:1|max:1000',
            'readings.*.value' => 'required|numeric',
            'readings.*.timestamp' => 'required|date',
        ]);

        $sensor = IotSensor::where('external_id', $validated['sensor_id'])
            ->orWhere('serial_number', $validated['sensor_id'])
            ->first();

        if (!$sensor) {
            return response()->json(['error' => 'Unknown sensor'], 404);
        }

        $imported = 0;
        $alerts = 0;

        foreach ($validated['readings'] as $data) {
            $reading = $this->iotService->recordReading(
                $sensor,
                $data['value'],
                Carbon::parse($data['timestamp'])
            );

            $imported++;
            if ($reading->triggered_alert) {
                $alerts++;
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'alerts_triggered' => $alerts,
        ]);
    }

    /**
     * Noke smart lock webhook
     * Receives events from Noke lock system
     */
    public function nokeWebhook(Request $request)
    {
        // Log only safe fields, never log full payload
        Log::info('Noke webhook received', [
            'event' => $request->input('event'),
            'lock_id' => $request->input('lock_id'),
        ]);

        $payload = $request->only([
            'event', 'type', 'lock_id', 'mac', 'method',
            'quick_click_code', 'timestamp', 'battery'
        ]);
        $eventType = $payload['event'] ?? $payload['type'] ?? null;

        // Verify webhook signature if configured
        $signature = $request->header('X-Noke-Signature');
        if (!$this->verifyNokeSignature($request, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $lockId = $payload['lock_id'] ?? $payload['mac'] ?? null;
        $lock = SmartLock::where('external_id', $lockId)->first();

        if (!$lock) {
            return response()->json(['error' => 'Unknown lock'], 404);
        }

        // Map Noke events
        $eventMapping = [
            'unlocked' => 'unlock_success',
            'locked' => 'lock_success',
            'access_denied' => 'access_denied',
            'invalid_command' => 'command_failed',
            'low_battery' => 'battery_low',
        ];

        $mappedEvent = $eventMapping[$eventType] ?? $eventType;

        // Log the access event
        AccessLog::create([
            'tenant_id' => $lock->tenant_id,
            'box_id' => $lock->box_id,
            'smart_lock_id' => $lock->id,
            'access_method' => $payload['method'] ?? 'noke_app',
            'status' => str_contains($mappedEvent, 'success') ? 'granted' : 'denied',
            'user_identifier' => $payload['quick_click_code'] ?? null,
            'accessed_at' => isset($payload['timestamp'])
                ? Carbon::parse($payload['timestamp'])
                : now(),
            'metadata' => $payload,
        ]);

        // Update lock state
        if (in_array($mappedEvent, ['unlock_success', 'lock_success'])) {
            $lock->update([
                'is_locked' => $mappedEvent === 'lock_success',
                'last_' . ($mappedEvent === 'lock_success' ? 'locked' : 'unlocked') . '_at' => now(),
            ]);
        }

        // Handle battery alerts
        if ($mappedEvent === 'battery_low' || (isset($payload['battery']) && $payload['battery'] < 20)) {
            $lock->update(['battery_level' => $payload['battery'] ?? null]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Salto KS webhook
     * Receives events from Salto KS cloud
     */
    public function saltoWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifySaltoSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Log only safe fields, never log full payload
        Log::info('Salto webhook received', [
            'eventType' => $request->input('eventType'),
            'lockId' => $request->input('lockId'),
        ]);

        $payload = $request->only([
            'eventType', 'lockId', 'accessPoint', 'accessMethod',
            'mobileKey', 'pinCode', 'eventTimestamp'
        ]);
        $eventType = $payload['eventType'] ?? null;

        // Find lock by Salto ID
        $lockId = $payload['lockId'] ?? $payload['accessPoint']['id'] ?? null;
        $lock = SmartLock::where('external_id', $lockId)->first();

        if (!$lock) {
            return response()->json(['error' => 'Unknown lock'], 404);
        }

        // Map Salto events
        $eventMapping = [
            'ACCESS_GRANTED' => 'unlock_success',
            'ACCESS_DENIED' => 'access_denied',
            'DOOR_OPENED' => 'door_opened',
            'DOOR_CLOSED' => 'door_closed',
            'DOOR_FORCED' => 'door_forced',
            'BATTERY_LOW' => 'battery_low',
        ];

        $mappedEvent = $eventMapping[$eventType] ?? 'unlock_success';

        AccessLog::create([
            'tenant_id' => $lock->tenant_id,
            'box_id' => $lock->box_id,
            'smart_lock_id' => $lock->id,
            'access_method' => $payload['accessMethod'] ?? 'salto_ks',
            'status' => str_contains($mappedEvent, 'success') || $mappedEvent === 'door_opened' ? 'granted' : 'denied',
            'user_identifier' => $payload['mobileKey']['id'] ?? $payload['pinCode'] ?? null,
            'accessed_at' => isset($payload['eventTimestamp'])
                ? Carbon::parse($payload['eventTimestamp'])
                : now(),
            'metadata' => $payload,
        ]);

        // Security alert for forced doors
        if ($mappedEvent === 'door_forced') {
            $this->createSecurityAlert($lock, 'Door forced open detected');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Kisi webhook
     */
    public function kisiWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifyKisiSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Log only safe fields, never log full payload
        Log::info('Kisi webhook received', [
            'event_type' => $request->input('event.type'),
            'lock_id' => $request->input('event.lock_id'),
        ]);

        $payload = $request->only(['event', 'lock', 'timestamp']);
        $event = $payload['event'] ?? [];

        $lockId = $event['lock_id'] ?? $payload['lock']['id'] ?? null;
        $lock = SmartLock::where('external_id', $lockId)->first();

        if (!$lock) {
            return response()->json(['error' => 'Unknown lock'], 404);
        }

        $eventType = $event['type'] ?? 'unlock';
        $mappedEvent = match ($eventType) {
            'unlock' => 'unlock_success',
            'lock' => 'lock_success',
            'failed_unlock' => 'access_denied',
            default => 'unlock_success',
        };

        AccessLog::create([
            'tenant_id' => $lock->tenant_id,
            'box_id' => $lock->box_id,
            'smart_lock_id' => $lock->id,
            'access_method' => 'kisi',
            'status' => str_contains($mappedEvent, 'success') ? 'granted' : 'denied',
            'accessed_at' => isset($event['timestamp'])
                ? Carbon::parse($event['timestamp'])
                : now(),
            'metadata' => $payload,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * PTI Storlogix webhook
     */
    public function ptiWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifyPtiSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Log only safe fields, never log full payload
        Log::info('PTI webhook received', [
            'event_code' => $request->input('event_code'),
            'device_id' => $request->input('device_id'),
        ]);

        $payload = $request->only([
            'device_id', 'gate_id', 'event_code',
            'access_code', 'timestamp'
        ]);

        // PTI typically sends gate/door events
        $deviceId = $payload['device_id'] ?? $payload['gate_id'] ?? null;
        $lock = SmartLock::where('external_id', $deviceId)->first();

        if (!$lock) {
            return response()->json(['error' => 'Unknown device'], 404);
        }

        $eventType = match ($payload['event_code'] ?? '') {
            'OPEN', 'GRANT' => 'unlock_success',
            'CLOSE' => 'lock_success',
            'DENY' => 'access_denied',
            'ALARM' => 'alarm_triggered',
            default => 'unlock_success',
        };

        AccessLog::create([
            'tenant_id' => $lock->tenant_id,
            'box_id' => $lock->box_id,
            'smart_lock_id' => $lock->id,
            'access_method' => 'pti',
            'status' => str_contains($eventType, 'success') ? 'granted' : ($eventType === 'access_denied' ? 'denied' : 'granted'),
            'user_identifier' => $payload['access_code'] ?? null,
            'accessed_at' => isset($payload['timestamp'])
                ? Carbon::createFromTimestamp($payload['timestamp'])
                : now(),
            'metadata' => $payload,
        ]);

        if ($eventType === 'alarm_triggered') {
            $this->createSecurityAlert($lock, 'Alarm triggered at ' . $lock->box->display_name);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Gate/Door sensor webhook
     * For simple open/close sensors
     */
    public function doorSensorWebhook(Request $request)
    {
        $validated = $request->validate([
            'sensor_id' => 'required|string',
            'state' => 'required|in:open,closed,unknown',
            'timestamp' => 'nullable|date',
        ]);

        $sensor = IotSensor::where('external_id', $validated['sensor_id'])
            ->orWhere('serial_number', $validated['sensor_id'])
            ->first();

        if (!$sensor) {
            return response()->json(['error' => 'Unknown sensor'], 404);
        }

        // Record as boolean value (1 = open, 0 = closed)
        $value = $validated['state'] === 'open' ? 1 : 0;

        $reading = $this->iotService->recordReading(
            $sensor,
            $value,
            isset($validated['timestamp']) ? Carbon::parse($validated['timestamp']) : now()
        );

        // Check for prolonged open state
        if ($value === 1) {
            $this->checkProlongedOpenState($sensor);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Climate sensor webhook (temperature/humidity combo)
     */
    public function climateSensorWebhook(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'timestamp' => 'nullable|date',
            'battery' => 'nullable|integer',
        ]);

        $timestamp = isset($validated['timestamp'])
            ? Carbon::parse($validated['timestamp'])
            : now();

        $results = [];

        // Find and record temperature sensor
        if (isset($validated['temperature'])) {
            $tempSensor = IotSensor::where('external_id', $validated['device_id'] . '_temp')
                ->orWhere('external_id', $validated['device_id'])
                ->whereHas('sensorType', fn($q) => $q->where('slug', 'temperature'))
                ->first();

            if ($tempSensor) {
                $reading = $this->iotService->recordReading($tempSensor, $validated['temperature'], $timestamp);
                $results['temperature'] = ['recorded' => true, 'alert' => $reading->triggered_alert];
            }
        }

        // Find and record humidity sensor
        if (isset($validated['humidity'])) {
            $humSensor = IotSensor::where('external_id', $validated['device_id'] . '_hum')
                ->orWhere('external_id', $validated['device_id'])
                ->whereHas('sensorType', fn($q) => $q->where('slug', 'humidity'))
                ->first();

            if ($humSensor) {
                $reading = $this->iotService->recordReading($humSensor, $validated['humidity'], $timestamp);
                $results['humidity'] = ['recorded' => true, 'alert' => $reading->triggered_alert];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Health check endpoint for IoT devices
     */
    public function healthCheck(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Verify Noke webhook signature
     */
    protected function verifyNokeSignature(Request $request, ?string $signature): bool
    {
        $secret = config('services.noke.webhook_secret');

        // If no secret is configured, reject webhooks in production for security
        if (empty($secret)) {
            if (app()->environment('production')) {
                Log::warning('Noke webhook rejected: No webhook secret configured');
                return false;
            }
            return true; // Allow in development only
        }

        if (!$signature) {
            Log::warning('Noke webhook rejected: Missing signature header');
            return false;
        }

        // Verify HMAC signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Salto webhook signature
     */
    protected function verifySaltoSignature(Request $request): bool
    {
        $secret = config('services.salto.webhook_secret');

        if (empty($secret)) {
            if (app()->environment('production')) {
                Log::warning('Salto webhook rejected: No webhook secret configured');
                return false;
            }
            return true;
        }

        $signature = $request->header('X-Salto-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Kisi webhook signature
     */
    protected function verifyKisiSignature(Request $request): bool
    {
        $secret = config('services.kisi.webhook_secret');

        if (empty($secret)) {
            if (app()->environment('production')) {
                Log::warning('Kisi webhook rejected: No webhook secret configured');
                return false;
            }
            return true;
        }

        $signature = $request->header('X-Kisi-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify PTI webhook signature
     */
    protected function verifyPtiSignature(Request $request): bool
    {
        $secret = config('services.pti.webhook_secret');

        if (empty($secret)) {
            if (app()->environment('production')) {
                Log::warning('PTI webhook rejected: No webhook secret configured');
                return false;
            }
            return true;
        }

        $signature = $request->header('X-PTI-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create a security alert
     */
    protected function createSecurityAlert(SmartLock $lock, string $message): void
    {
        $siteId = $lock->box?->site_id;

        \App\Models\IotAlert::create([
            'tenant_id' => $lock->tenant_id,
            'site_id' => $siteId,
            'box_id' => $lock->box_id,
            'alert_type' => 'device_error',
            'severity' => 'critical',
            'title' => 'Security Alert',
            'message' => $message,
            'status' => 'active',
        ]);

        // TODO: Send push notification to admin
    }

    /**
     * Check for prolonged open state
     */
    protected function checkProlongedOpenState(IotSensor $sensor): void
    {
        $cacheKey = "door_open_{$sensor->id}";

        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, now(), 3600);
        } else {
            $openSince = Cache::get($cacheKey);
            $openMinutes = now()->diffInMinutes($openSince);

            if ($openMinutes > 30) {
                // Alert for door left open too long
                \App\Models\IotAlert::firstOrCreate([
                    'sensor_id' => $sensor->id,
                    'alert_type' => 'door_left_open',
                    'status' => 'active',
                ], [
                    'tenant_id' => $sensor->site->tenant_id,
                    'site_id' => $sensor->site_id,
                    'box_id' => $sensor->box_id,
                    'severity' => 'warning',
                    'title' => 'Door Left Open',
                    'message' => "Door has been open for over {$openMinutes} minutes",
                ]);
            }
        }
    }
}
