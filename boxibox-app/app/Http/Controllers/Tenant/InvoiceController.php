<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\ExcelExportService;
use App\Services\InvoiceGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request): Response
    {
        $this->authorize('view_invoices');

        $tenantId = $request->user()->tenant_id;

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with(['customer', 'contract'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->customer_id, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Invoice::where('tenant_id', $tenantId)->count(),
            'paid' => Invoice::where('tenant_id', $tenantId)->where('status', 'paid')->count(),
            'overdue' => Invoice::where('tenant_id', $tenantId)->where('status', 'overdue')->count(),
            'draft' => Invoice::where('tenant_id', $tenantId)->where('status', 'draft')->count(),
            'total_outstanding' => Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['sent', 'overdue', 'partial'])
                ->sum('total'),
        ];

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Tenant/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'type', 'customer_id']),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create_invoices');

        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box'])
            ->where('status', 'active')
            ->select('id', 'contract_number', 'customer_id', 'box_id', 'monthly_price')
            ->orderBy('contract_number')
            ->get();

        return Inertia::render('Tenant/Invoices/Create', [
            'customers' => $customers,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Auto-generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $prefix = match($data['type']) {
                'credit_note' => 'CN',
                'proforma' => 'PRO',
                default => 'INV',
            };
            $data['invoice_number'] = $prefix . '-' . strtoupper(substr(uniqid(), -8));
        }

        // Initialize paid_amount if not set
        if (!isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }

        // Initialize reminder_count
        $data['reminder_count'] = 0;

        $invoice = Invoice::create($data);

        // Update customer total_revenue if invoice is paid
        if ($invoice->status === 'paid' && $invoice->customer) {
            $invoice->customer->increment('total_revenue', $invoice->total);
        }

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $this->authorize('view_invoices');

        // Ensure tenant can only view their own invoices
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $invoice->load(['tenant', 'customer', 'contract', 'payments']);

        return Inertia::render('Tenant/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->authorize('update_invoices');

        // Ensure tenant can only edit their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box'])
            ->where(function ($query) use ($invoice) {
                $query->where('status', 'active')
                    ->orWhere('id', $invoice->contract_id);
            })
            ->select('id', 'contract_number', 'customer_id', 'box_id', 'monthly_price')
            ->orderBy('contract_number')
            ->get();

        return Inertia::render('Tenant/Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        // Ensure tenant can only update their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $data = $request->validated();

        // Store old status and total for revenue tracking
        $oldStatus = $invoice->status;
        $oldTotal = $invoice->total;

        // Update invoice
        $invoice->update($data);

        // Update customer revenue if status changed to/from paid
        if ($invoice->customer) {
            if ($oldStatus !== 'paid' && $data['status'] === 'paid') {
                // Invoice was just paid
                $invoice->customer->increment('total_revenue', $invoice->total);
            } elseif ($oldStatus === 'paid' && $data['status'] !== 'paid') {
                // Invoice was unpaid
                $invoice->customer->decrement('total_revenue', $oldTotal);
            } elseif ($oldStatus === 'paid' && $data['status'] === 'paid' && $oldTotal !== $invoice->total) {
                // Total amount changed while paid
                $invoice->customer->decrement('total_revenue', $oldTotal);
                $invoice->customer->increment('total_revenue', $invoice->total);
            }
        }

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete_invoices');

        // Ensure tenant can only delete their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Update customer revenue if invoice was paid
        if ($invoice->status === 'paid' && $invoice->customer) {
            $invoice->customer->decrement('total_revenue', $invoice->total);
        }

        $invoice->delete();

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Download the invoice as PDF.
     */
    public function downloadPdf(Request $request, Invoice $invoice)
    {
        // Ensure tenant can only download their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Load relationships
        $invoice->load(['customer', 'contract.box', 'contract.site']);

        // Get tenant information
        $tenant = $request->user()->tenant;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        // Return PDF for download
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Export invoices to Excel (CSV).
     */
    public function export(Request $request, ExcelExportService $exportService)
    {
        $this->authorize('view_invoices');

        $tenantId = $request->user()->tenant_id;
        $status = $request->query('status');

        $result = $exportService->exportInvoices($tenantId, $status);
        $csv = $exportService->generateCSV($result['data']);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    /**
     * Generate invoices for active contracts.
     */
    public function generateInvoices(Request $request, InvoiceGenerationService $service): RedirectResponse
    {
        $this->authorize('create_invoices');

        $tenantId = $request->user()->tenant_id;
        $result = $service->generateInvoicesForContracts($tenantId);

        $message = "Generated {$result['total']} invoice(s).";
        if (count($result['failed']) > 0) {
            $message .= " Failed: " . count($result['failed']);
        }

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', $message);
    }

    /**
     * Record payment for an invoice.
     */
    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update_invoices');

        // Ensure tenant can only update their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Validate request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:bank_transfer,card,cash,sepa',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
        ]);

        $paymentDate = $validated['payment_date'] ?? now()->toDateString();

        // Record payment
        $invoice->recordPayment($validated['amount']);

        // Create payment record
        Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => $paymentDate,
            'reference' => $validated['reference'] ?? 'Manual payment',
        ]);

        // Update customer revenue if invoice is now paid
        if ($invoice->status === 'paid' && $invoice->customer) {
            $invoice->customer->increment('total_revenue', $invoice->total);
        }

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Send invoice to customer (mark as sent).
     */
    public function sendInvoice(Request $request, Invoice $invoice, InvoiceGenerationService $service): RedirectResponse
    {
        $this->authorize('update_invoices');

        // Ensure tenant can only update their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only draft invoices can be sent
        if ($invoice->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Only draft invoices can be sent.');
        }

        $service->sendInvoice($invoice);

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', 'Invoice sent to customer.');
    }

    /**
     * Send payment reminder for an invoice.
     */
    public function sendReminder(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update_invoices');

        // Ensure tenant can only update their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only unpaid invoices can receive reminders
        if (!in_array($invoice->status, ['sent', 'overdue', 'partial'])) {
            return redirect()
                ->back()
                ->with('error', 'Only unpaid invoices can receive reminders.');
        }

        $invoice->sendReminder();

        // TODO: Send email reminder

        return redirect()
            ->route('tenant.invoices.show', $invoice->id)
            ->with('success', 'Payment reminder sent.');
    }

    /**
     * Get overdue invoices summary.
     */
    public function overdueInvoices(Request $request): Response
    {
        $this->authorize('view_invoices');

        $tenantId = $request->user()->tenant_id;

        $invoices = Invoice::overdue()
            ->where('tenant_id', $tenantId)
            ->with(['customer', 'contract'])
            ->orderBy('due_date')
            ->paginate(10);

        $totalAmount = Invoice::overdue()
            ->where('tenant_id', $tenantId)
            ->sum('total');

        return Inertia::render('Tenant/Invoices/Overdue', [
            'invoices' => $invoices,
            'totalAmount' => $totalAmount,
        ]);
    }
}
