<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\RevenueForecastingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevenueForecastingController extends Controller
{
    public function __construct(
        protected RevenueForecastingService $forecastService
    ) {}

    /**
     * Main revenue forecasting dashboard
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $months = $request->input('months', 12);

        $forecast = $this->forecastService->getForecast($tenantId, $months);

        return Inertia::render('Tenant/Analytics/RevenueForecast', [
            'forecast' => $forecast,
            'months' => $months,
        ]);
    }

    /**
     * Get MRR dashboard data
     */
    public function mrr(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $data = [
            'current_mrr' => $this->forecastService->calculateCurrentMRR($tenantId),
            'growth_metrics' => $this->forecastService->calculateGrowthMetrics($tenantId),
            'breakdown' => $this->forecastService->getRevenueBreakdown($tenantId),
            'historical' => $this->forecastService->getHistoricalRevenue($tenantId, 12),
        ];

        return Inertia::render('Tenant/Analytics/MRRDashboard', [
            'data' => $data,
        ]);
    }

    /**
     * Get scenarios comparison
     */
    public function scenarios(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $months = $request->input('months', 12);

        $scenarios = $this->forecastService->generateScenarios($tenantId, $months);

        return response()->json([
            'success' => true,
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Get revenue at risk
     */
    public function atRisk(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $atRisk = $this->forecastService->calculateRevenueAtRisk($tenantId);

        return response()->json([
            'success' => true,
            'at_risk' => $atRisk,
        ]);
    }

    /**
     * Get pipeline revenue
     */
    public function pipeline(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $pipeline = $this->forecastService->calculatePipelineRevenue($tenantId);

        return response()->json([
            'success' => true,
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Refresh forecast data
     */
    public function refresh(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $months = $request->input('months', 12);

        // Clear cache
        cache()->forget("revenue_forecast_{$tenantId}_{$months}_" . now()->format('Y-m-d'));

        $forecast = $this->forecastService->getForecast($tenantId, $months);

        return response()->json([
            'success' => true,
            'forecast' => $forecast,
        ]);
    }

    /**
     * Export forecast to CSV
     */
    public function export(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $months = $request->input('months', 12);

        $forecast = $this->forecastService->getForecast($tenantId, $months);

        $csv = "Month,Predicted MRR,Lower Bound,Upper Bound,Confidence,New Revenue,Churn Loss,Net Change,Annual Projection\n";

        foreach ($forecast['forecast'] as $month) {
            $csv .= implode(',', [
                $month['month_label'],
                $month['predicted_mrr'],
                $month['lower_bound'],
                $month['upper_bound'],
                $month['confidence'] . '%',
                $month['breakdown']['new_revenue'],
                $month['breakdown']['churn_loss'],
                $month['breakdown']['net_change'],
                $month['annual_projection'],
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="revenue_forecast_' . date('Y-m-d') . '.csv"');
    }
}
