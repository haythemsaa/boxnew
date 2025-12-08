<?php

namespace App\Jobs\AI;

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChurnPredictorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) return;

        $now = Carbon::now();
        $highRiskCustomers = [];
        $totalAtRisk = 0;

        // Get all active customers
        $customers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->with(['contracts' => fn($q) => $q->where('status', 'active')])
            ->get();

        foreach ($customers as $customer) {
            $riskScore = 0;
            $riskFactors = [];
            $monthlyValue = 0;

            foreach ($customer->contracts as $contract) {
                $monthlyValue += $contract->monthly_price;

                // Factor 1: Contract ending within 30 days
                $daysToEnd = $now->diffInDays($contract->end_date, false);
                if ($daysToEnd > 0 && $daysToEnd <= 30) {
                    $riskScore += 25;
                    $riskFactors[] = [
                        'factor' => 'contract_expiring',
                        'description' => "Contrat expire dans {$daysToEnd} jours",
                        'weight' => 25,
                    ];
                }

                // Factor 2: Payment issues
                $latePayments = Invoice::where('customer_id', $customer->id)
                    ->where('due_date', '<', $now)
                    ->where('status', '!=', 'paid')
                    ->count();

                if ($latePayments > 0) {
                    $paymentRisk = min(25, $latePayments * 8);
                    $riskScore += $paymentRisk;
                    $riskFactors[] = [
                        'factor' => 'payment_issues',
                        'description' => "{$latePayments} facture(s) en retard",
                        'weight' => $paymentRisk,
                    ];
                }

                // Factor 3: No recent communication
                $daysSinceContact = $now->diffInDays($customer->updated_at);
                if ($daysSinceContact > 90) {
                    $riskScore += 15;
                    $riskFactors[] = [
                        'factor' => 'no_contact',
                        'description' => "Aucune interaction depuis {$daysSinceContact} jours",
                        'weight' => 15,
                    ];
                }

                // Factor 4: Short contract duration (opportunistic customer)
                $contractMonths = $contract->created_at->diffInMonths($contract->end_date);
                if ($contractMonths <= 3) {
                    $riskScore += 15;
                    $riskFactors[] = [
                        'factor' => 'short_term',
                        'description' => "Contrat courte duree ({$contractMonths} mois)",
                        'weight' => 15,
                    ];
                }

                // Factor 5: Price sensitivity (compare to average)
                $avgPrice = Contract::where('tenant_id', $this->tenantId)
                    ->where('status', 'active')
                    ->avg('monthly_price') ?? 0;

                if ($avgPrice > 0 && $contract->monthly_price > $avgPrice * 1.2) {
                    $riskScore += 10;
                    $riskFactors[] = [
                        'factor' => 'price_sensitive',
                        'description' => "Prix au-dessus de la moyenne (+20%)",
                        'weight' => 10,
                    ];
                }
            }

            // Cap risk score at 100
            $riskScore = min(100, $riskScore);

            if ($riskScore >= 40) {
                $riskLevel = $riskScore >= 70 ? 'high' : ($riskScore >= 50 ? 'medium' : 'low');

                $highRiskCustomers[] = [
                    'customer_id' => $customer->id,
                    'name' => $customer->full_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'monthly_value' => $monthlyValue,
                    'factors' => $riskFactors,
                    'recommended_action' => $this->getRecommendedAction($riskScore, $riskFactors),
                ];

                $totalAtRisk += $monthlyValue;
            }
        }

        // Sort by risk score descending
        usort($highRiskCustomers, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        // Create alerts for high-risk customers
        $highRiskCount = count(array_filter($highRiskCustomers, fn($c) => $c['risk_level'] === 'high'));

        if ($highRiskCount > 0) {
            $this->createChurnAlert($highRiskCustomers, $highRiskCount, $totalAtRisk);
        }

        // Store analysis results for API access
        cache()->put(
            "churn_analysis_{$this->tenantId}",
            [
                'customers' => $highRiskCustomers,
                'total_at_risk' => $totalAtRisk,
                'high_risk_count' => $highRiskCount,
                'analyzed_at' => $now->toIso8601String(),
            ],
            now()->addHours(6)
        );

        Log::info("ChurnPredictorJob completed for tenant {$this->tenantId}", [
            'high_risk' => $highRiskCount,
            'total_at_risk' => $totalAtRisk,
        ]);
    }

    protected function getRecommendedAction(int $riskScore, array $factors): string
    {
        $hasPaymentIssues = collect($factors)->contains('factor', 'payment_issues');
        $hasExpiring = collect($factors)->contains('factor', 'contract_expiring');
        $hasNoContact = collect($factors)->contains('factor', 'no_contact');

        if ($riskScore >= 70) {
            if ($hasPaymentIssues) {
                return 'URGENT: Appelez immediatement et proposez un plan de paiement';
            }
            return 'URGENT: Appelez le client et proposez une offre de fidelisation (-15%)';
        }

        if ($hasExpiring) {
            return 'Envoyez une offre de renouvellement avec -10% pour 12 mois';
        }

        if ($hasNoContact) {
            return 'Envoyez un email de satisfaction et proposez un upgrade';
        }

        return 'Planifiez un appel de courtoisie et envoyez une offre speciale';
    }

    protected function createChurnAlert(array $customers, int $highRiskCount, float $totalAtRisk): void
    {
        $top3 = array_slice($customers, 0, 3);
        $customerNames = implode(', ', array_column($top3, 'name'));

        $adminUsers = \App\Models\User::where('tenant_id', $this->tenantId)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($adminUsers as $user) {
            Notification::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'type' => 'ai_alert',
                'title' => "[AI] {$highRiskCount} client(s) a haut risque de depart",
                'message' => sprintf(
                    '%.0f EUR/mois de revenus menaces. Clients prioritaires: %s. Action recommandee: contactez-les aujourd\'hui.',
                    $totalAtRisk,
                    $customerNames
                ),
                'data' => [
                    'alert_type' => 'churn_prediction',
                    'severity' => 'high',
                    'high_risk_customers' => array_slice($customers, 0, 5),
                    'total_at_risk' => $totalAtRisk,
                    'source' => 'ChurnPredictor',
                ],
                'read' => false,
            ]);
        }
    }
}
