<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\ApiKey;
use App\Services\WebhookService;
use Illuminate\Http\Request;
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
     * Display webhooks management page
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
