<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ChurnPredictionService;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChurnPredictionController extends Controller
{
    public function __construct(
        protected ChurnPredictionService $churnService
    ) {}

    /**
     * Main churn prediction dashboard
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $analysis = $this->churnService->getChurnAnalysis($tenantId);

        return Inertia::render('Tenant/Analytics/ChurnPrediction', [
            'analysis' => $analysis,
        ]);
    }

    /**
     * Get detailed prediction for a specific customer
     */
    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        $prediction = $this->churnService->predictCustomerChurn($customer);

        return response()->json([
            'success' => true,
            'prediction' => $prediction,
        ]);
    }

    /**
     * Refresh predictions
     */
    public function refresh(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Clear cache to force refresh
        cache()->forget("churn_analysis_{$tenantId}_" . now()->format('Y-m-d-H'));

        $analysis = $this->churnService->getChurnAnalysis($tenantId);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
        ]);
    }

    /**
     * Generate retention campaign
     */
    public function generateCampaign(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $campaigns = $this->churnService->generateRetentionCampaign($tenantId);

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Get at-risk customers list
     */
    public function atRiskCustomers(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $riskLevel = $request->input('risk_level', 'all');

        $analysis = $this->churnService->getChurnAnalysis($tenantId);
        $predictions = collect($analysis['predictions']);

        if ($riskLevel !== 'all') {
            $predictions = $predictions->where('risk_level', $riskLevel);
        }

        return Inertia::render('Tenant/Analytics/ChurnAtRisk', [
            'customers' => $predictions->values(),
            'summary' => $analysis['summary'],
            'riskLevel' => $riskLevel,
        ]);
    }

    /**
     * Export at-risk customers to CSV
     */
    public function export(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $analysis = $this->churnService->getChurnAnalysis($tenantId);

        $csv = "Customer ID,Name,Email,Phone,Risk Level,Probability,Monthly Revenue,Days Until Expiry,Top Risk Factor,Recommended Action\n";

        foreach ($analysis['predictions'] as $pred) {
            $topFactor = $pred['top_risk_factors'][0]['factor'] ?? 'N/A';
            $action = $pred['recommended_actions'][0]['action'] ?? 'N/A';

            $csv .= implode(',', [
                $pred['customer_id'],
                '"' . str_replace('"', '""', $pred['customer_name']) . '"',
                $pred['email'],
                $pred['phone'] ?? '',
                $pred['risk_level'],
                $pred['probability'] . '%',
                $pred['monthly_revenue'] . ' EUR',
                $pred['days_until_expiry'] ?? 'N/A',
                '"' . str_replace('"', '""', $topFactor) . '"',
                '"' . str_replace('"', '""', $action) . '"',
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="churn_predictions_' . date('Y-m-d') . '.csv"');
    }
}
