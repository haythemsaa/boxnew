<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Site;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        // Get statistics
        $stats = [
            'total_sites' => Site::where('tenant_id', $tenantId)->count(),
            'total_boxes' => Box::where('tenant_id', $tenantId)->count(),
            'available_boxes' => Box::where('tenant_id', $tenantId)->available()->count(),
            'occupied_boxes' => Box::where('tenant_id', $tenantId)->occupied()->count(),
            'total_customers' => Customer::where('tenant_id', $tenantId)->count(),
            'active_contracts' => Contract::where('tenant_id', $tenantId)->active()->count(),
            'monthly_revenue' => Contract::where('tenant_id', $tenantId)
                ->active()
                ->sum('monthly_price'),
            'pending_invoices' => Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['sent', 'overdue'])
                ->count(),
            'overdue_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'overdue')
                ->count(),
        ];

        // Calculate occupation rate
        $stats['occupation_rate'] = $stats['total_boxes'] > 0
            ? round(($stats['occupied_boxes'] / $stats['total_boxes']) * 100, 2)
            : 0;

        // Get recent contracts
        $recentContracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $contract->customer->full_name,
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
                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $contract->customer->full_name,
                    'box_name' => $contract->box->name,
                    'end_date' => $contract->end_date->format('Y-m-d'),
                    'days_until_expiry' => $contract->days_until_expiry,
                ];
            });

        // Get overdue invoices
        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->overdue()
            ->with('customer')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->full_name,
                    'total' => $invoice->total,
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                ];
            });

        return Inertia::render('Tenant/Dashboard', [
            'stats' => $stats,
            'recentContracts' => $recentContracts,
            'expiringContracts' => $expiringContracts,
            'overdueInvoices' => $overdueInvoices,
        ]);
    }
}
