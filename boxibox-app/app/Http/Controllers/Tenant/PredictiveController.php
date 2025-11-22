<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\MLService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PredictiveController extends Controller
{
    protected MLService $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Show predictive analytics dashboard
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        // Get current occupation
        $currentOccupation = \App\Models\Box::where('tenant_id', $tenantId)
            ->selectRaw('
                (COUNT(CASE WHEN status = "occupied" THEN 1 END) * 100.0 / COUNT(*)) as rate
            ')
            ->value('rate') ?? 0;

        return Inertia::render('Tenant/Analytics/Predictive', [
            'currentOccupation' => round($currentOccupation, 2),
        ]);
    }

    /**
     * Get occupation forecast
     */
    public function occupationForecast(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $days = $request->input('days', 30);

        $forecast = $this->mlService->forecastOccupation($tenantId, $days);

        return response()->json($forecast);
    }

    /**
     * Get churn predictions
     */
    public function churnPredictions()
    {
        $tenantId = auth()->user()->tenant_id;

        $predictions = $this->mlService->predictChurn($tenantId);

        // Filter only high and critical risk
        $highRisk = $predictions->filter(function ($prediction) {
            return $prediction['churn_score'] >= 60;
        });

        return response()->json($highRisk->values());
    }

    /**
     * Get upsell opportunities
     */
    public function upsellOpportunities()
    {
        $tenantId = auth()->user()->tenant_id;

        $opportunities = $this->mlService->recommendUpsells($tenantId);

        return response()->json($opportunities);
    }

    /**
     * Optimize pricing for a box
     */
    public function optimizePricing(Request $request, $boxId)
    {
        $box = \App\Models\Box::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($boxId);

        $optimization = $this->mlService->optimizePricing($box);

        return response()->json($optimization);
    }
}
