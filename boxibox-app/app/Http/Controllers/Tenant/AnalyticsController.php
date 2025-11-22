<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AdvancedAnalyticsService;
use App\Services\CRMService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AdvancedAnalyticsService $analyticsService,
        protected CRMService $crmService
    ) {}

    /**
     * Display occupancy analytics
     */
    public function occupancy(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $analytics = $this->analyticsService->getOccupancyAnalytics($tenantId, $siteId);

        return Inertia::render('Tenant/Analytics/Occupancy', [
            'analytics' => $analytics,
        ]);
    }

    /**
     * Display revenue analytics
     */
    public function revenue(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');
        $period = $request->input('period', 'month');

        $analytics = $this->analyticsService->getRevenueAnalytics($tenantId, $siteId, $period);

        return Inertia::render('Tenant/Analytics/Revenue', [
            'analytics' => $analytics,
        ]);
    }

    /**
     * Display marketing analytics
     */
    public function marketing(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $period = $request->input('period', 'month');

        $analytics = $this->analyticsService->getMarketingAnalytics($tenantId, $period);
        $funnel = $this->crmService->getFunnelMetrics($tenantId, now()->subMonth(), now());
        $campaigns = $this->crmService->getCampaignPerformance($tenantId);

        return Inertia::render('Tenant/Analytics/Marketing', [
            'analytics' => $analytics,
            'funnel' => $funnel,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Display operational analytics
     */
    public function operations(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $analytics = $this->analyticsService->getOperationalAnalytics($tenantId, $siteId);

        return Inertia::render('Tenant/Analytics/Operations', [
            'analytics' => $analytics,
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        // Implementation for exporting analytics to Excel/PDF
        // Would use Laravel Excel or similar package
    }
}
