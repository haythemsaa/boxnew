<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Prospect;
use App\Services\MLService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class AIInsightsController extends Controller
{
    protected MLService $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Main AI Insights Dashboard
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        // Get summary stats
        $stats = Cache::remember("ai_insights_stats_{$tenantId}", 300, function () use ($tenantId) {
            return [
                'churn_at_risk' => Customer::where('tenant_id', $tenantId)
                    ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
                    ->count() > 0 ? $this->getChurnAtRiskCount($tenantId) : 0,
                'upsell_opportunities' => $this->getUpsellOpportunitiesCount($tenantId),
                'pricing_optimizations' => $this->getPricingOptimizationsCount($tenantId),
                'hot_leads' => Lead::where('tenant_id', $tenantId)
                    ->where('score', '>=', 70)
                    ->whereNull('converted_at')
                    ->count() + Prospect::where('tenant_id', $tenantId)
                    ->where('priority', 'hot')
                    ->whereNotIn('status', ['converted', 'lost'])
                    ->count(),
            ];
        });

        return Inertia::render('Tenant/Analytics/AIInsights', [
            'stats' => $stats,
        ]);
    }

    /**
     * Churn Prediction page
     */
    public function churnPrediction()
    {
        $tenantId = auth()->user()->tenant_id;

        $churnData = Cache::remember("ai_churn_{$tenantId}", 600, function () use ($tenantId) {
            return $this->mlService->predictChurn($tenantId);
        });

        // Group by risk level
        $byRiskLevel = $churnData->groupBy('risk_level')->map->count();

        return Inertia::render('Tenant/Analytics/ChurnPrediction', [
            'customers' => $churnData->take(50), // Top 50 at risk
            'summary' => [
                'critical' => $byRiskLevel['critical'] ?? 0,
                'high' => $byRiskLevel['high'] ?? 0,
                'medium' => $byRiskLevel['medium'] ?? 0,
                'low' => $byRiskLevel['low'] ?? 0,
                'total_analyzed' => $churnData->count(),
            ],
        ]);
    }

    /**
     * Pricing Optimization page
     */
    public function pricingOptimization()
    {
        $tenantId = auth()->user()->tenant_id;

        $boxes = Box::where('tenant_id', $tenantId)
            ->where('status', '!=', 'maintenance')
            ->with('site')
            ->get();

        $optimizations = $boxes->map(function ($box) {
            $optimization = $this->mlService->optimizePricing($box);
            return array_merge($optimization, [
                'box_id' => $box->id,
                'box_code' => $box->number,
                'box_size' => $box->size,
                'site_name' => $box->site?->name,
                'status' => $box->status,
            ]);
        })->filter(function ($opt) {
            // Only show boxes with significant optimization potential
            return abs($opt['adjustment_percent']) >= 5;
        })->sortByDesc(function ($opt) {
            return abs($opt['estimated_revenue_impact']);
        })->values();

        // Calculate total potential revenue
        $totalPotentialRevenue = $optimizations->sum('estimated_revenue_impact');

        return Inertia::render('Tenant/Analytics/PricingOptimization', [
            'optimizations' => $optimizations->take(30),
            'summary' => [
                'boxes_analyzed' => $boxes->count(),
                'boxes_to_optimize' => $optimizations->count(),
                'total_potential_revenue' => round($totalPotentialRevenue, 2),
                'average_adjustment' => round($optimizations->avg('adjustment_percent') ?? 0, 1),
            ],
        ]);
    }

    /**
     * Upsell Opportunities page
     */
    public function upsellOpportunities()
    {
        $tenantId = auth()->user()->tenant_id;

        $upsells = Cache::remember("ai_upsells_{$tenantId}", 600, function () use ($tenantId) {
            return $this->mlService->recommendUpsells($tenantId);
        });

        $totalPotentialRevenue = $upsells->sum('estimated_additional_revenue') * 12; // Annual

        return Inertia::render('Tenant/Analytics/UpsellOpportunities', [
            'opportunities' => $upsells->take(30),
            'summary' => [
                'total_opportunities' => $upsells->count(),
                'high_score_count' => $upsells->where('upsell_score', '>=', 70)->count(),
                'total_potential_revenue' => round($totalPotentialRevenue, 2),
            ],
        ]);
    }

    /**
     * Occupation Forecast page
     */
    public function occupationForecast()
    {
        $tenantId = auth()->user()->tenant_id;

        $forecast = Cache::remember("ai_forecast_{$tenantId}", 3600, function () use ($tenantId) {
            return $this->mlService->forecastOccupation($tenantId, 90);
        });

        // Current occupation
        $totalBoxes = Box::where('tenant_id', $tenantId)->count();
        $occupiedBoxes = Box::where('tenant_id', $tenantId)->where('status', 'occupied')->count();
        $currentOccupation = $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0;

        return Inertia::render('Tenant/Analytics/OccupationForecast', [
            'forecast' => $forecast['forecast'],
            'current_occupation' => $currentOccupation,
            'trend' => $forecast['trend'],
            'accuracy' => $forecast['accuracy'],
        ]);
    }

    /**
     * Hot Leads Dashboard
     */
    public function hotLeads()
    {
        $tenantId = auth()->user()->tenant_id;

        // Get hot leads
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->orderByDesc('score')
            ->with('site')
            ->take(20)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'type' => 'lead',
                    'name' => $lead->full_name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'score' => $lead->score,
                    'priority' => $lead->priority ?? ($lead->score >= 70 ? 'hot' : ($lead->score >= 45 ? 'warm' : 'cold')),
                    'source' => $lead->source,
                    'site' => $lead->site?->name,
                    'move_in_date' => $lead->move_in_date?->format('d/m/Y'),
                    'created_at' => $lead->created_at->diffForHumans(),
                    'url' => "/tenant/crm/leads/{$lead->id}",
                ];
            });

        // Get hot prospects
        $prospects = Prospect::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['converted', 'lost'])
            ->orderByDesc('score')
            ->take(20)
            ->get()
            ->map(function ($prospect) {
                return [
                    'id' => $prospect->id,
                    'type' => 'prospect',
                    'name' => $prospect->full_name,
                    'email' => $prospect->email,
                    'phone' => $prospect->phone,
                    'score' => $prospect->score ?? 0,
                    'priority' => $prospect->priority ?? 'cold',
                    'source' => $prospect->source,
                    'site' => null,
                    'move_in_date' => $prospect->move_in_date?->format('d/m/Y'),
                    'created_at' => $prospect->created_at->diffForHumans(),
                    'url' => "/tenant/crm/prospects/{$prospect->id}",
                ];
            });

        // Combine and sort
        $combined = $leads->concat($prospects)
            ->sortByDesc('score')
            ->values();

        return Inertia::render('Tenant/Analytics/HotLeads', [
            'leads' => $combined->take(30),
            'summary' => [
                'total_hot' => $combined->where('priority', 'hot')->count(),
                'total_warm' => $combined->where('priority', 'warm')->count(),
                'total_cold' => $combined->where('priority', 'cold')->count(),
                'needs_action' => $combined->where('score', '>=', 70)->count(),
            ],
        ]);
    }

    /**
     * Apply pricing optimization to a box
     */
    public function applyPricing(Request $request, Box $box)
    {
        $this->authorize('update', $box);

        $validated = $request->validate([
            'new_price' => 'required|numeric|min:0',
        ]);

        $oldPrice = $box->price;
        $box->update(['price' => $validated['new_price']]);

        // Log the change
        activity()
            ->performedOn($box)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_price' => $oldPrice,
                'new_price' => $validated['new_price'],
                'source' => 'ai_optimization',
            ])
            ->log('Price optimized by AI recommendation');

        // Clear cache
        Cache::forget("ai_insights_stats_{$box->tenant_id}");

        return back()->with('success', "Prix mis à jour: {$oldPrice}€ → {$validated['new_price']}€");
    }

    /**
     * Get API data for charts
     */
    public function getChartData(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $type = $request->get('type', 'forecast');

        switch ($type) {
            case 'forecast':
                return response()->json($this->mlService->forecastOccupation($tenantId, 90));

            case 'churn':
                $churnData = $this->mlService->predictChurn($tenantId);
                return response()->json([
                    'distribution' => $churnData->groupBy('risk_level')->map->count(),
                    'top_at_risk' => $churnData->take(10),
                ]);

            case 'revenue':
                return response()->json($this->getRevenueProjection($tenantId));

            default:
                return response()->json(['error' => 'Unknown chart type'], 400);
        }
    }

    /**
     * Helper: Get churn at risk count
     */
    private function getChurnAtRiskCount(int $tenantId): int
    {
        $churnData = $this->mlService->predictChurn($tenantId);
        return $churnData->whereIn('risk_level', ['critical', 'high'])->count();
    }

    /**
     * Helper: Get upsell opportunities count
     */
    private function getUpsellOpportunitiesCount(int $tenantId): int
    {
        $upsells = $this->mlService->recommendUpsells($tenantId);
        return $upsells->where('upsell_score', '>=', 60)->count();
    }

    /**
     * Helper: Get pricing optimizations count
     */
    private function getPricingOptimizationsCount(int $tenantId): int
    {
        $boxes = Box::where('tenant_id', $tenantId)->get();
        return $boxes->filter(function ($box) {
            $opt = $this->mlService->optimizePricing($box);
            return abs($opt['adjustment_percent']) >= 10;
        })->count();
    }

    /**
     * Helper: Get revenue projection
     */
    private function getRevenueProjection(int $tenantId): array
    {
        $currentMRR = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');

        $forecast = [];
        for ($month = 1; $month <= 12; $month++) {
            $growthRate = 1 + (rand(1, 5) / 100); // 1-5% monthly growth
            $churnRate = rand(1, 3) / 100; // 1-3% churn
            $netGrowth = $growthRate - $churnRate;
            $currentMRR = $currentMRR * $netGrowth;

            $forecast[] = [
                'month' => now()->addMonths($month)->format('M Y'),
                'projected_mrr' => round($currentMRR, 2),
                'lower_bound' => round($currentMRR * 0.9, 2),
                'upper_bound' => round($currentMRR * 1.1, 2),
            ];
        }

        return $forecast;
    }
}
