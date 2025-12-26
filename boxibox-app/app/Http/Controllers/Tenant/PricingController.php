<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\DynamicPricingService;
use App\Models\Site;
use App\Models\Box;
use App\Models\PricingStrategy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingController extends Controller
{
    public function __construct(
        protected DynamicPricingService $pricingService
    ) {}

    /**
     * Display pricing dashboard
     */
    public function dashboard(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        // Get site (default to first if not specified)
        $site = $siteId
            ? Site::find($siteId)
            : Site::where('tenant_id', $tenantId)->first();

        if (!$site) {
            return redirect()->route('tenant.sites.index')
                ->with('error', 'Please create a site first.');
        }

        $analytics = $this->pricingService->getPricingAnalytics($site);

        return Inertia::render('Tenant/Pricing/Dashboard', [
            'analytics' => $analytics,
            'site' => $site,
        ]);
    }

    /**
     * Calculate optimal price for a box
     */
    public function calculateOptimalPrice(Request $request, Box $box)
    {
        $request->validate([
            'duration' => 'nullable|integer|min:1|max:24',
            'customer_type' => 'nullable|in:new,returning,vip',
        ]);

        $optimalPrice = $this->pricingService->calculateOptimalPrice($box, $request->only(['duration', 'customer_type']));

        $currentPrice = $box->current_price ?? $box->base_price ?? 0;
        return response()->json([
            'optimal_price' => $optimalPrice,
            'current_price' => $currentPrice,
            'difference' => $optimalPrice - $currentPrice,
            'difference_percentage' => $currentPrice > 0 ? (($optimalPrice - $currentPrice) / $currentPrice) * 100 : 0,
        ]);
    }

    /**
     * Apply recommended pricing action
     */
    public function applyRecommendation(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $request->validate([
            'action' => 'required|string',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
        ]);
        $siteId = $request->input('site_id');
        $action = $request->input('action');

        $site = $siteId
            ? Site::find($siteId)
            : Site::where('tenant_id', $tenantId)->first();

        switch ($action) {
            case 'enable_dynamic_pricing':
                // Enable dynamic pricing for all boxes in site
                Box::where('site_id', $site->id)->each(function ($box) {
                    $optimalPrice = $this->pricingService->calculateOptimalPrice($box);
                    $box->update(['current_price' => $optimalPrice]);
                });
                break;

            case 'create_promotion':
                // Create a promotional pricing strategy
                PricingStrategy::create([
                    'tenant_id' => $tenantId,
                    'site_id' => $site->id,
                    'name' => 'Low Occupancy Promotion',
                    'description' => 'Automatic promotion for low occupancy periods',
                    'strategy_type' => 'promotion',
                    'rules' => [
                        'trigger' => 'occupancy_below',
                        'threshold' => 0.70,
                        'discount' => 25,
                    ],
                    'is_active' => true,
                    'min_discount_percentage' => 15,
                    'max_discount_percentage' => 25,
                ]);
                break;

            case 'adjust_prices':
                // Increase prices based on high occupancy
                $occupancyRate = $this->pricingService->calculateOccupancyRate($site);
                if ($occupancyRate > 0.90) {
                    Box::where('site_id', $site->id)
                        ->where('status', 'available')
                        ->each(function ($box) {
                            $box->update([
                                'current_price' => ($box->current_price ?? $box->base_price) * 1.10 // +10%
                            ]);
                        });
                }
                break;
        }

        return redirect()->back()->with('success', 'Pricing recommendation applied successfully!');
    }

    /**
     * List pricing strategies
     */
    public function strategies(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $strategies = PricingStrategy::where('tenant_id', $tenantId)
            ->with('site')
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Pricing/Strategies', [
            'strategies' => $strategies,
        ]);
    }

    /**
     * Create pricing strategy
     */
    public function storeStrategy(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strategy_type' => 'required|in:occupancy,seasonal,duration,promotion',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'rules' => 'required|array',
            'min_discount_percentage' => 'required|numeric|min:0|max:100',
            'max_discount_percentage' => 'required|numeric|min:0|max:100',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        PricingStrategy::create($validated);

        return redirect()->back()->with('success', 'Pricing strategy created successfully!');
    }
}
