<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Box;
use App\Models\Customer;
use App\Models\PlatformInvoice;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Module;
use App\Models\TenantModule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ImprovedDashboardController extends Controller
{
    public function index()
    {
        // Global statistics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'trial_tenants' => Tenant::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>=', now())
                ->count(),
            'suspended_tenants' => Tenant::where('is_active', false)->count(),

            'total_users' => User::count(),
            'total_boxes' => Box::count(),
            'total_customers' => Customer::count(),
            'total_contracts' => Contract::where('status', 'active')->count(),

            // Revenus tenants (via leurs paiements)
            'total_tenant_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'monthly_tenant_revenue' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),

            // Revenus plateforme (factures aux tenants)
            'platform_revenue_total' => PlatformInvoice::where('status', 'paid')->sum('total_amount'),
            'platform_revenue_monthly' => PlatformInvoice::where('status', 'paid')
                ->whereMonth('paid_date', now()->month)
                ->whereYear('paid_date', now()->year)
                ->sum('total_amount'),
            'platform_pending' => PlatformInvoice::where('status', 'pending')->sum('total_amount'),
            'platform_overdue' => PlatformInvoice::overdue()->sum('total_amount'),

            // Abonnements
            'active_subscriptions' => TenantSubscription::where('status', 'active')->count(),
            'trial_subscriptions' => TenantSubscription::where('status', 'trial')->count(),
            'past_due_subscriptions' => TenantSubscription::where('status', 'past_due')->count(),

            // Modules
            'total_modules' => Module::count(),
            'active_modules' => Module::where('is_active', true)->count(),
            'module_subscriptions' => TenantModule::whereIn('status', ['active', 'trial'])->count(),
        ];

        // Revenue trend (last 12 months) - Plateforme
        $platformRevenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $platformRevenueTrend[] = [
                'month' => $date->format('M Y'),
                'revenue' => PlatformInvoice::where('status', 'paid')
                    ->whereMonth('paid_date', $date->month)
                    ->whereYear('paid_date', $date->year)
                    ->sum('total_amount'),
            ];
        }

        // Tenants growth (last 12 months)
        $tenantsGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $tenantsGrowth[] = [
                'month' => $date->format('M Y'),
                'total' => Tenant::whereDate('created_at', '<=', $date->endOfMonth())->count(),
                'active' => Tenant::where('is_active', true)
                    ->whereDate('created_at', '<=', $date->endOfMonth())
                    ->count(),
            ];
        }

        // Top tenants by platform revenue
        $topTenantsByRevenue = Tenant::withCount(['contracts', 'customers', 'users'])
            ->get()
            ->map(function ($tenant) {
                $platformRevenue = PlatformInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'paid')
                    ->sum('total_amount');

                $pendingAmount = PlatformInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->sum('total_amount');

                $overdueAmount = PlatformInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->sum('total_amount');

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'plan' => $tenant->plan,
                    'is_active' => $tenant->is_active,
                    'contracts_count' => $tenant->contracts_count,
                    'customers_count' => $tenant->customers_count,
                    'users_count' => $tenant->users_count,
                    'platform_revenue' => $platformRevenue,
                    'pending_amount' => $pendingAmount,
                    'overdue_amount' => $overdueAmount,
                    'created_at' => $tenant->created_at->format('d/m/Y'),
                ];
            })
            ->sortByDesc('platform_revenue')
            ->take(10)
            ->values();

        // Tenants with overdue payments
        $overdueTenantsData = Tenant::all()
            ->map(function ($tenant) {
                $overdueAmount = PlatformInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->sum('total_amount');

                $overdueCount = PlatformInvoice::where('tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->count();

                if ($overdueAmount > 0) {
                    $oldestOverdue = PlatformInvoice::where('tenant_id', $tenant->id)
                        ->where('status', 'pending')
                        ->where('due_date', '<', now())
                        ->orderBy('due_date')
                        ->first();

                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'email' => $tenant->email,
                        'overdue_amount' => $overdueAmount,
                        'overdue_count' => $overdueCount,
                        'oldest_due_date' => $oldestOverdue?->due_date,
                        'days_overdue' => $oldestOverdue ? now()->diffInDays($oldestOverdue->due_date) : 0,
                    ];
                }
                return null;
            })
            ->filter()
            ->sortByDesc('overdue_amount')
            ->values();

        // Répartition par plan
        $planDistribution = [];
        $plans = SubscriptionPlan::all();
        foreach ($plans as $plan) {
            $count = Tenant::where('current_plan_id', $plan->id)->count();
            if ($count > 0) {
                $planDistribution[] = [
                    'name' => $plan->name,
                    'count' => $count,
                    'color' => $plan->badge_color,
                ];
            }
        }

        // Modules les plus utilisés
        $topModules = Module::all()
            ->map(function ($module) {
                $activeCount = TenantModule::where('module_id', $module->id)
                    ->whereIn('status', ['active', 'trial'])
                    ->count();
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'code' => $module->code,
                    'active_count' => $activeCount,
                    'category' => $module->category,
                ];
            })
            ->sortByDesc('active_count')
            ->take(10)
            ->values();

        // Recent activity
        $recentTenants = Tenant::latest()->take(5)->get(['id', 'name', 'slug', 'is_active', 'created_at'])
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->is_active ? 'active' : 'inactive',
                    'created_at' => $tenant->created_at,
                ];
            });

        $recentUsers = User::with('tenant:id,name')->latest()->take(5)->get(['id', 'name', 'email', 'tenant_id', 'created_at']);

        $recentSubscriptions = TenantSubscription::with(['tenant:id,name', 'plan:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Alertes système
        $systemAlerts = [];

        // Tenants en retard de paiement
        if ($overdueTenantsData->count() > 0) {
            $systemAlerts[] = [
                'type' => 'warning',
                'title' => 'Paiements en retard',
                'message' => "{$overdueTenantsData->count()} tenant(s) ont des factures en retard",
                'count' => $overdueTenantsData->count(),
                'link' => '/superadmin/billing?status=overdue',
            ];
        }

        // Abonnements expirant bientôt
        $expiringSubscriptions = TenantSubscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(30)])
            ->count();
        if ($expiringSubscriptions > 0) {
            $systemAlerts[] = [
                'type' => 'info',
                'title' => 'Abonnements expirant',
                'message' => "{$expiringSubscriptions} abonnement(s) expirent dans les 30 jours",
                'count' => $expiringSubscriptions,
            ];
        }

        // Essais se terminant
        $endingTrials = TenantSubscription::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();
        if ($endingTrials > 0) {
            $systemAlerts[] = [
                'type' => 'info',
                'title' => 'Essais se terminant',
                'message' => "{$endingTrials} période(s) d'essai se terminent dans les 7 jours",
                'count' => $endingTrials,
            ];
        }

        // System health
        $systemHealth = [
            'database' => true,
            'storage' => disk_free_space('/') > 1000000000, // > 1GB
            'queue' => true,
            'cache' => true,
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
            'platformRevenueTrend' => $platformRevenueTrend,
            'tenantsGrowth' => $tenantsGrowth,
            'topTenantsByRevenue' => $topTenantsByRevenue,
            'overdueTenantsData' => $overdueTenantsData,
            'planDistribution' => $planDistribution,
            'topModules' => $topModules,
            'recentTenants' => $recentTenants,
            'recentUsers' => $recentUsers,
            'recentSubscriptions' => $recentSubscriptions,
            'systemAlerts' => $systemAlerts,
            'systemHealth' => $systemHealth,
        ]);
    }
}
