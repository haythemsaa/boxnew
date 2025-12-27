<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Site;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Signature;
use App\Services\DashboardCacheService;
use App\Services\DynamicPricingService;
use App\Services\SelfStorageKPIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardCacheService $cacheService,
        protected SelfStorageKPIService $kpiService
    ) {}

    /**
     * Display the tenant dashboard.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        // Get cached stats (auto-invalidated when models change)
        $stats = $this->cacheService->getStats($tenantId);

        // Get cached revenue trend
        $revenueTrend = $this->cacheService->getRevenueTrend($tenantId, 6);

        // Get monthly summary (current month)
        $monthlySummary = $this->getMonthlySummary($tenantId);

        // Get recent contracts
        $recentContracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($contract) {
                $customerName = $contract->customer->type === 'company'
                    ? $contract->customer->company_name
                    : $contract->customer->first_name . ' ' . $contract->customer->last_name;

                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $customerName,
                    'box_name' => $contract->box->name,
                    'monthly_price' => $contract->monthly_price,
                    'status' => $contract->status,
                    'start_date' => $contract->start_date->format('Y-m-d'),
                ];
            });

        // Get expiring contracts (next 30 days)
        $expiringContracts = Contract::where('tenant_id', $tenantId)
            ->expiringSoon(30)
            ->with(['customer', 'box'])
            ->get()
            ->map(function ($contract) {
                $customerName = $contract->customer->type === 'company'
                    ? $contract->customer->company_name
                    : $contract->customer->first_name . ' ' . $contract->customer->last_name;

                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $customerName,
                    'box_name' => $contract->box->name,
                    'end_date' => $contract->end_date->format('Y-m-d'),
                    'days_until_expiry' => $contract->days_until_expiry,
                ];
            });

        // Get overdue invoices with days overdue
        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->overdue()
            ->with('customer')
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                $customerName = $invoice->customer->type === 'company'
                    ? $invoice->customer->company_name
                    : $invoice->customer->first_name . ' ' . $invoice->customer->last_name;

                $daysOverdue = now()->diffInDays($invoice->due_date, false);

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $customerName,
                    'total' => $invoice->total,
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'days_overdue' => abs($daysOverdue),
                ];
            });

        // Get recent payments
        $recentPayments = Payment::where('tenant_id', $tenantId)
            ->with(['customer', 'invoice'])
            ->where('status', 'completed')
            ->latest('paid_at')
            ->take(5)
            ->get()
            ->map(function ($payment) {
                $customerName = $payment->customer->type === 'company'
                    ? $payment->customer->company_name
                    : $payment->customer->first_name . ' ' . $payment->customer->last_name;

                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'customer_name' => $customerName,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'method' => $payment->method,
                    'paid_at' => $payment->paid_at->format('Y-m-d'),
                ];
            });

        // Get key KPIs for dashboard header
        $keyKpis = $this->getKeyKpis($tenantId);

        return Inertia::render('Tenant/Dashboard', [
            'stats' => $stats,
            'monthlySummary' => $monthlySummary,
            'revenueTrend' => $revenueTrend,
            'recentContracts' => $recentContracts,
            'expiringContracts' => $expiringContracts,
            'overdueInvoices' => $overdueInvoices,
            'recentPayments' => $recentPayments,
            'keyKpis' => $keyKpis,
        ]);
    }

    /**
     * Display the advanced KPI dashboard.
     */
    public function kpiDashboard(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        // Get all KPIs
        $kpis = $this->kpiService->getAllKPIs($tenantId, $siteId);

        // Get sites for filter
        $sites = Site::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        return Inertia::render('Tenant/Analytics/KPIDashboard', [
            'kpis' => $kpis,
            'sites' => $sites,
            'selectedSiteId' => $siteId,
        ]);
    }

    /**
     * Get KPIs as JSON (for AJAX refresh).
     */
    public function getKpis(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $siteId = $request->input('site_id');

        $kpis = $this->kpiService->getAllKPIs($tenantId, $siteId);

        return response()->json($kpis);
    }

    /**
     * Get key KPIs for dashboard header display.
     */
    private function getKeyKpis(int $tenantId): array
    {
        $occupancy = $this->kpiService->getOccupancyMetrics($tenantId);
        $revenue = $this->kpiService->getRevenueMetrics($tenantId);
        $operations = $this->kpiService->getOperationsMetrics($tenantId);
        $financial = $this->kpiService->getFinancialMetrics($tenantId);

        return [
            'physical_occupancy' => $occupancy['physical_occupancy'] ?? 0,
            'economic_occupancy' => $occupancy['economic_occupancy'] ?? 0,
            'mrr' => $revenue['mrr'] ?? 0,
            'noi' => $revenue['noi'] ?? 0,
            'noi_margin' => $financial['noi_margin'] ?? 0,
            'revpasf' => $revenue['revpasf'] ?? 0,
            'churn_rate' => $operations['churn_rate'] ?? 0,
            'avg_length_of_stay' => $operations['avg_length_of_stay'] ?? 0,
            'delinquency_rate' => $financial['delinquency_rate'] ?? 0,
            'collection_rate' => $financial['collection_rate'] ?? 0,
        ];
    }

    /**
     * Get monthly revenue trend for the last N months.
     */
    private function getMonthlyRevenueTrend(int $tenantId, int $months = 6): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $revenue = Payment::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->where('type', 'payment')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $trend[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue,
            ];
        }

        return $trend;
    }

    /**
     * Get monthly summary for current month.
     * Optimized: Uses single queries with selectRaw instead of multiple queries
     */
    private function getMonthlySummary(int $tenantId): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Revenue and payments this month (single optimized query)
        $paymentStats = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->selectRaw("
                COUNT(*) as payments_count,
                COALESCE(SUM(amount), 0) as payments_total,
                SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as revenue_count,
                COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as revenue
            ")
            ->first();

        // Invoices this month (single optimized query)
        $invoiceStats = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->selectRaw("
                COUNT(*) as invoices_count,
                COALESCE(SUM(total), 0) as invoices_total
            ")
            ->first();

        // New contracts this month (single optimized query)
        $contractStats = Contract::where('tenant_id', $tenantId)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->selectRaw("
                COUNT(*) as new_contracts,
                COALESCE(SUM(monthly_price), 0) as new_contracts_value
            ")
            ->first();

        // New customers this month
        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        return [
            'revenue' => $paymentStats->revenue ?? 0,
            'revenue_count' => $paymentStats->revenue_count ?? 0,
            'invoices_count' => $invoiceStats->invoices_count ?? 0,
            'invoices_total' => $invoiceStats->invoices_total ?? 0,
            'payments_count' => $paymentStats->payments_count ?? 0,
            'payments_total' => $paymentStats->payments_total ?? 0,
            'new_contracts' => $contractStats->new_contracts ?? 0,
            'new_contracts_value' => $contractStats->new_contracts_value ?? 0,
            'new_customers' => $newCustomers,
        ];
    }
}
