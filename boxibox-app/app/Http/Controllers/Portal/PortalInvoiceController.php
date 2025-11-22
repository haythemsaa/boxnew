<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class PortalInvoiceController extends Controller
{
    /**
     * Display a listing of the customer's invoices.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        $invoices = $customer->invoices()
            ->with(['contract.box'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate(10);

        // Calculate totals
        $totals = [
            'outstanding' => $customer->invoices()
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('balance'),
            'paid' => $customer->invoices()
                ->where('status', 'paid')
                ->sum('total'),
        ];

        return Inertia::render('Portal/Invoices/Index', [
            'invoices' => $invoices,
            'totals' => $totals,
            'filters' => [
                'status' => $request->input('status'),
            ],
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, Invoice $invoice): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        // Ensure customer can only view their own invoices
        if (!$customer || $invoice->customer_id !== $customer->id) {
            abort(403, 'Unauthorized to view this invoice.');
        }

        $invoice->load(['contract.box', 'payments']);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Download the invoice as PDF.
     */
    public function downloadPdf(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        $customer = $user->customer;

        // Ensure customer can only download their own invoices
        if (!$customer || $invoice->customer_id !== $customer->id) {
            abort(403, 'Unauthorized to download this invoice.');
        }

        $invoice->load(['customer', 'contract.box', 'contract.site']);
        $tenant = $invoice->tenant;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
