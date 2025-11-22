<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with(['customer', 'contract'])
            ->when($request->input('customer_id'), function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('invoice_number', 'like', "%{$search}%");
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get invoices by customer.
     */
    public function byCustomer(Request $request, Customer $customer): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $invoices = $customer->invoices()
            ->with(['contract'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get invoices by contract.
     */
    public function byContract(Request $request, Contract $contract): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $invoices = $contract->invoices()
            ->with(['customer'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'status' => ['required', 'in:draft,sent,paid,overdue,cancelled'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string'],
        ]);

        // Calculate amounts
        $subtotal = 0;
        foreach ($validated['items'] as &$item) {
            $item['total'] = $item['quantity'] * $item['unit_price'];
            $subtotal += $item['total'];
        }

        $discountAmount = $validated['discount_amount'] ?? 0;
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = ($taxableAmount * $validated['tax_rate']) / 100;
        $total = $taxableAmount + $taxAmount;

        $invoice = Invoice::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'paid_amount' => 0,
            'balance' => $total,
        ]);

        return (new InvoiceResource($invoice->load(['customer', 'contract'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        // Ensure tenant can only view their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $invoice->load(['customer', 'contract', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice): InvoiceResource
    {
        // Ensure tenant can only update their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'exists:customers,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:draft,sent,paid,overdue,cancelled'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // Recalculate amounts if items changed
        if (isset($validated['items'])) {
            $subtotal = 0;
            foreach ($validated['items'] as &$item) {
                $item['total'] = $item['quantity'] * $item['unit_price'];
                $subtotal += $item['total'];
            }

            $taxRate = $validated['tax_rate'] ?? $invoice->tax_rate;
            $discountAmount = $validated['discount_amount'] ?? $invoice->discount_amount ?? 0;
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = ($taxableAmount * $taxRate) / 100;
            $total = $taxableAmount + $taxAmount;

            $validated['subtotal'] = $subtotal;
            $validated['tax_amount'] = $taxAmount;
            $validated['total'] = $total;
            $validated['balance'] = $total - $invoice->paid_amount;
        }

        $invoice->update($validated);

        return new InvoiceResource($invoice->fresh()->load(['customer', 'contract']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        // Ensure tenant can only delete their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only allow deletion of draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft invoices can be deleted.',
            ], 422);
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
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

        $invoice->load(['customer', 'contract.box', 'contract.site']);
        $tenant = $request->user()->tenant;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
