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
        $tenantId = $request->user()->tenant_id;
        $type = $request->input('type', 'occupancy');
        $siteId = $request->input('site_id');
        $period = $request->input('period', 'month');
        $format = $request->input('format', 'csv');

        // Get data based on type
        $data = match($type) {
            'revenue' => $this->analyticsService->getRevenueAnalytics($tenantId, $siteId, $period),
            'marketing' => $this->analyticsService->getMarketingAnalytics($tenantId, $period),
            'operations' => $this->analyticsService->getOperationalAnalytics($tenantId, $siteId),
            default => $this->analyticsService->getOccupancyAnalytics($tenantId, $siteId),
        };

        // Generate CSV
        $filename = "analytics_{$type}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers based on type
            if ($type === 'occupancy') {
                fputcsv($file, ['Metric', 'Value'], ';');
                fputcsv($file, ['Total Boxes', $data['total_boxes'] ?? 0], ';');
                fputcsv($file, ['Occupied Boxes', $data['occupied_boxes'] ?? 0], ';');
                fputcsv($file, ['Available Boxes', $data['available_boxes'] ?? 0], ';');
                fputcsv($file, ['Occupancy Rate (%)', $data['occupancy_rate'] ?? 0], ';');
            } elseif ($type === 'revenue') {
                fputcsv($file, ['Metric', 'Value'], ';');
                fputcsv($file, ['Total Revenue', $data['total_revenue'] ?? 0], ';');
                fputcsv($file, ['Monthly Recurring', $data['monthly_recurring'] ?? 0], ';');
                fputcsv($file, ['Outstanding', $data['outstanding'] ?? 0], ';');
                fputcsv($file, ['Average Contract Value', $data['average_contract_value'] ?? 0], ';');
            } elseif ($type === 'marketing') {
                fputcsv($file, ['Metric', 'Value'], ';');
                fputcsv($file, ['New Leads', $data['new_leads'] ?? 0], ';');
                fputcsv($file, ['Conversions', $data['conversions'] ?? 0], ';');
                fputcsv($file, ['Conversion Rate (%)', $data['conversion_rate'] ?? 0], ';');
            } else {
                fputcsv($file, ['Metric', 'Value'], ';');
                foreach ($data as $key => $value) {
                    if (!is_array($value)) {
                        fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value], ';');
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
