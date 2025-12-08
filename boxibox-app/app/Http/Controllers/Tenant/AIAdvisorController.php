<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AIBusinessAdvisorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class AIAdvisorController extends Controller
{
    protected AIBusinessAdvisorService $advisorService;

    public function __construct(AIBusinessAdvisorService $advisorService)
    {
        $this->advisorService = $advisorService;
    }

    /**
     * Display the AI Advisor dashboard
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Cache les conseils pour 15 minutes
        $cacheKey = "ai_advisor_{$tenantId}";
        $advice = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            return $this->advisorService->generateAdvice($tenantId);
        });

        return Inertia::render('Tenant/Analytics/AIAdvisor', [
            'advice' => $advice,
        ]);
    }

    /**
     * Get AI advice via API (for AJAX requests)
     */
    public function getAdvice(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $forceRefresh = $request->boolean('refresh', false);

        $cacheKey = "ai_advisor_{$tenantId}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $advice = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            return $this->advisorService->generateAdvice($tenantId);
        });

        return response()->json($advice);
    }

    /**
     * Refresh AI advice (force recalculation)
     */
    public function refresh(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $cacheKey = "ai_advisor_{$tenantId}";
        Cache::forget($cacheKey);

        $advice = $this->advisorService->generateAdvice($tenantId);
        Cache::put($cacheKey, $advice, now()->addMinutes(15));

        return response()->json($advice);
    }

    /**
     * Get specific category recommendations
     */
    public function getCategory(Request $request, string $category): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $advice = $this->advisorService->generateAdvice($tenantId);

        $filteredRecommendations = array_filter(
            $advice['recommendations'],
            fn($rec) => $rec['category'] === $category
        );

        return response()->json([
            'category' => $category,
            'recommendations' => array_values($filteredRecommendations),
            'metrics' => $advice['metrics'],
        ]);
    }

    /**
     * Mark a recommendation as done/dismissed
     */
    public function dismissRecommendation(Request $request): JsonResponse
    {
        $request->validate([
            'recommendation_id' => 'required|string',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $recommendationId = $request->input('recommendation_id');

        // Stocker les recommandations dismissées dans le cache utilisateur
        $dismissedKey = "ai_advisor_dismissed_{$tenantId}";
        $dismissed = Cache::get($dismissedKey, []);
        $dismissed[] = $recommendationId;
        Cache::put($dismissedKey, array_unique($dismissed), now()->addDays(7));

        return response()->json(['success' => true]);
    }

    /**
     * Get quick metrics summary for dashboard widget
     */
    public function getQuickMetrics(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $cacheKey = "ai_advisor_{$tenantId}";
        $advice = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            return $this->advisorService->generateAdvice($tenantId);
        });

        return response()->json([
            'score' => $advice['score'],
            'critical_count' => count(array_filter(
                $advice['recommendations'],
                fn($r) => $r['priority'] === 'critical'
            )),
            'high_count' => count(array_filter(
                $advice['recommendations'],
                fn($r) => $r['priority'] === 'high'
            )),
            'total_recommendations' => count($advice['recommendations']),
            'key_metrics' => [
                'occupancy_rate' => $advice['metrics']['occupancy_rate'] ?? 0,
                'monthly_revenue' => $advice['metrics']['monthly_revenue'] ?? 0,
                'total_overdue' => $advice['metrics']['total_overdue'] ?? 0,
                'conversion_rate' => $advice['metrics']['prospect_conversion_rate'] ?? 0,
            ],
        ]);
    }
}
