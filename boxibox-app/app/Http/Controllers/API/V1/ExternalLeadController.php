<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Site;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * External Lead Controller
 *
 * API endpoints for external integrations (n8n, Zapier, Make, etc.)
 * Uses API Key authentication via X-API-Key header
 */
class ExternalLeadController extends Controller
{
    /**
     * Authenticate request via API Key
     */
    private function authenticateApiKey(Request $request): ?object
    {
        $apiKey = $request->header('X-API-Key') ?? $request->header('Authorization');

        if (!$apiKey) {
            return null;
        }

        // Remove 'Bearer ' prefix if present
        $apiKey = str_replace('Bearer ', '', $apiKey);

        // Find API key in database
        $key = ApiKey::where('key', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($key) {
            // Update last used timestamp
            $key->update(['last_used_at' => now()]);
        }

        return $key;
    }

    /**
     * Store a new lead from external source
     *
     * POST /api/v1/external/leads
     *
     * Headers:
     *   X-API-Key: your-api-key
     *
     * Body:
     * {
     *   "first_name": "Jean",
     *   "last_name": "Dupont",
     *   "email": "jean@example.com",
     *   "phone": "+33612345678",
     *   "company": "StoragePlus",
     *   "source": "n8n_google_places",
     *   "score": 75,
     *   "priority": "hot",
     *   "notes": "Found via Google Maps",
     *   "metadata": { "google_rating": 4.2, "reviews_count": 45 }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        // Authenticate
        $apiKey = $this->authenticateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or missing API key',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'score' => 'nullable|integer|min:0|max:100',
            'priority' => 'nullable|string|in:cold,lukewarm,warm,hot,very_hot',
            'notes' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
            'site_id' => 'nullable|exists:sites,id',
            'box_type_interest' => 'nullable|string|max:50',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $validator->errors()
            ], 422);
        }

        // Check for duplicate (same email in last 24h)
        $existingLead = Lead::where('tenant_id', $apiKey->tenant_id)
            ->where('email', $request->email)
            ->where('created_at', '>', now()->subDay())
            ->first();

        if ($existingLead) {
            return response()->json([
                'success' => false,
                'error' => 'Duplicate lead detected',
                'code' => 'DUPLICATE_LEAD',
                'existing_lead_id' => $existingLead->id
            ], 409);
        }

        try {
            // Create the lead
            $lead = Lead::create([
                'tenant_id' => $apiKey->tenant_id,
                'site_id' => $request->site_id,
                'first_name' => $request->first_name ?? '',
                'last_name' => $request->last_name ?? '',
                'email' => $request->email,
                'phone' => $request->phone,
                'company' => $request->company,
                'source' => $request->source ?? 'api_external',
                'status' => 'new',
                'score' => $request->score ?? 50,
                'priority' => $request->priority ?? 'warm',
                'notes' => $request->notes,
                'metadata' => array_merge(
                    $request->metadata ?? [],
                    [
                        'api_key_id' => $apiKey->id,
                        'api_key_name' => $apiKey->name,
                        'imported_at' => now()->toIso8601String(),
                        'source_ip' => $request->ip(),
                    ]
                ),
                'box_type_interest' => $request->box_type_interest,
                'budget_min' => $request->budget_min,
                'budget_max' => $request->budget_max,
                'move_in_date' => $request->move_in_date,
            ]);

            // Log the creation
            Log::info("External lead created via API", [
                'lead_id' => $lead->id,
                'tenant_id' => $apiKey->tenant_id,
                'api_key_name' => $apiKey->name,
                'source' => $request->source,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => [
                    'id' => $lead->id,
                    'email' => $lead->email,
                    'score' => $lead->score,
                    'priority' => $lead->priority,
                    'status' => $lead->status,
                    'created_at' => $lead->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Failed to create external lead", [
                'error' => $e->getMessage(),
                'request' => $request->except(['metadata']),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create lead',
                'code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Update lead status
     *
     * POST /api/v1/external/leads/{lead}/status
     */
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $apiKey = $this->authenticateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or missing API key',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        // Verify tenant ownership
        if ($lead->tenant_id !== $apiKey->tenant_id) {
            return response()->json([
                'success' => false,
                'error' => 'Lead not found',
                'code' => 'NOT_FOUND'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $lead->update([
            'status' => $request->status,
            'notes' => $request->notes ? $lead->notes . "\n\n[API Update] " . $request->notes : $lead->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated',
            'data' => [
                'id' => $lead->id,
                'status' => $lead->status,
            ]
        ]);
    }

    /**
     * Get available sites for the tenant
     *
     * GET /api/v1/external/sites
     */
    public function getSites(Request $request): JsonResponse
    {
        $apiKey = $this->authenticateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or missing API key',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        $sites = Site::where('tenant_id', $apiKey->tenant_id)
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'city', 'postal_code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sites
        ]);
    }
}
