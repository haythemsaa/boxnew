<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(Request $request): Response
    {

        $tenantId = $request->user()->tenant_id;

        $payments = Payment::where('tenant_id', $tenantId)
            ->with(['customer', 'invoice', 'contract'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('gateway_payment_id', 'like', "%{$search}%")
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
            ->when($request->method, function ($query, $method) {
                $query->where('method', $method);
            })
            ->when($request->customer_id, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->latest('paid_at')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Payment::where('tenant_id', $tenantId)->count(),
            'completed' => Payment::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'pending' => Payment::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'failed' => Payment::where('tenant_id', $tenantId)->where('status', 'failed')->count(),
            'total_amount' => Payment::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->where('type', 'payment')
                ->sum('amount'),
        ];

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Tenant/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'type', 'method', 'customer_id']),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request): Response
    {

        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with('customer')
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->select('id', 'invoice_number', 'customer_id', 'total', 'paid_amount')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with('customer')
            ->where('status', 'active')
            ->select('id', 'contract_number', 'customer_id', 'monthly_price')
            ->orderBy('contract_number')
            ->get();

        return Inertia::render('Tenant/Payments/Create', [
            'customers' => $customers,
            'invoices' => $invoices,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Auto-generate payment number if not provided
        if (empty($data['payment_number'])) {
            $prefix = match($data['type']) {
                'refund' => 'REF',
                'deposit' => 'DEP',
                default => 'PAY',
            };
            $data['payment_number'] = $prefix . '-' . strtoupper(substr(uniqid(), -8));
        }

        // Set paid_at if status is completed and not provided
        if ($data['status'] === 'completed' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        // Initialize fee if not set
        if (!isset($data['fee'])) {
            $data['fee'] = 0;
        }

        $payment = Payment::create($data);

        // Update invoice if payment is completed
        if ($payment->status === 'completed' && $payment->invoice) {
            $payment->invoice->recordPayment($payment->amount);
        }

        // Update customer revenue if payment is completed
        if ($payment->status === 'completed' && $payment->customer && $payment->type === 'payment') {
            $payment->customer->increment('total_revenue', $payment->amount);
        }

        return redirect()
            ->route('tenant.payments.index')
            ->with('success', 'Payment created successfully.');
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): Response
    {

        // Ensure tenant can only view their own payments
        if ($payment->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $payment->load(['tenant', 'customer', 'invoice', 'contract', 'refundForPayment']);

        return Inertia::render('Tenant/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Request $request, Payment $payment): Response
    {

        // Ensure tenant can only edit their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with('customer')
            ->where(function ($query) use ($payment) {
                $query->whereIn('status', ['sent', 'overdue', 'partial'])
                    ->orWhere('id', $payment->invoice_id);
            })
            ->select('id', 'invoice_number', 'customer_id', 'total', 'paid_amount')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with('customer')
            ->where(function ($query) use ($payment) {
                $query->where('status', 'active')
                    ->orWhere('id', $payment->contract_id);
            })
            ->select('id', 'contract_number', 'customer_id', 'monthly_price')
            ->orderBy('contract_number')
            ->get();

        return Inertia::render('Tenant/Payments/Edit', [
            'payment' => $payment,
            'customers' => $customers,
            'invoices' => $invoices,
            'contracts' => $contracts,
        ]);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        // Ensure tenant can only update their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $data = $request->validated();

        // Store old values for tracking changes
        $oldStatus = $payment->status;
        $oldAmount = $payment->amount;
        $oldType = $payment->type;

        // Update payment
        $payment->update($data);

        // Handle invoice payment recording changes
        if ($payment->invoice) {
            if ($oldStatus !== 'completed' && $data['status'] === 'completed') {
                // Payment just completed
                $payment->invoice->recordPayment($payment->amount);
            } elseif ($oldStatus === 'completed' && $data['status'] !== 'completed') {
                // Payment was un-completed (reduce invoice paid_amount)
                $newPaidAmount = max(0, $payment->invoice->paid_amount - $oldAmount);
                $payment->invoice->update(['paid_amount' => $newPaidAmount]);
                if ($newPaidAmount < $payment->invoice->total) {
                    $payment->invoice->update(['status' => $newPaidAmount > 0 ? 'partial' : 'sent']);
                }
            }
        }

        // Handle customer revenue tracking
        if ($payment->customer) {
            if ($oldStatus !== 'completed' && $data['status'] === 'completed' && $payment->type === 'payment') {
                $payment->customer->increment('total_revenue', $payment->amount);
            } elseif ($oldStatus === 'completed' && $data['status'] !== 'completed' && $oldType === 'payment') {
                $payment->customer->decrement('total_revenue', $oldAmount);
            }
        }

        return redirect()
            ->route('tenant.payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Request $request, Payment $payment): RedirectResponse
    {

        // Ensure tenant can only delete their own payments
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Reverse invoice payment if completed
        if ($payment->status === 'completed' && $payment->invoice) {
            $newPaidAmount = max(0, $payment->invoice->paid_amount - $payment->amount);
            $payment->invoice->update(['paid_amount' => $newPaidAmount]);
            if ($newPaidAmount < $payment->invoice->total) {
                $payment->invoice->update(['status' => $newPaidAmount > 0 ? 'partial' : 'sent']);
            }
        }

        // Reverse customer revenue if completed payment
        if ($payment->status === 'completed' && $payment->customer && $payment->type === 'payment') {
            $payment->customer->decrement('total_revenue', $payment->amount);
        }

        $payment->delete();

        return redirect()
            ->route('tenant.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}
