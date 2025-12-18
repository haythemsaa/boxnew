<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPortalController extends Controller
{
    /**
     * Show customer dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $customer = $request->user();

        // Get customer's contracts
        $contracts = Contract::where('customer_id', $customer->id)
            ->with(['box', 'site'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get customer's invoices
        $invoices = Invoice::where('customer_id', $customer->id)
            ->with(['contract'])
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        // Calculate statistics
        $stats = [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'total_invoices' => Invoice::where('customer_id', $customer->id)->count(),
            'outstanding_balance' => Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['sent', 'overdue', 'partial'])
                ->sum('total'),
            'paid_invoices' => Invoice::where('customer_id', $customer->id)
                ->where('status', 'paid')
                ->count(),
            'overdue_invoices' => Invoice::where('customer_id', $customer->id)
                ->overdue()
                ->count(),
        ];

        return Inertia::render('Portal/Dashboard', [
            'customer' => $customer,
            'contracts' => $contracts,
            'invoices' => $invoices,
            'stats' => $stats,
        ]);
    }

    /**
     * Show customer's contracts.
     */
    public function contracts(Request $request): Response
    {
        $customer = $request->user();

        $contracts = Contract::where('customer_id', $customer->id)
            ->with(['box', 'site'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Portal/Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Show contract details.
     */
    public function contractDetail(Request $request, Contract $contract): Response
    {
        // Check if customer owns this contract
        if ($contract->customer_id !== $request->user()->id) {
            abort(403);
        }

        $contract->load(['box', 'site', 'invoices']);

        return Inertia::render('Portal/Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    /**
     * Show customer's invoices.
     */
    public function invoices(Request $request): Response
    {
        $customer = $request->user();

        $invoices = Invoice::where('customer_id', $customer->id)
            ->with(['contract'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('invoice_number', 'like', "%{$search}%");
            })
            ->latest('invoice_date')
            ->paginate(15);

        $stats = [
            'total' => Invoice::where('customer_id', $customer->id)->count(),
            'paid' => Invoice::where('customer_id', $customer->id)->where('status', 'paid')->count(),
            'overdue' => Invoice::where('customer_id', $customer->id)->overdue()->count(),
            'outstanding' => Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['sent', 'overdue', 'partial'])
                ->sum('total'),
        ];

        return Inertia::render('Portal/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show invoice details.
     */
    public function invoiceDetail(Request $request, Invoice $invoice): Response
    {
        // Check if customer owns this invoice
        if ($invoice->customer_id !== $request->user()->id) {
            abort(403);
        }

        $invoice->load(['contract', 'payments']);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoicePdf(Request $request, Invoice $invoice)
    {
        // Check if customer owns this invoice
        if ($invoice->customer_id !== $request->user()->id) {
            abort(403);
        }

        $invoice->load(['customer', 'contract.box', 'contract.site']);

        // Get tenant information
        $tenant = $invoice->contract->site->tenant;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Show payment history.
     */
    public function paymentHistory(Request $request): Response
    {
        $customer = $request->user();

        $payments = Payment::whereHas('invoice', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
            ->with(['invoice'])
            ->latest('paid_at')
            ->paginate(20);

        $stats = [
            'total_paid' => Payment::whereHas('invoice', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })->sum('amount'),
            'payments_count' => Payment::whereHas('invoice', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })->count(),
        ];

        return Inertia::render('Portal/Payments/History', [
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    /**
     * Show customer profile/account settings.
     */
    public function profile(Request $request): Response
    {
        $customer = $request->user();

        return Inertia::render('Portal/Profile', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $validated = $request->validate([
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('portal.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
