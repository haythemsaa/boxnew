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
use App\Services\DynamicPricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        // Get comprehensive statistics
        $stats = [
            // Sites
            'total_sites' => Site::where('tenant_id', $tenantId)->count(),
            'active_sites' => Site::where('tenant_id', $tenantId)->where('is_active', true)->count(),

            // Boxes
            'total_boxes' => Box::where('tenant_id', $tenantId)->count(),
            'available_boxes' => Box::where('tenant_id', $tenantId)->available()->count(),
            'occupied_boxes' => Box::where('tenant_id', $tenantId)->occupied()->count(),
            'maintenance_boxes' => Box::where('tenant_id', $tenantId)->where('status', 'maintenance')->count(),

            // Customers
            'total_customers' => Customer::where('tenant_id', $tenantId)->count(),
            'active_customers' => Customer::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'individual_customers' => Customer::where('tenant_id', $tenantId)->where('type', 'individual')->count(),
            'company_customers' => Customer::where('tenant_id', $tenantId)->where('type', 'company')->count(),

            // Contracts
            'active_contracts' => Contract::where('tenant_id', $tenantId)->active()->count(),
            'expiring_soon' => Contract::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->count(),

            // Financial
            'monthly_revenue' => Contract::where('tenant_id', $tenantId)
                ->active()
                ->sum('monthly_price'),
            'annual_revenue_projection' => Contract::where('tenant_id', $tenantId)
                ->active()
                ->sum('monthly_price') * 12,

            // Invoices
            'total_invoices' => Invoice::where('tenant_id', $tenantId)->count(),
            'pending_invoices' => Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['sent', 'overdue'])
                ->count(),
            'overdue_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'overdue')
                ->count(),
            'paid_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->count(),

            // Payments
            'total_payments' => Payment::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->where('type', 'payment')
                ->count(),
            'total_collected' => Payment::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->where('type', 'payment')
                ->sum('amount'),
            'pending_payments' => Payment::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->count(),
        ];

        // Calculate occupation rate
        $stats['occupation_rate'] = $stats['total_boxes'] > 0
            ? round(($stats['occupied_boxes'] / $stats['total_boxes']) * 100, 2)
            : 0;

        // Additional stats for new Dashboard
        $stats['reserved_boxes'] = Box::where('tenant_id', $tenantId)->where('status', 'reserved')->count();

        // Overdue amount
        $stats['overdue_amount'] = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->sum('total');

        // Last month revenue for comparison
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $stats['last_month_revenue'] = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('type', 'payment')
            ->whereBetween('paid_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        // New customers this month
        $monthStart = now()->startOfMonth();
        $stats['new_customers_this_month'] = Customer::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Pending actions count (expiring contracts + overdue invoices + pending signatures)
        $stats['pending_actions'] = $stats['expiring_soon'] + $stats['overdue_invoices'];

        // Advanced KPIs
        // RevPAU - Revenue Per Available Unit
        $stats['revpau'] = $stats['total_boxes'] > 0
            ? round($stats['monthly_revenue'] / $stats['total_boxes'], 2)
            : 0;

        // Average contract value
        $stats['average_contract_value'] = $stats['active_contracts'] > 0
            ? round($stats['monthly_revenue'] / $stats['active_contracts'], 2)
            : 0;

        // Churn rate (contracts terminated this month / total active contracts)
        $terminatedThisMonth = Contract::where('tenant_id', $tenantId)
            ->where('status', 'terminated')
            ->where('actual_end_date', '>=', now()->startOfMonth())
            ->count();
        $stats['churn_rate'] = $stats['active_contracts'] > 0
            ? round(($terminatedThisMonth / ($stats['active_contracts'] + $terminatedThisMonth)) * 100, 2)
            : 0;

        // Customer Acquisition Cost (simplified - based on marketing spend if available)
        $stats['cac'] = 0; // Placeholder - would need marketing data

        // Average duration of contracts (in months)
        $avgDuration = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereNotNull('start_date')
            ->get()
            ->avg(fn($c) => $c->start_date->diffInMonths(now()));
        $stats['avg_contract_duration'] = round($avgDuration ?? 0, 1);

        // Prospects/Leads stats
        $stats['total_prospects'] = Prospect::where('tenant_id', $tenantId)->count();
        $stats['hot_prospects'] = Prospect::where('tenant_id', $tenantId)
            ->where('status', 'hot')
            ->count();
        $stats['prospects_this_month'] = Prospect::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Conversion rate (prospects converted to customers this month)
        $convertedThisMonth = Prospect::where('tenant_id', $tenantId)
            ->where('status', 'converted')
            ->where('converted_at', '>=', now()->startOfMonth())
            ->count();
        $totalProspectsMonthBefore = Prospect::where('tenant_id', $tenantId)
            ->where('created_at', '<', now()->startOfMonth())
            ->count();
        $stats['conversion_rate'] = $totalProspectsMonthBefore > 0
            ? round(($convertedThisMonth / $totalProspectsMonthBefore) * 100, 1)
            : 0;

        // Signatures pending
        $stats['pending_signatures'] = Signature::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'sent', 'viewed'])
            ->count();

        // Get monthly revenue trend (last 6 months)
        $revenueTrend = $this->getMonthlyRevenueTrend($tenantId, 6);

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

        return Inertia::render('Tenant/Dashboard', [
            'stats' => $stats,
            'monthlySummary' => $monthlySummary,
            'revenueTrend' => $revenueTrend,
            'recentContracts' => $recentContracts,
            'expiringContracts' => $expiringContracts,
            'overdueInvoices' => $overdueInvoices,
            'recentPayments' => $recentPayments,
        ]);
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
     */
    private function getMonthlySummary(int $tenantId): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Revenue this month (completed payments)
        $revenue = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('type', 'payment')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $revenueCount = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('type', 'payment')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->count();

        // Invoices this month
        $invoicesCount = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $invoicesTotal = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('total');

        // Payments this month
        $paymentsCount = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->count();

        $paymentsTotal = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        // New contracts this month
        $newContracts = Contract::where('tenant_id', $tenantId)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->count();

        $newContractsValue = Contract::where('tenant_id', $tenantId)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->sum('monthly_price');

        // New customers this month
        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        return [
            'revenue' => $revenue,
            'revenue_count' => $revenueCount,
            'invoices_count' => $invoicesCount,
            'invoices_total' => $invoicesTotal,
            'payments_count' => $paymentsCount,
            'payments_total' => $paymentsTotal,
            'new_contracts' => $newContracts,
            'new_contracts_value' => $newContractsValue,
            'new_customers' => $newCustomers,
        ];
    }
}
