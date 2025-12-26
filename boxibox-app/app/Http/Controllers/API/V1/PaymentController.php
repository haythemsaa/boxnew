<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $payments = Payment::where('tenant_id', $tenantId)
            ->with(['customer', 'invoice'])
            ->when($request->input('customer_id'), function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('method'), function ($query, $method) {
                $query->where('method', $method);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderBy('paid_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Get payments by customer.
     */
    public function byCustomer(Request $request, Customer $customer): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $payments = $customer->payments()
            ->with(['invoice'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('paid_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Get payments by invoice.
     */
    public function byInvoice(Request $request, Invoice $invoice): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own invoices
        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $payments = $invoice->payments()
            ->with(['customer'])
            ->orderBy('paid_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'invoice_id' => ['nullable', 'exists:invoices,id', new \App\Rules\SameTenantResource(\App\Models\Invoice::class, $tenantId)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:bank_transfer,credit_card,direct_debit,cash,check'],
            'status' => ['required', 'in:pending,completed,failed,refunded'],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'card_brand' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = Payment::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'payment_number' => Payment::generatePaymentNumber(),
            'processed_at' => $validated['status'] === 'completed' ? now() : null,
            'processed_by' => $validated['status'] === 'completed' ? $request->user()->id : null,
        ]);

        // Update invoice if linked
        if ($payment->invoice_id && $payment->status === 'completed') {
            $invoice = $payment->invoice;
            $invoice->paid_amount += $payment->amount;
            $invoice->balance = $invoice->total - $invoice->paid_amount;

            // Update invoice status
            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
            }

            $invoice->save();
        }

        return (new PaymentResource($payment->load(['customer', 'invoice'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Payment $payment): PaymentResource
    {
        // Ensure tenant can only view their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $payment->load(['customer', 'invoice']);

        return new PaymentResource($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment): PaymentResource
    {
        // Ensure tenant can only update their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'invoice_id' => ['nullable', 'exists:invoices,id', new \App\Rules\SameTenantResource(\App\Models\Invoice::class, $tenantId)],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'method' => ['sometimes', 'in:bank_transfer,credit_card,direct_debit,cash,check'],
            'status' => ['sometimes', 'in:pending,completed,failed,refunded'],
            'paid_at' => ['sometimes', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'card_brand' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Handle status change to completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && $payment->status !== 'completed') {
            $validated['processed_at'] = now();
            $validated['processed_by'] = $request->user()->id;

            // Update invoice if linked
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                $invoice->paid_amount += $payment->amount;
                $invoice->balance = $invoice->total - $invoice->paid_amount;

                if ($invoice->balance <= 0) {
                    $invoice->status = 'paid';
                    $invoice->paid_at = now();
                }

                $invoice->save();
            }
        }

        $payment->update($validated);

        return new PaymentResource($payment->fresh()->load(['customer', 'invoice']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        // Ensure tenant can only delete their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only allow deletion of pending or failed payments
        if (!in_array($payment->status, ['pending', 'failed'])) {
            return response()->json([
                'message' => 'Only pending or failed payments can be deleted.',
            ], 422);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully',
        ]);
    }
}
