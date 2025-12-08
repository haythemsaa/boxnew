<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Global KPIs
        $kpis = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateMRR() * 12,
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'churn_rate' => $this->calculateChurnRate(),
            'ltv' => $this->calculateLTV(),
            'total_boxes' => Box::count(),
            'occupied_boxes' => Box::where('status', 'occupied')->count(),
            'occupancy_rate' => Box::count() > 0 ? round((Box::where('status', 'occupied')->count() / Box::count()) * 100, 1) : 0,
        ];

        // Revenue by month (last 12 months)
        $revenueByMonth = $this->getRevenueByMonth();

        // Tenants by plan
        $tenantsByPlan = [
            ['plan' => 'Free', 'count' => Tenant::where('plan', 'free')->count()],
            ['plan' => 'Starter', 'count' => Tenant::where('plan', 'starter')->count()],
            ['plan' => 'Professional', 'count' => Tenant::where('plan', 'professional')->count()],
            ['plan' => 'Enterprise', 'count' => Tenant::where('plan', 'enterprise')->count()],
        ];

        // New tenants per month
        $newTenantsPerMonth = $this->getNewTenantsPerMonth();

        // Top performing tenants
        $topTenants = Tenant::withCount(['contracts', 'customers'])
            ->with(['invoices' => function ($q) {
                $q->where('status', 'paid');
            }])
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'plan' => $tenant->plan,
                    'revenue' => $tenant->invoices->sum('total'),
                    'contracts' => $tenant->contracts_count,
                    'customers' => $tenant->customers_count,
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        // Geographic distribution
        $geoDistribution = Tenant::selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country ?? 'Non spécifié',
                    'count' => $item->count,
                ];
            });

        return Inertia::render('SuperAdmin/Analytics/Index', [
            'kpis' => $kpis,
            'revenueByMonth' => $revenueByMonth,
            'tenantsByPlan' => $tenantsByPlan,
            'newTenantsPerMonth' => $newTenantsPerMonth,
            'topTenants' => $topTenants,
            'geoDistribution' => $geoDistribution,
        ]);
    }

    private function getRevenueByMonth(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $data[] = [
                'month' => $date->format('M Y'),
                'revenue' => Payment::where('status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount'),
            ];
        }
        return $data;
    }

    private function getNewTenantsPerMonth(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $data[] = [
                'month' => $date->format('M Y'),
                'count' => Tenant::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }
        return $data;
    }

    private function calculateMRR(): float
    {
        $pricing = [
            'free' => 0,
            'starter' => 49,
            'professional' => 99,
            'enterprise' => 249,
        ];

        $mrr = 0;
        foreach ($pricing as $plan => $price) {
            $count = Tenant::where('plan', $plan)
                ->where('is_active', true)
                ->count();
            $mrr += $count * $price;
        }

        return $mrr;
    }

    private function calculateChurnRate(): float
    {
        $totalTenants = Tenant::count();
        if ($totalTenants === 0) return 0;

        $churned = Tenant::where('is_active', false)
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->count();

        return round(($churned / $totalTenants) * 100, 2);
    }

    private function calculateLTV(): float
    {
        $avgRevenuePerTenant = Payment::where('status', 'completed')->avg('amount') ?? 0;
        $avgLifetimeMonths = 24; // Assumption
        return round($avgRevenuePerTenant * $avgLifetimeMonths, 2);
    }
}
