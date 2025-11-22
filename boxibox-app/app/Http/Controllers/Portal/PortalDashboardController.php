<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController extends Controller
{
    /**
     * Display the customer portal dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get customer record
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        // Get active contracts
        $activeContracts = $customer->contracts()
            ->where('status', 'active')
            ->with(['box.site'])
            ->get();

        // Get recent invoices
        $recentInvoices = $customer->invoices()
            ->orderBy('invoice_date', 'desc')
            ->limit(5)
            ->get();

        // Get recent payments
        $recentPayments = $customer->payments()
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate statistics
        $stats = [
            'active_contracts' => $activeContracts->count(),
            'total_invoices' => $customer->invoices()->count(),
            'pending_invoices' => $customer->invoices()
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->count(),
            'total_paid' => $customer->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'outstanding_balance' => $customer->invoices()
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('balance'),
        ];

        return Inertia::render('Portal/Dashboard', [
            'customer' => $customer,
            'activeContracts' => $activeContracts,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'stats' => $stats,
        ]);
    }
}
