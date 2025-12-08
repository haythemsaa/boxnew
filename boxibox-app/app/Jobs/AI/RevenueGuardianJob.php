<?php

namespace App\Jobs\AI;

use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Box;
use App\Models\Notification;
use App\Services\AICopilotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RevenueGuardianJob implements ShouldQueue
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
        $alerts = [];

        // Check 1: Revenue drop compared to last month
        $currentRevenue = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('total');

        $lastMonthRevenue = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', $now->copy()->subMonth()->month)
            ->whereYear('paid_at', $now->copy()->subMonth()->year)
            ->sum('total');

        if ($lastMonthRevenue > 0) {
            $revenueChange = (($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;

            if ($revenueChange < -15) {
                $alerts[] = [
                    'type' => 'revenue_drop',
                    'severity' => 'critical',
                    'title' => 'Baisse critique des revenus',
                    'message' => sprintf(
                        'Les revenus ont baisse de %.1f%% ce mois (%.0f EUR vs %.0f EUR le mois dernier). Action immediate recommandee.',
                        abs($revenueChange),
                        $currentRevenue,
                        $lastMonthRevenue
                    ),
                    'data' => [
                        'current' => $currentRevenue,
                        'previous' => $lastMonthRevenue,
                        'change' => $revenueChange,
                    ],
                    'recommended_action' => 'Lancez une campagne promotionnelle et contactez les prospects en attente',
                ];
            } elseif ($revenueChange < -5) {
                $alerts[] = [
                    'type' => 'revenue_decline',
                    'severity' => 'warning',
                    'title' => 'Revenus en baisse',
                    'message' => sprintf(
                        'Les revenus ont baisse de %.1f%% par rapport au mois dernier.',
                        abs($revenueChange)
                    ),
                    'data' => [
                        'current' => $currentRevenue,
                        'previous' => $lastMonthRevenue,
                        'change' => $revenueChange,
                    ],
                ];
            }
        }

        // Check 2: High vacancy rate opportunity cost
        $totalBoxes = Box::where('tenant_id', $this->tenantId)->count();
        $availableBoxes = Box::where('tenant_id', $this->tenantId)->where('status', 'available')->count();

        if ($totalBoxes > 0) {
            $occupancyRate = (($totalBoxes - $availableBoxes) / $totalBoxes) * 100;
            $avgPrice = Contract::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->avg('monthly_price') ?? 0;

            $lostRevenue = $availableBoxes * $avgPrice;

            if ($occupancyRate < 60 && $lostRevenue > 2000) {
                $alerts[] = [
                    'type' => 'vacancy_cost',
                    'severity' => 'high',
                    'title' => 'Perte de revenus importante',
                    'message' => sprintf(
                        '%d boxes vides representent %.0f EUR/mois de revenus manques. Taux d\'occupation: %.1f%%',
                        $availableBoxes,
                        $lostRevenue,
                        $occupancyRate
                    ),
                    'data' => [
                        'available_boxes' => $availableBoxes,
                        'lost_revenue' => $lostRevenue,
                        'occupancy_rate' => $occupancyRate,
                    ],
                    'recommended_action' => 'Proposez -30% sur le 1er mois pour attirer de nouveaux clients',
                ];
            }
        }

        // Check 3: Contracts ending soon without renewal
        $expiringContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays(14)])
            ->sum('monthly_price');

        if ($expiringContracts > 1000) {
            $count = Contract::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->whereBetween('end_date', [$now, $now->copy()->addDays(14)])
                ->count();

            $alerts[] = [
                'type' => 'expiring_revenue',
                'severity' => 'warning',
                'title' => 'Revenus a risque',
                'message' => sprintf(
                    '%d contrats expirent dans les 14 prochains jours (%.0f EUR/mois). Contactez ces clients pour renouvellement.',
                    $count,
                    $expiringContracts
                ),
                'data' => [
                    'count' => $count,
                    'revenue' => $expiringContracts,
                ],
                'recommended_action' => 'Envoyez des offres de renouvellement avec -10%',
            ];
        }

        // Check 4: Pricing opportunity (high occupancy)
        if (isset($occupancyRate) && $occupancyRate > 95) {
            $alerts[] = [
                'type' => 'pricing_opportunity',
                'severity' => 'info',
                'title' => 'Opportunite d\'augmentation des prix',
                'message' => sprintf(
                    'Occupation a %.1f%%! Vous pouvez augmenter les prix de 5-10%% pour les nouveaux contrats.',
                    $occupancyRate
                ),
                'data' => [
                    'occupancy_rate' => $occupancyRate,
                    'potential_increase' => $currentRevenue * 0.1,
                ],
                'recommended_action' => 'Augmentez les prix des nouveaux contrats de 10%',
            ];
        }

        // Create notifications for alerts
        foreach ($alerts as $alert) {
            $this->createNotification($alert);
        }

        Log::info("RevenueGuardianJob completed for tenant {$this->tenantId}", [
            'alerts_count' => count($alerts),
        ]);
    }

    protected function createNotification(array $alert): void
    {
        // Get admin users for this tenant
        $adminUsers = \App\Models\User::where('tenant_id', $this->tenantId)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($adminUsers as $user) {
            Notification::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'type' => 'ai_alert',
                'title' => '[AI] ' . $alert['title'],
                'message' => $alert['message'],
                'data' => [
                    'alert_type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'data' => $alert['data'] ?? [],
                    'recommended_action' => $alert['recommended_action'] ?? null,
                    'source' => 'RevenueGuardian',
                ],
                'read' => false,
            ]);
        }
    }
}
