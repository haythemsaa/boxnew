<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\ApiKey;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Display webhooks management page (legacy)
     */
    public function index(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;

        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->withCount('deliveries')
            ->orderByDesc('created_at')
            ->get();

        $apiKeys = ApiKey::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Tenant/Integrations/Index', [
            'webhooks' => $webhooks,
            'apiKeys' => $apiKeys,
            'availableEvents' => Webhook::EVENTS,
            'availablePermissions' => ApiKey::PERMISSIONS,
        ]);
    }

    /**
     * Display webhooks list page (new interface)
     */
    public function webhooksIndex(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;

        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($webhook) {
                $webhook->successful_calls = $webhook->deliveries()->where('status', 'success')->count();
                $webhook->failed_calls = $webhook->deliveries()->where('status', 'failed')->count();
                $webhook->last_triggered_at = $webhook->deliveries()->latest()->value('created_at');
                $lastDelivery = $webhook->deliveries()->latest()->first();
                $webhook->last_status = $lastDelivery?->status;
                $webhook->last_error = $lastDelivery?->error;
                return $webhook;
            });

        // Calculate stats
        $activeCount = $webhooks->where('is_active', true)->count();
        $totalCalls = $webhooks->sum('successful_calls') + $webhooks->sum('failed_calls');
        $successfulCalls = $webhooks->sum('successful_calls');
        $failedCalls = $webhooks->sum('failed_calls');
        $successRate = $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100) : 100;

        // Recent failures (last 24h)
        $recentFailures = WebhookDelivery::whereHas('webhook', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return Inertia::render('Tenant/Integrations/Webhooks/Index', [
            'webhooks' => $webhooks,
            'stats' => [
                'active' => $activeCount,
                'calls_this_month' => $totalCalls,
                'success_rate' => $successRate,
                'recent_failures' => $recentFailures,
            ],
        ]);
    }

    /**
     * Show create webhook form
     */
    public function webhooksCreate(): Response
    {
        return Inertia::render('Tenant/Integrations/Webhooks/Create');
    }

    /**
     * Show edit webhook form
     */
    public function webhooksEdit(Webhook $webhook): Response
    {
        $this->authorize('update', $webhook);

        $webhook->successful_calls = $webhook->deliveries()->where('status', 'success')->count();
        $webhook->failed_calls = $webhook->deliveries()->where('status', 'failed')->count();
        $webhook->average_response_time = round($webhook->deliveries()->avg('response_time_ms') ?? 0);

        return Inertia::render('Tenant/Integrations/Webhooks/Edit', [
            'webhook' => $webhook,
        ]);
    }

    /**
     * Show webhook logs
     */
    public function webhooksLogs(Webhook $webhook, Request $request): Response
    {
        $this->authorize('view', $webhook);

        $perPage = 20;
        $query = $webhook->deliveries()->orderByDesc('created_at');

        // Pagination
        $total = $query->count();
        $page = max(1, (int) $request->get('page', 1));
        $logs = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Stats
        $stats = [
            'total' => $total,
            'success' => $webhook->deliveries()->where('status', 'success')->count(),
            'failed' => $webhook->deliveries()->where('status', 'failed')->count(),
            'success_rate' => $total > 0 ? round(($webhook->deliveries()->where('status', 'success')->count() / $total) * 100) : 100,
            'avg_response_time' => round($webhook->deliveries()->avg('response_time_ms') ?? 0),
        ];

        return Inertia::render('Tenant/Integrations/Webhooks/Logs', [
            'webhook' => $webhook,
            'logs' => $logs,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    }

    /**
     * Retry a failed webhook delivery
     */
    public function retryDelivery(Webhook $webhook, WebhookDelivery $delivery): JsonResponse
    {
        $this->authorize('update', $webhook);

        try {
            $result = $this->webhookService->retryDelivery($webhook, $delivery);

            return response()->json([
                'success' => $result['success'] ?? false,
                'error' => $result['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display API documentation page
     */
    public function apiDocs(): Response
    {
        return Inertia::render('Tenant/Integrations/ApiDocs');
    }

    /**
     * Store a new webhook
     */
    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', array_keys(Webhook::EVENTS)),
            'headers' => 'nullable|array',
            'verify_ssl' => 'boolean',
            'retry_count' => 'nullable|integer|min:0|max:5',
            'timeout' => 'nullable|integer|min:5|max:60',
        ]);

        $webhook = Webhook::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        return back()->with('success', 'Webhook créé avec succès.');
    }

    /**
     * Update a webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', array_keys(Webhook::EVENTS)),
            'headers' => 'nullable|array',
            'is_active' => 'boolean',
            'verify_ssl' => 'boolean',
            'retry_count' => 'nullable|integer|min:0|max:5',
            'timeout' => 'nullable|integer|min:5|max:60',
        ]);

        $webhook->update($validated);

        return back()->with('success', 'Webhook mis à jour avec succès.');
    }

    /**
     * Delete a webhook
     */
    public function destroy(Webhook $webhook)
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return back()->with('success', 'Webhook supprimé avec succès.');
    }

    /**
     * Test a webhook
     */
    public function test(Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $result = $this->webhookService->testWebhook($webhook);

        if ($result['success']) {
            return back()->with('success', "Test réussi ! Temps de réponse: {$result['duration_ms']}ms");
        }

        $error = $result['error'] ?? "Erreur HTTP {$result['status_code']}";
        return back()->with('error', "Échec du test: {$error}");
    }

    /**
     * Show webhook deliveries
     */
    public function deliveries(Webhook $webhook): Response
    {
        $this->authorize('view', $webhook);

        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Tenant/Integrations/Deliveries', [
            'webhook' => $webhook,
            'deliveries' => $deliveries,
        ]);
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $webhook->update(['secret_key' => \Illuminate\Support\Str::random(32)]);

        return back()->with('success', 'Secret regénéré avec succès. Mettez à jour votre intégration.');
    }

    // =============================================
    // API KEYS MANAGEMENT
    // =============================================

    /**
     * Store a new API key
     */
    public function storeApiKey(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(ApiKey::PERMISSIONS)),
            'ip_whitelist' => 'nullable|array',
            'ip_whitelist.*' => 'ip',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $apiKey = ApiKey::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        // Return the secret key ONLY on creation
        return back()->with([
            'success' => 'Clé API créée avec succès.',
            'api_key' => $apiKey->key,
            'api_secret' => $apiKey->secret,
        ]);
    }

    /**
     * Update an API key
     */
    public function updateApiKey(Request $request, ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(ApiKey::PERMISSIONS)),
            'ip_whitelist' => 'nullable|array',
            'ip_whitelist.*' => 'ip',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $apiKey->update($validated);

        return back()->with('success', 'Clé API mise à jour avec succès.');
    }

    /**
     * Delete an API key
     */
    public function destroyApiKey(ApiKey $apiKey)
    {
        $this->authorize('delete', $apiKey);

        $apiKey->delete();

        return back()->with('success', 'Clé API supprimée avec succès.');
    }

    /**
     * Regenerate API key secret
     */
    public function regenerateApiKeySecret(ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey);

        $newSecret = $apiKey->regenerateSecret();

        return back()->with([
            'success' => 'Secret regénéré avec succès.',
            'api_secret' => $newSecret,
        ]);
    }
}
