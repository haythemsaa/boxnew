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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Global statistics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'total_users' => User::count(),
            'total_boxes' => Box::count(),
            'total_customers' => Customer::count(),
            'total_contracts' => Contract::where('status', 'active')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        // Revenue trend (last 12 months)
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenueTrend[] = [
                'month' => $date->format('M Y'),
                'revenue' => Payment::where('status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount'),
            ];
        }

        // Tenants growth (last 12 months)
        $tenantsGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $tenantsGrowth[] = [
                'month' => $date->format('M Y'),
                'count' => Tenant::whereDate('created_at', '<=', $date->endOfMonth())->count(),
            ];
        }

        // Top tenants by revenue
        $topTenants = Tenant::withCount(['contracts', 'customers'])
            ->with(['invoices' => function ($q) {
                $q->where('status', 'paid');
            }])
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->is_active ? 'active' : 'inactive',
                    'contracts_count' => $tenant->contracts_count,
                    'customers_count' => $tenant->customers_count,
                    'revenue' => $tenant->invoices->sum('total'),
                    'created_at' => $tenant->created_at->format('d/m/Y'),
                ];
            })
            ->sortByDesc('revenue')
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

        // System health
        $systemHealth = [
            'database' => true,
            'storage' => disk_free_space('/') > 1000000000, // > 1GB
            'queue' => true, // Would check queue status
            'cache' => true,
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
            'revenueTrend' => $revenueTrend,
            'tenantsGrowth' => $tenantsGrowth,
            'topTenants' => $topTenants,
            'recentTenants' => $recentTenants,
            'recentUsers' => $recentUsers,
            'systemHealth' => $systemHealth,
        ]);
    }
}
