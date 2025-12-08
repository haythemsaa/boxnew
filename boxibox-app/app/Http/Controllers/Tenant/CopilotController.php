<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AICopilotService;
use App\Jobs\AI\RevenueGuardianJob;
use App\Jobs\AI\ChurnPredictorJob;
use App\Jobs\AI\CollectionAgentJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class CopilotController extends Controller
{
    protected AICopilotService $copilot;

    public function __construct(AICopilotService $copilot)
    {
        $this->copilot = $copilot;
    }

    /**
     * Display the Copilot chat interface
     */
    public function index(): Response
    {
        $tenantId = auth()->user()->tenant_id;

        // Get initial briefing data
        $briefing = $this->copilot->getDailyBriefing($tenantId);

        return Inertia::render('Tenant/Analytics/Copilot', [
            'briefing' => $briefing,
            'conversationId' => null,
        ]);
    }

    /**
     * Handle chat messages
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|integer',
        ]);

        $tenantId = auth()->user()->tenant_id;

        try {
            $response = $this->copilot->chat(
                $tenantId,
                $request->input('message'),
                $request->input('conversation_id')
            );

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => config('app.debug') ? $e->getMessage() : 'Veuillez reessayer',
            ], 500);
        }
    }

    /**
     * Execute an action from the chat
     */
    public function executeAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string',
            'params' => 'nullable|array',
        ]);

        $tenantId = auth()->user()->tenant_id;

        try {
            $result = $this->copilot->executeAction(
                $tenantId,
                $request->input('action'),
                $request->input('params', [])
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'execution',
            ], 500);
        }
    }

    /**
     * Get daily briefing
     */
    public function briefing(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        try {
            $briefing = $this->copilot->getDailyBriefing($tenantId);
            return response()->json($briefing);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Impossible de generer le briefing',
            ], 500);
        }
    }

    /**
     * Get forecast data
     */
    public function forecast(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:7|max:90',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $days = $request->input('days', 30);

        try {
            $forecast = $this->copilot->getForecast($tenantId, $days);
            return response()->json($forecast);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Impossible de generer les previsions',
            ], 500);
        }
    }

    /**
     * Get churn risk analysis
     */
    public function churnRisk(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        try {
            // Check cache first
            $cached = cache()->get("churn_analysis_{$tenantId}");

            if ($cached) {
                return response()->json($cached);
            }

            // Run analysis
            $customers = $this->copilot->getChurnRiskCustomers();

            return response()->json([
                'customers' => $customers,
                'total_at_risk' => array_sum(array_column($customers, 'monthly_value')),
                'high_risk_count' => count(array_filter($customers, fn($c) => $c['risk_level'] === 'high')),
                'analyzed_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Impossible d\'analyser le risque de churn',
            ], 500);
        }
    }

    /**
     * Get pricing recommendations
     */
    public function pricingRecommendations(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        try {
            $recommendations = $this->copilot->getPricingRecommendations();
            return response()->json([
                'recommendations' => $recommendations,
                'generated_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Impossible de generer les recommandations',
            ], 500);
        }
    }

    /**
     * Trigger AI agents manually
     */
    public function runAgents(Request $request): JsonResponse
    {
        $request->validate([
            'agents' => 'nullable|array',
            'agents.*' => 'string|in:revenue,churn,collection',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $agents = $request->input('agents', ['revenue', 'churn', 'collection']);
        $dispatched = [];

        try {
            if (in_array('revenue', $agents)) {
                RevenueGuardianJob::dispatch($tenantId);
                $dispatched[] = 'Revenue Guardian';
            }

            if (in_array('churn', $agents)) {
                ChurnPredictorJob::dispatch($tenantId);
                $dispatched[] = 'Churn Predictor';
            }

            if (in_array('collection', $agents)) {
                CollectionAgentJob::dispatch($tenantId, false);
                $dispatched[] = 'Collection Agent';
            }

            return response()->json([
                'success' => true,
                'message' => 'Agents lances: ' . implode(', ', $dispatched),
                'agents' => $dispatched,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du lancement des agents',
            ], 500);
        }
    }

    /**
     * Get agent status and recent alerts
     */
    public function agentStatus(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Get recent AI notifications
        $recentAlerts = \App\Models\Notification::where('tenant_id', $tenantId)
            ->where('type', 'ai_alert')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'severity' => $n->data['severity'] ?? 'info',
                'source' => $n->data['source'] ?? 'unknown',
                'read' => $n->read,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        // Get cached analysis results
        $churnAnalysis = cache()->get("churn_analysis_{$tenantId}");
        $collectionStatus = cache()->get("collection_status_{$tenantId}");

        return response()->json([
            'agents' => [
                [
                    'name' => 'Revenue Guardian',
                    'description' => 'Surveille les revenus et detecte les anomalies',
                    'status' => 'active',
                    'schedule' => 'Toutes les heures',
                ],
                [
                    'name' => 'Churn Predictor',
                    'description' => 'Predit les clients a risque de depart',
                    'status' => 'active',
                    'schedule' => 'Quotidien',
                    'last_analysis' => $churnAnalysis['analyzed_at'] ?? null,
                ],
                [
                    'name' => 'Collection Agent',
                    'description' => 'Gere automatiquement les relances d\'impayes',
                    'status' => 'active',
                    'schedule' => 'Quotidien',
                    'last_run' => $collectionStatus['updated_at'] ?? null,
                ],
            ],
            'recent_alerts' => $recentAlerts,
            'stats' => [
                'churn_at_risk' => $churnAnalysis['high_risk_count'] ?? 0,
                'collection_pending' => $collectionStatus['summary']['calls_required'] ?? 0,
            ],
        ]);
    }
}
