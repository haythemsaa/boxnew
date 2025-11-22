<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalPaymentController extends Controller
{
    /**
     * Display a listing of the customer's payments.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        $payments = $customer->payments()
            ->with(['invoice'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('method'), function ($query, $method) {
                $query->where('method', $method);
            })
            ->orderBy('paid_at', 'desc')
            ->paginate(10);

        // Calculate totals
        $totals = [
            'total_paid' => $customer->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'pending' => $customer->payments()
                ->where('status', 'pending')
                ->sum('amount'),
        ];

        return Inertia::render('Portal/Payments/Index', [
            'payments' => $payments,
            'totals' => $totals,
            'filters' => [
                'status' => $request->input('status'),
                'method' => $request->input('method'),
            ],
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, Payment $payment): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        // Ensure customer can only view their own payments
        if (!$customer || $payment->customer_id !== $customer->id) {
            abort(403, 'Unauthorized to view this payment.');
        }

        $payment->load(['invoice']);

        return Inertia::render('Portal/Payments/Show', [
            'payment' => $payment,
        ]);
    }
}
