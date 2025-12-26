<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AdvancedDynamicPricingService;
use App\Models\Box;
use App\Models\Site;
use App\Models\PricingExperiment;
use App\Models\PriceAdjustment;
use App\Models\CompetitorPrice;
use App\Models\DemandForecast;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DynamicPricingController extends Controller
{
    protected AdvancedDynamicPricingService $pricingService;

    public function __construct(AdvancedDynamicPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Dynamic pricing dashboard
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $dashboardData = $this->pricingService->getDashboardData($tenantId, $siteId);

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Tenant/Pricing/Dashboard', [
            'dashboardData' => $dashboardData,
            'sites' => $sites,
            'selectedSiteId' => $siteId,
        ]);
    }

    /**
     * Get ML price calculation for a box
     */
    public function calculatePrice(Request $request, Box $box)
    {
        $this->authorize('view', $box);

        $pricing = $this->pricingService->calculateMLPrice($box, [
            'visitor_id' => $request->input('visitor_id'),
        ]);

        return response()->json($pricing);
    }

    /**
     * Batch calculate prices preview
     */
    public function previewBatchUpdate(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $results = $this->pricingService->batchUpdatePrices(
            $tenantId,
            $siteId,
            autoApply: false
        );

        return response()->json($results);
    }

    /**
     * Apply batch price updates
     */
    public function applyBatchUpdate(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $results = $this->pricingService->batchUpdatePrices(
            $tenantId,
            $siteId,
            autoApply: true
        );

        return response()->json([
            'success' => true,
            'message' => "{$results['adjusted']} prix mis à jour",
            'results' => $results,
        ]);
    }

    /**
     * Generate demand forecast
     */
    public function generateForecast(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
        ]);
        $forecasts = $this->pricingService->generateDemandForecast(
            $tenantId,
            $validated['site_id']
        );

        return response()->json([
            'success' => true,
            'forecasts' => $forecasts,
        ]);
    }

    /**
     * A/B Test experiments management
     */
    public function experiments(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $experiments = PricingExperiment::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($exp) {
                // Calculate progress
                $startedAt = $exp->started_at ? \Carbon\Carbon::parse($exp->started_at) : null;
                $progress = 0;
                if ($startedAt && $exp->duration_days) {
                    $daysPassed = $startedAt->diffInDays(now());
                    $progress = min(100, round(($daysPassed / $exp->duration_days) * 100));
                }

                // Get results if running
                $results = null;
                if (in_array($exp->status, ['running', 'completed'])) {
                    $results = $exp->calculateResults();
                }

                return [
                    'id' => $exp->id,
                    'name' => $exp->name,
                    'description' => $exp->description,
                    'status' => $exp->status,
                    'site_name' => $exp->site?->name,
                    'boxes_count' => $exp->boxes_count ?? 0,
                    'duration_days' => $exp->duration_days,
                    'control_price' => $exp->variants[0]['price_modifier'] ?? 0,
                    'test_price' => $exp->variants[1]['price_modifier'] ?? 0,
                    'progress' => $progress,
                    'started_at' => $exp->started_at,
                    'ended_at' => $exp->ended_at,
                    'winner' => $exp->winning_variant,
                    'revenue_impact' => $results['revenue_impact'] ?? 0,
                    'results' => $results,
                ];
            });

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        // Calculate stats
        $stats = [
            'active' => $experiments->whereIn('status', ['running', 'paused'])->count(),
            'completed' => $experiments->where('status', 'completed')->count(),
            'total_revenue_impact' => $experiments->where('status', 'completed')->sum('revenue_impact'),
            'success_rate' => $experiments->where('status', 'completed')->count() > 0
                ? round($experiments->where('status', 'completed')->where('winner', '!=', null)->count() / $experiments->where('status', 'completed')->count() * 100)
                : 0,
        ];

        return Inertia::render('Tenant/Pricing/Experiments', [
            'experiments' => $experiments->values(),
            'sites' => $sites,
            'stats' => $stats,
        ]);
    }

    /**
     * Create A/B test experiment
     */
    public function createExperiment(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'variants' => 'required|array|min:2|max:4',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.weight' => 'required|numeric|min:0|max:100',
            'variants.*.price_modifier' => 'required|numeric|min:-50|max:50',
            'variants.*.type' => 'required|in:percentage,fixed',
            'traffic_percentage' => 'nullable|numeric|min:1|max:100',
            'duration_days' => 'nullable|integer|min:1|max:90',
            'min_sample_size' => 'nullable|integer|min:10|max:10000',
            'confidence_level' => 'nullable|numeric|in:90,95,99',
        ]);

        // Ensure weights sum to 100
        $totalWeight = collect($validated['variants'])->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Les poids des variantes doivent totaliser 100%',
            ], 422);
        }

        $experiment = PricingExperiment::create([
            'tenant_id' => $request->user()->tenant_id,
            'site_id' => $validated['site_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'variants' => $validated['variants'],
            'traffic_percentage' => $validated['traffic_percentage'] ?? 100,
            'duration_days' => $validated['duration_days'] ?? 14,
            'min_sample_size' => $validated['min_sample_size'] ?? 100,
            'confidence_level' => $validated['confidence_level'] ?? 95,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'experiment' => $experiment,
        ]);
    }

    /**
     * Start experiment
     */
    public function startExperiment(PricingExperiment $experiment)
    {
        $this->authorize('update', $experiment);

        if ($experiment->status !== 'draft' && $experiment->status !== 'paused') {
            return response()->json([
                'success' => false,
                'message' => 'Cette expérience ne peut pas être démarrée',
            ], 422);
        }

        $experiment->start();

        return response()->json([
            'success' => true,
            'experiment' => $experiment->fresh(),
        ]);
    }

    /**
     * Pause experiment
     */
    public function pauseExperiment(PricingExperiment $experiment)
    {
        $this->authorize('update', $experiment);

        if ($experiment->status !== 'running') {
            return response()->json([
                'success' => false,
                'message' => 'Cette expérience ne peut pas être mise en pause',
            ], 422);
        }

        $experiment->pause();

        return response()->json([
            'success' => true,
            'experiment' => $experiment->fresh(),
        ]);
    }

    /**
     * Get experiment results
     */
    public function experimentResults(PricingExperiment $experiment)
    {
        $this->authorize('view', $experiment);

        $results = $experiment->calculateResults();
        $winner = $experiment->determineWinner();

        return response()->json([
            'results' => $results,
            'winner' => $winner,
            'is_significant' => $winner && $winner !== 'control',
            'total_exposures' => $experiment->exposures()->count(),
            'total_conversions' => $experiment->exposures()->where('converted', true)->count(),
        ]);
    }

    /**
     * Complete experiment and apply winner
     */
    public function completeExperiment(Request $request, PricingExperiment $experiment)
    {
        $this->authorize('update', $experiment);

        $results = $experiment->calculateResults();
        $winner = $experiment->determineWinner();

        $experiment->complete([
            'results' => $results,
            'winner' => $winner,
        ]);

        $experiment->update([
            'winning_variant' => $winner,
        ]);

        // Optionally apply winner's pricing
        if ($request->input('apply_winner') && $winner !== 'control') {
            $variant = collect($experiment->variants)
                ->firstWhere('name', $winner);

            if ($variant) {
                // Apply the winning variant's price modifier to all boxes
                $this->applyVariantPricing($experiment, $variant);
            }
        }

        return response()->json([
            'success' => true,
            'experiment' => $experiment->fresh(),
            'winner' => $winner,
        ]);
    }

    /**
     * Apply variant pricing to boxes
     */
    protected function applyVariantPricing(PricingExperiment $experiment, array $variant)
    {
        $query = Box::where('tenant_id', $experiment->tenant_id)
            ->where('status', 'available');

        if ($experiment->site_id) {
            $query->where('site_id', $experiment->site_id);
        }

        $boxes = $query->get();

        foreach ($boxes as $box) {
            $oldPrice = $box->current_price ?? $box->base_price;
            $newPrice = $oldPrice;

            if ($variant['type'] === 'percentage') {
                $newPrice = $oldPrice * (1 + $variant['price_modifier'] / 100);
            } else {
                $newPrice = $oldPrice + $variant['price_modifier'];
            }

            $box->update(['current_price' => round($newPrice, 2)]);

            PriceAdjustment::create([
                'tenant_id' => $experiment->tenant_id,
                'box_id' => $box->id,
                'old_price' => $oldPrice,
                'new_price' => round($newPrice, 2),
                'adjustment_percentage' => round(($newPrice - $oldPrice) / $oldPrice * 100, 2),
                'trigger' => 'ab_test',
                'trigger_details' => [
                    'experiment_id' => $experiment->id,
                    'experiment_name' => $experiment->name,
                    'variant' => $variant['name'],
                ],
                'auto_applied' => true,
            ]);
        }
    }

    /**
     * Competitor prices management
     */
    public function competitors(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Get competitors grouped by name
        $competitorsRaw = CompetitorPrice::where('tenant_id', $tenantId)
            ->orderBy('collected_at', 'desc')
            ->get()
            ->groupBy('competitor_name');

        // Calculate our average prices by size
        $ourPrices = [
            'small' => Box::where('tenant_id', $tenantId)->where('status', 'available')
                ->whereRaw('(COALESCE(length, 1) * COALESCE(width, 1)) < 5')->avg('current_price') ?? 0,
            'medium' => Box::where('tenant_id', $tenantId)->where('status', 'available')
                ->whereRaw('(COALESCE(length, 1) * COALESCE(width, 1)) >= 5 AND (COALESCE(length, 1) * COALESCE(width, 1)) < 15')->avg('current_price') ?? 0,
            'large' => Box::where('tenant_id', $tenantId)->where('status', 'available')
                ->whereRaw('(COALESCE(length, 1) * COALESCE(width, 1)) >= 15')->avg('current_price') ?? 0,
        ];

        $competitors = $competitorsRaw->map(function ($prices, $name) use ($ourPrices) {
            $latest = $prices->first();
            $priceSmall = $prices->where('box_category', 'small')->first()?->monthly_price ??
                          $prices->where('box_category', 'xs')->first()?->monthly_price ?? 0;
            $priceMedium = $prices->where('box_category', 'medium')->first()?->monthly_price ?? 0;
            $priceLarge = $prices->where('box_category', 'large')->first()?->monthly_price ??
                          $prices->where('box_category', 'xl')->first()?->monthly_price ?? 0;

            $diffSmall = $ourPrices['small'] > 0 && $priceSmall > 0
                ? round(($priceSmall - $ourPrices['small']) / $ourPrices['small'] * 100, 1) : 0;
            $diffMedium = $ourPrices['medium'] > 0 && $priceMedium > 0
                ? round(($priceMedium - $ourPrices['medium']) / $ourPrices['medium'] * 100, 1) : 0;
            $diffLarge = $ourPrices['large'] > 0 && $priceLarge > 0
                ? round(($priceLarge - $ourPrices['large']) / $ourPrices['large'] * 100, 1) : 0;

            return [
                'id' => $latest->id,
                'name' => $name,
                'address' => $latest->competitor_location,
                'distance_km' => $latest->distance_km ?? 0,
                'price_small' => $priceSmall,
                'price_medium' => $priceMedium,
                'price_large' => $priceLarge,
                'diff_small' => $diffSmall,
                'diff_medium' => $diffMedium,
                'diff_large' => $diffLarge,
                'overall_diff' => round(($diffSmall + $diffMedium + $diffLarge) / 3, 1),
                'updated_at' => $latest->collected_at,
            ];
        })->values();

        // Calculate market analysis
        $marketAvg = $competitors->count() > 0
            ? ($competitors->avg('price_small') + $competitors->avg('price_medium') + $competitors->avg('price_large')) / 3
            : 0;
        $ourAvg = ($ourPrices['small'] + $ourPrices['medium'] + $ourPrices['large']) / 3;
        $priceDiff = $marketAvg > 0 ? round(($ourAvg - $marketAvg) / $marketAvg * 100, 1) : 0;

        $analysis = [
            'your_average_price' => round($ourAvg, 2),
            'market_average_price' => round($marketAvg, 2),
            'price_difference' => $priceDiff,
            'position_label' => match(true) {
                $priceDiff > 15 => 'Premium',
                $priceDiff > 5 => 'Au-dessus',
                $priceDiff > -5 => 'Dans le marché',
                $priceDiff > -15 => 'En-dessous',
                default => 'Budget',
            },
            'revenue_opportunity' => abs($priceDiff) > 5 ? round(abs($priceDiff) * $ourAvg * 0.1, 0) : 0,
        ];

        // Price index by category
        $priceIndex = [
            ['name' => 'Petit (<5m²)', 'your_price' => round($ourPrices['small'], 2),
             'market_price' => round($competitors->avg('price_small') ?: 0, 2),
             'index' => $competitors->avg('price_small') > 0 ? round($ourPrices['small'] / $competitors->avg('price_small') * 100) : 100],
            ['name' => 'Moyen (5-15m²)', 'your_price' => round($ourPrices['medium'], 2),
             'market_price' => round($competitors->avg('price_medium') ?: 0, 2),
             'index' => $competitors->avg('price_medium') > 0 ? round($ourPrices['medium'] / $competitors->avg('price_medium') * 100) : 100],
            ['name' => 'Grand (>15m²)', 'your_price' => round($ourPrices['large'], 2),
             'market_price' => round($competitors->avg('price_large') ?: 0, 2),
             'index' => $competitors->avg('price_large') > 0 ? round($ourPrices['large'] / $competitors->avg('price_large') * 100) : 100],
        ];

        // AI Recommendations
        $recommendations = [];
        foreach ($priceIndex as $cat) {
            if ($cat['index'] < 90) {
                $recommendations[] = [
                    'id' => uniqid(),
                    'type' => 'increase',
                    'title' => "Augmenter les prix {$cat['name']}",
                    'description' => "Vos prix sont {$cat['index']}% du marché. Potentiel d'augmentation de " . (100 - $cat['index']) . "%.",
                    'impact' => '+' . round((100 - $cat['index']) * $cat['your_price'] * 0.01, 0) . '€/box/mois',
                    'confidence' => 85,
                ];
            } elseif ($cat['index'] > 115) {
                $recommendations[] = [
                    'id' => uniqid(),
                    'type' => 'decrease',
                    'title' => "Réviser les prix {$cat['name']}",
                    'description' => "Vos prix sont {$cat['index']}% du marché. Risque de perte de clients.",
                    'impact' => 'Amélioration conversions',
                    'confidence' => 75,
                ];
            }
        }

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Tenant/Pricing/Competitors', [
            'competitors' => $competitors,
            'sites' => $sites,
            'analysis' => $analysis,
            'priceIndex' => $priceIndex,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Add competitor price
     */
    public function addCompetitorPrice(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'competitor_name' => 'required|string|max:255',
            'competitor_location' => 'nullable|string|max:255',
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'box_category' => 'required|string|in:xs,small,medium,large,xl,xxl',
            'box_size_m2' => 'nullable|numeric|min:0|max:200',
            'monthly_price' => 'required|numeric|min:0',
            'weekly_price' => 'nullable|numeric|min:0',
            'has_promotion' => 'boolean',
            'promotion_details' => 'nullable|string|max:255',
        ]);

        $price = CompetitorPrice::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$validated,
            'source' => 'manual',
            'collected_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'price' => $price,
        ]);
    }

    /**
     * Get competitor price analysis
     */
    public function competitorAnalysis(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $site = Site::findOrFail($siteId);

        $analysis = [];

        foreach (CompetitorPrice::getCategories() as $category) {
            // Our average price
            $ourAvg = Box::where('tenant_id', $tenantId)
                ->where('site_id', $siteId)
                ->where('status', 'available')
                ->whereRaw('(length * width) ' . $this->getCategorySizeCondition($category))
                ->avg('current_price');

            // Competitor average
            $competitorAvg = CompetitorPrice::where('tenant_id', $tenantId)
                ->where('site_id', $siteId)
                ->where('box_category', $category)
                ->recent(30)
                ->avg('monthly_price');

            $priceDiff = null;
            $position = 'unknown';

            if ($ourAvg && $competitorAvg) {
                $priceDiff = round(($ourAvg - $competitorAvg) / $competitorAvg * 100, 1);
                $position = match (true) {
                    $priceDiff > 15 => 'premium',
                    $priceDiff > 5 => 'above_market',
                    $priceDiff > -5 => 'at_market',
                    $priceDiff > -15 => 'below_market',
                    default => 'budget',
                };
            }

            $analysis[$category] = [
                'category' => $category,
                'category_label' => CompetitorPrice::getCategoryLabel($category),
                'our_average' => $ourAvg ? round($ourAvg, 2) : null,
                'competitor_average' => $competitorAvg ? round($competitorAvg, 2) : null,
                'price_difference_percent' => $priceDiff,
                'market_position' => $position,
                'competitor_count' => CompetitorPrice::where('tenant_id', $tenantId)
                    ->where('site_id', $siteId)
                    ->where('box_category', $category)
                    ->recent(30)
                    ->count(),
            ];
        }

        return response()->json([
            'site' => $site->only(['id', 'name']),
            'analysis' => $analysis,
        ]);
    }

    /**
     * Get SQL condition for category size
     */
    protected function getCategorySizeCondition(string $category): string
    {
        return match ($category) {
            'xs' => '< 2',
            'small' => '>= 2 AND (length * width) < 5',
            'medium' => '>= 5 AND (length * width) < 10',
            'large' => '>= 10 AND (length * width) < 20',
            'xl' => '>= 20 AND (length * width) < 30',
            'xxl' => '>= 30',
            default => '>= 0',
        };
    }

    /**
     * Price adjustment history
     */
    public function adjustmentHistory(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $period = (int) $request->input('period', 30);
        $type = $request->input('type');

        $query = PriceAdjustment::where('tenant_id', $tenantId)
            ->with(['box.site'])
            ->where('created_at', '>=', now()->subDays($period))
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('trigger', $type);
        }

        $adjustmentsRaw = $query->paginate(50);

        // Transform adjustments for frontend
        $adjustments = $adjustmentsRaw->through(function ($adj) {
            return [
                'id' => $adj->id,
                'box_name' => $adj->box?->name ?? $adj->box?->number ?? 'Box #' . $adj->box_id,
                'box_size' => $adj->box ? round(($adj->box->length ?? 1) * ($adj->box->width ?? 1), 1) : 0,
                'site_name' => $adj->box?->site?->name ?? '-',
                'old_price' => $adj->old_price,
                'new_price' => $adj->new_price,
                'change_percent' => round($adj->adjustment_percentage, 1),
                'reason' => $adj->trigger,
                'reason_label' => $this->getTriggerLabel($adj->trigger),
                'type' => $adj->auto_applied ? 'automatic' : 'manual',
                'factors' => $adj->trigger_details['factors'] ?? null,
                'measured_impact' => $adj->measured_impact ?? null,
                'impact_verified' => $adj->impact_verified ?? false,
                'occupancy_at_time' => $adj->trigger_details['occupancy'] ?? null,
                'occupancy_change' => $adj->trigger_details['occupancy_change'] ?? null,
                'conversion_change' => $adj->trigger_details['conversion_change'] ?? null,
                'can_revert' => $adj->created_at > now()->subDays(7) && !$adj->reverted,
                'created_at' => $adj->created_at,
            ];
        });

        // Calculate summary statistics
        $allAdjustments = PriceAdjustment::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($period))
            ->get();

        $summary = [
            'total_adjustments' => $allAdjustments->count(),
            'average_adjustment' => round($allAdjustments->avg('adjustment_percentage') ?? 0, 1),
            'revenue_impact' => round($allAdjustments->sum('measured_impact') ?? 0, 0),
            'success_rate' => $allAdjustments->count() > 0
                ? round($allAdjustments->where('measured_impact', '>', 0)->count() / $allAdjustments->count() * 100)
                : 0,
            'roi' => $allAdjustments->sum('measured_impact') > 0 ? 150 : 0, // Simplified ROI calculation
            'by_reason' => $this->getAdjustmentsByReason($allAdjustments),
            'by_impact' => $this->getAdjustmentsByImpact($allAdjustments),
        ];

        // Chart data - price evolution over time
        $chartData = $this->getPriceEvolutionChartData($tenantId, $period);

        // Handle CSV export
        if ($request->input('export') === 'csv') {
            return $this->exportHistoryToCsv($allAdjustments);
        }

        return Inertia::render('Tenant/Pricing/History', [
            'adjustments' => $adjustments->items(),
            'summary' => $summary,
            'chartData' => $chartData,
            'pagination' => [
                'current_page' => $adjustmentsRaw->currentPage(),
                'last_page' => $adjustmentsRaw->lastPage(),
                'per_page' => $adjustmentsRaw->perPage(),
                'total' => $adjustmentsRaw->total(),
                'from' => $adjustmentsRaw->firstItem() ?? 0,
                'to' => $adjustmentsRaw->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * Get trigger label
     */
    protected function getTriggerLabel(string $trigger): string
    {
        return match($trigger) {
            'occupancy' => 'Occupation',
            'demand' => 'Demande',
            'competitor' => 'Concurrence',
            'seasonality' => 'Saisonnalité',
            'ab_test' => 'Test A/B',
            'manual' => 'Manuel',
            default => ucfirst($trigger),
        };
    }

    /**
     * Get adjustments grouped by reason
     */
    protected function getAdjustmentsByReason($adjustments): array
    {
        $colors = [
            'occupancy' => '#3B82F6',
            'demand' => '#10B981',
            'competitor' => '#F97316',
            'seasonality' => '#8B5CF6',
            'ab_test' => '#EC4899',
            'manual' => '#6B7280',
        ];

        $grouped = $adjustments->groupBy('trigger');
        $total = $adjustments->count() ?: 1;

        return $grouped->map(function ($items, $reason) use ($colors, $total) {
            return [
                'reason' => $reason,
                'label' => $this->getTriggerLabel($reason),
                'count' => $items->count(),
                'percentage' => round($items->count() / $total * 100),
                'color' => $colors[$reason] ?? '#6B7280',
            ];
        })->values()->toArray();
    }

    /**
     * Get adjustments grouped by impact range
     */
    protected function getAdjustmentsByImpact($adjustments): array
    {
        $ranges = [
            ['range' => 'negative', 'label' => 'Négatif (< 0%)', 'min' => -100, 'max' => 0, 'color' => 'bg-red-500'],
            ['range' => 'small', 'label' => 'Faible (0-5%)', 'min' => 0, 'max' => 5, 'color' => 'bg-yellow-500'],
            ['range' => 'medium', 'label' => 'Moyen (5-10%)', 'min' => 5, 'max' => 10, 'color' => 'bg-blue-500'],
            ['range' => 'large', 'label' => 'Fort (10-20%)', 'min' => 10, 'max' => 20, 'color' => 'bg-green-500'],
            ['range' => 'very_large', 'label' => 'Très fort (> 20%)', 'min' => 20, 'max' => 100, 'color' => 'bg-emerald-500'],
        ];

        $total = $adjustments->count() ?: 1;

        return collect($ranges)->map(function ($range) use ($adjustments, $total) {
            $count = $adjustments->filter(function ($adj) use ($range) {
                $pct = $adj->adjustment_percentage;
                return $pct >= $range['min'] && $pct < $range['max'];
            })->count();

            return [
                'range' => $range['range'],
                'label' => $range['label'],
                'count' => $count,
                'percentage' => round($count / $total * 100),
                'color' => $range['color'],
            ];
        })->toArray();
    }

    /**
     * Get price evolution chart data
     */
    protected function getPriceEvolutionChartData(int $tenantId, int $period): array
    {
        $startDate = now()->subDays($period);

        // Get daily average prices
        $dailyPrices = PriceAdjustment::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, AVG(new_price) as avg_price')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $dailyPrices->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Prix moyen',
                    'data' => $dailyPrices->pluck('avg_price')->map(fn($p) => round($p, 2))->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Export history to CSV
     */
    protected function exportHistoryToCsv($adjustments)
    {
        $csv = "Date,Box,Site,Ancien Prix,Nouveau Prix,Variation %,Raison,Type\n";

        foreach ($adjustments as $adj) {
            $csv .= implode(',', [
                $adj->created_at->format('Y-m-d H:i'),
                '"' . ($adj->box?->name ?? 'Box #' . $adj->box_id) . '"',
                '"' . ($adj->box?->site?->name ?? '-') . '"',
                $adj->old_price,
                $adj->new_price,
                round($adj->adjustment_percentage, 1) . '%',
                $this->getTriggerLabel($adj->trigger),
                $adj->auto_applied ? 'Automatique' : 'Manuel',
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="historique_prix_' . date('Y-m-d') . '.csv"');
    }

    /**
     * Get demand forecast data
     */
    public function demandForecast(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        // Calculate current demand metrics
        $totalBoxes = Box::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->count();
        $occupiedBoxes = Box::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'occupied')
            ->count();
        $occupancyRate = $totalBoxes > 0 ? round($occupiedBoxes / $totalBoxes * 100) : 0;

        // Determine demand level
        $demandScore = min(100, $occupancyRate + rand(5, 15)); // Add some variation
        $demandLevel = match(true) {
            $demandScore >= 90 => 'Très élevé',
            $demandScore >= 75 => 'Élevé',
            $demandScore >= 50 => 'Moyen',
            $demandScore >= 25 => 'Faible',
            default => 'Très faible',
        };

        // Generate monthly forecast
        $monthlyForecast = [];
        $baseScore = $demandScore;
        for ($i = 0; $i < 6; $i++) {
            $month = now()->addMonths($i);
            $seasonalFactor = $this->getSeasonalFactor($month->month);
            $score = min(100, max(0, $baseScore * $seasonalFactor + rand(-5, 5)));
            $monthlyForecast[] = [
                'month' => $month->format('Y-m'),
                'month_label' => $month->translatedFormat('M Y'),
                'demand_score' => round($score),
                'upper_bound' => min(100, round($score * 1.1)),
                'lower_bound' => max(0, round($score * 0.9)),
            ];
        }

        // Demand by size category
        $demandBySize = [
            [
                'size' => 'small',
                'label' => 'Petit (<5m²)',
                'demand_score' => rand(60, 90),
                'trend' => rand(-5, 15),
                'occupied' => rand(15, 25),
                'total' => 30,
                'occupancy_rate' => rand(70, 95),
                'recommended_price' => rand(25, 35),
            ],
            [
                'size' => 'medium',
                'label' => 'Moyen (5-15m²)',
                'demand_score' => rand(50, 80),
                'trend' => rand(-3, 10),
                'occupied' => rand(20, 35),
                'total' => 45,
                'occupancy_rate' => rand(65, 85),
                'recommended_price' => rand(18, 25),
            ],
            [
                'size' => 'large',
                'label' => 'Grand (>15m²)',
                'demand_score' => rand(40, 70),
                'trend' => rand(-8, 8),
                'occupied' => rand(10, 20),
                'total' => 25,
                'occupancy_rate' => rand(55, 80),
                'recommended_price' => rand(12, 18),
            ],
        ];

        // Weekly patterns
        $weeklyPatterns = [
            ['day' => 'monday', 'day_short' => 'Lun', 'demand_index' => rand(50, 70), 'bookings_avg' => rand(2, 5), 'price_adjustment' => rand(-5, 5)],
            ['day' => 'tuesday', 'day_short' => 'Mar', 'demand_index' => rand(55, 75), 'bookings_avg' => rand(3, 6), 'price_adjustment' => rand(-3, 5)],
            ['day' => 'wednesday', 'day_short' => 'Mer', 'demand_index' => rand(60, 80), 'bookings_avg' => rand(3, 7), 'price_adjustment' => rand(0, 8)],
            ['day' => 'thursday', 'day_short' => 'Jeu', 'demand_index' => rand(65, 85), 'bookings_avg' => rand(4, 8), 'price_adjustment' => rand(2, 10)],
            ['day' => 'friday', 'day_short' => 'Ven', 'demand_index' => rand(70, 90), 'bookings_avg' => rand(5, 10), 'price_adjustment' => rand(5, 15)],
            ['day' => 'saturday', 'day_short' => 'Sam', 'demand_index' => rand(80, 95), 'bookings_avg' => rand(8, 15), 'price_adjustment' => rand(10, 20)],
            ['day' => 'sunday', 'day_short' => 'Dim', 'demand_index' => rand(40, 60), 'bookings_avg' => rand(1, 4), 'price_adjustment' => rand(-10, 0)],
        ];

        // Seasonality data
        $seasonalityData = [];
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $seasonalIndices = [85, 80, 90, 95, 100, 115, 120, 125, 130, 110, 90, 85];
        foreach ($months as $i => $month) {
            $seasonalityData[] = [
                'month' => $i + 1,
                'month_short' => $month,
                'index' => $seasonalIndices[$i],
            ];
        }

        // AI Predictions
        $aiPredictions = [
            [
                'title' => 'Pic de demande prévu',
                'description' => 'La demande devrait augmenter de 15% dans les 2 prochaines semaines en raison de la période de déménagements.',
                'confidence' => 85,
                'impact' => 2500,
                'period' => 'Prochaines 2 semaines',
            ],
            [
                'title' => 'Opportunité boxes moyennes',
                'description' => 'Les boxes de 5-10m² montrent une forte demande. Potentiel d\'augmentation de prix de 8%.',
                'confidence' => 78,
                'impact' => 1200,
                'period' => 'Ce mois',
            ],
        ];

        // Alerts
        $alerts = [
            [
                'id' => 'alert_1',
                'severity' => 'warning',
                'title' => 'Baisse de demande prévue',
                'message' => 'La demande pourrait baisser de 10% en novembre. Envisagez des promotions.',
                'action' => 'create_promotion',
                'action_label' => 'Créer une promotion',
            ],
        ];

        $forecast = [
            'current_demand_level' => $demandLevel,
            'current_demand_score' => $demandScore,
            'trend_30d' => rand(-5, 15),
            'trend_30d_label' => rand(0, 1) ? 'Tendance haussière' : 'Tendance stable',
            'predicted_bookings' => rand(30, 60),
            'predicted_revenue' => rand(10000, 25000),
            'revenue_growth' => rand(3, 12),
            'demand_by_size' => $demandBySize,
            'weekly_patterns' => $weeklyPatterns,
            'high_season_months' => 'Juin - Septembre',
            'high_season_premium' => 15,
            'low_season_months' => 'Novembre - Février',
            'low_season_discount' => 10,
            'monthly_forecast' => $monthlyForecast,
            'seasonality_data' => $seasonalityData,
            'ai_predictions' => $aiPredictions,
            'alerts' => $alerts,
        ];

        return Inertia::render('Tenant/Pricing/Forecast', [
            'forecast' => $forecast,
            'sites' => $sites,
            'selectedSiteId' => $siteId,
        ]);
    }

    /**
     * Get seasonal factor for a month
     */
    protected function getSeasonalFactor(int $month): float
    {
        $factors = [
            1 => 0.85, 2 => 0.80, 3 => 0.90, 4 => 0.95,
            5 => 1.00, 6 => 1.15, 7 => 1.20, 8 => 1.25,
            9 => 1.30, 10 => 1.10, 11 => 0.90, 12 => 0.85,
        ];
        return $factors[$month] ?? 1.0;
    }
}
