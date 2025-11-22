<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Process automated billing for all active contracts
     */
    public function processAutomatedBilling(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'errors' => [],
        ];

        // Get contracts that need billing
        $contractsToBill = $this->getContractsNeedingBilling();

        foreach ($contractsToBill as $contract) {
            try {
                $invoice = $this->generateInvoiceForContract($contract);

                if ($this->shouldAttemptAutoPayment($contract)) {
                    $this->attemptAutoPayment($invoice);
                }

                $results['processed']++;
                $results['total_amount'] += $invoice->total;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Billing failed for contract', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get contracts that need billing
     */
    protected function getContractsNeedingBilling()
    {
        $today = now();

        return Contract::where('status', 'active')
            ->where(function ($query) use ($today) {
                // Monthly billing
                $query->where(function ($q) use ($today) {
                    $q->where('billing_frequency', 'monthly')
                      ->where('billing_day', $today->day);
                })
                // Quarterly billing
                ->orWhere(function ($q) use ($today) {
                    $q->where('billing_frequency', 'quarterly')
                      ->where('billing_day', $today->day)
                      ->whereRaw('MONTH(start_date) % 3 = MONTH(?) % 3', [$today]);
                })
                // Yearly billing
                ->orWhere(function ($q) use ($today) {
                    $q->where('billing_frequency', 'yearly')
                      ->where('billing_day', $today->day)
                      ->whereMonth('start_date', $today->month);
                });
            })
            ->with(['customer', 'box', 'site'])
            ->get();
    }

    /**
     * Generate invoice for a contract
     */
    public function generateInvoiceForContract(Contract $contract): Invoice
    {
        // Calculate billing period
        $billingPeriod = $this->calculateBillingPeriod($contract);

        // Check if invoice already exists for this period
        $existingInvoice = Invoice::where('contract_id', $contract->id)
            ->where('period_start', $billingPeriod['start'])
            ->where('period_end', $billingPeriod['end'])
            ->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        // Create invoice items
        $items = [];

        // Main rental charge
        $items[] = [
            'description' => "Storage rental - {$contract->box->name} ({$billingPeriod['start']->format('M d')} - {$billingPeriod['end']->format('M d, Y')})",
            'quantity' => 1,
            'unit_price' => $contract->monthly_price,
            'total' => $contract->monthly_price,
        ];

        // Add recurring services/products
        $recurringProducts = $this->getRecurringProductsForContract($contract);
        foreach ($recurringProducts as $product) {
            $items[] = [
                'description' => $product->name,
                'quantity' => 1,
                'unit_price' => $product->price,
                'total' => $product->price,
            ];
        }

        // Calculate totals
        $subtotal = collect($items)->sum('total');
        $taxRate = 20; // 20% VAT (configurable)
        $taxAmount = ($subtotal * $taxRate) / 100;
        $total = $subtotal + $taxAmount;

        // Create invoice
        $invoice = Invoice::create([
            'tenant_id' => $contract->tenant_id,
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(15), // 15 days payment term
            'status' => 'sent',
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance' => $total,
            'period_start' => $billingPeriod['start'],
            'period_end' => $billingPeriod['end'],
        ]);

        // Send invoice notification
        $contract->customer->user?->notify(new \App\Notifications\InvoiceCreatedNotification($invoice));

        return $invoice;
    }

    /**
     * Calculate billing period for contract
     */
    protected function calculateBillingPeriod(Contract $contract): array
    {
        $today = now();

        return match ($contract->billing_frequency) {
            'monthly' => [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $today->copy()->firstOfQuarter(),
                'end' => $today->copy()->lastOfQuarter(),
            ],
            'yearly' => [
                'start' => $today->copy()->startOfYear(),
                'end' => $today->copy()->endOfYear(),
            ],
            default => [
                'start' => $today->copy(),
                'end' => $today->copy()->addMonth(),
            ],
        };
    }

    /**
     * Get recurring products for contract
     */
    protected function getRecurringProductsForContract(Contract $contract): array
    {
        // This would fetch from a contract_products pivot table
        // For now, returning empty array
        return [];
    }

    /**
     * Check if auto-payment should be attempted
     */
    protected function shouldAttemptAutoPayment(Contract $contract): bool
    {
        return $contract->payment_method === 'credit_card'
            && $contract->customer->stripe_customer_id !== null;
    }

    /**
     * Attempt automatic payment
     */
    protected function attemptAutoPayment(Invoice $invoice): void
    {
        $customer = $invoice->customer;

        if (!$customer->stripe_customer_id) {
            throw new \Exception('No Stripe customer ID found');
        }

        try {
            // Create payment intent
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $invoice->total,
                'eur',
                [
                    'customer' => $customer->stripe_customer_id,
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'customer_id' => $customer->id,
                    ],
                ]
            );

            // Create payment record
            Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'payment_number' => Payment::generatePaymentNumber(),
                'amount' => $invoice->total,
                'method' => 'credit_card',
                'status' => 'pending',
                'transaction_id' => $paymentIntent->id,
                'paid_at' => now(),
            ]);

            Log::info('Auto-payment attempted', [
                'invoice_id' => $invoice->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-payment failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process late fees for overdue invoices
     */
    public function processLateFees(): array
    {
        $results = [
            'processed' => 0,
            'total_fees' => 0,
        ];

        $overdueInvoices = Invoice::where('status', 'overdue')
            ->where('due_date', '<', now()->subDays(7)) // 7 days grace period
            ->whereNull('late_fee_applied_at')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $lateFee = $this->calculateLateFee($invoice);

            if ($lateFee > 0) {
                // Add late fee to invoice
                $items = $invoice->items;
                $items[] = [
                    'description' => 'Late payment fee',
                    'quantity' => 1,
                    'unit_price' => $lateFee,
                    'total' => $lateFee,
                ];

                $invoice->update([
                    'items' => $items,
                    'subtotal' => $invoice->subtotal + $lateFee,
                    'total' => $invoice->total + $lateFee,
                    'balance' => $invoice->balance + $lateFee,
                    'late_fee_applied_at' => now(),
                ]);

                $results['processed']++;
                $results['total_fees'] += $lateFee;
            }
        }

        return $results;
    }

    /**
     * Calculate late fee
     */
    protected function calculateLateFee(Invoice $invoice): float
    {
        // Fixed late fee of €25 or 5% of balance, whichever is greater
        $percentageFee = $invoice->balance * 0.05;
        $fixedFee = 25.00;

        return max($percentageFee, $fixedFee);
    }

    /**
     * Send payment reminders
     */
    public function sendPaymentReminders(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
        ];

        // Invoices due in 3 days - First reminder
        $upcomingInvoices = Invoice::where('status', 'sent')
            ->whereBetween('due_date', [now(), now()->addDays(3)])
            ->whereNull('first_reminder_sent_at')
            ->with('customer')
            ->get();

        foreach ($upcomingInvoices as $invoice) {
            $invoice->customer->user?->notify(new \App\Notifications\InvoiceReminderNotification($invoice));
            $invoice->update(['first_reminder_sent_at' => now()]);
            $results['sent']++;
        }

        // Overdue invoices - Second reminder
        $overdueInvoices = Invoice::where('status', 'overdue')
            ->where('due_date', '<', now())
            ->where(function ($query) {
                $query->whereNull('last_reminder_sent')
                      ->orWhere('last_reminder_sent', '<', now()->subDays(7));
            })
            ->with('customer')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->customer->user?->notify(new \App\Notifications\InvoiceOverdueNotification($invoice));
            $invoice->update(['last_reminder_sent' => now()]);
            $invoice->increment('reminder_count');
            $results['sent']++;
        }

        return $results;
    }
}
