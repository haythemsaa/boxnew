<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;

class InvoiceGenerationService
{
    protected ContractAddonService $contractAddonService;

    public function __construct(ContractAddonService $contractAddonService)
    {
        $this->contractAddonService = $contractAddonService;
    }

    /**
     * Generate invoices for active contracts based on billing frequency.
     */
    public function generateInvoicesForContracts(?int $tenantId = null): array
    {
        $generated = [];
        $failed = [];

        // Get active contracts
        $query = Contract::where('status', 'active')
            ->with(['customer', 'box', 'site']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $contracts = $query->get();

        foreach ($contracts as $contract) {
            try {
                if ($this->shouldGenerateInvoice($contract)) {
                    $invoice = $this->createInvoice($contract);
                    $generated[] = $invoice;
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
            'total' => count($generated),
        ];
    }

    /**
     * Check if an invoice should be generated for a contract.
     */
    public function shouldGenerateInvoice(Contract $contract): bool
    {
        // Check if contract is active and within date range
        if ($contract->status !== 'active') {
            return false;
        }

        if ($contract->end_date && $contract->end_date->isPast()) {
            return false;
        }

        // Check if invoice already exists for this period
        $invoicePeriod = $this->getInvoicePeriod($contract);

        $existingInvoice = Invoice::where('contract_id', $contract->id)
            ->whereDate('period_start', $invoicePeriod['start'])
            ->whereDate('period_end', $invoicePeriod['end'])
            ->exists();

        return !$existingInvoice;
    }

    /**
     * Create an invoice for a contract.
     */
    public function createInvoice(Contract $contract): Invoice
    {
        $invoicePeriod = $this->getInvoicePeriod($contract);
        $invoiceNumber = $this->generateInvoiceNumber($contract);

        // Start with storage item
        $items = [
            [
                'type' => 'storage',
                'product_id' => null,
                'description' => "Location Box - {$contract->box->code}" . ($contract->box->size ? " ({$contract->box->size}m²)" : ""),
                'quantity' => 1,
                'unit_price' => $contract->monthly_price,
                'tax_rate' => 20,
                'discount' => 0,
                'total' => $contract->monthly_price,
            ],
        ];

        $subtotal = $contract->monthly_price;

        // Add contract addons (recurring services)
        $addonItems = $this->getAddonItemsForBillingPeriod($contract, $invoicePeriod);
        foreach ($addonItems as $addonItem) {
            $items[] = $addonItem;
            $subtotal += $addonItem['total'];
        }

        // Apply discount if any
        $discountAmount = 0;

        if ($contract->discount_percentage > 0) {
            $discountAmount = $subtotal * ($contract->discount_percentage / 100);
        } elseif ($contract->discount_amount > 0) {
            $discountAmount = $contract->discount_amount;
        }

        // Calculate tax (default 20% TVA for France)
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $this->calculateTaxAmount($items, $discountAmount);
        $total = $taxableAmount + $taxAmount;

        $dueDate = Carbon::parse($invoicePeriod['end'])->addDays(30); // 30-day payment terms

        $invoice = Invoice::create([
            'tenant_id' => $contract->tenant_id,
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'invoice_number' => $invoiceNumber,
            'type' => 'monthly',
            'status' => 'draft',
            'invoice_date' => now()->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'period_start' => $invoicePeriod['start'],
            'period_end' => $invoicePeriod['end'],
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_rate' => 20,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'paid_amount' => 0,
            'currency' => 'EUR',
            'is_recurring' => true,
            'reminder_count' => 0,
        ]);

        // Update addon next billing dates
        $this->updateAddonBillingDates($contract, $invoicePeriod);

        return $invoice;
    }

    /**
     * Get addon items for the billing period.
     */
    protected function getAddonItemsForBillingPeriod(Contract $contract, array $period): array
    {
        $items = [];

        $activeAddons = $contract->addons()
            ->where('status', 'active')
            ->where(function ($query) use ($period) {
                $query->where('start_date', '<=', $period['end'])
                    ->where(function ($q) use ($period) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $period['start']);
                    });
            })
            ->get();

        foreach ($activeAddons as $addon) {
            // Check if this addon should be billed in this period
            if ($this->shouldBillAddon($addon, $period)) {
                $items[] = [
                    'type' => 'addon',
                    'product_id' => $addon->product_id,
                    'addon_id' => $addon->id,
                    'description' => $addon->product_name,
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'tax_rate' => $addon->tax_rate ?? 20,
                    'discount' => 0,
                    'total' => $addon->quantity * $addon->unit_price,
                ];
            }
        }

        return $items;
    }

    /**
     * Check if an addon should be billed in a given period.
     */
    protected function shouldBillAddon($addon, array $period): bool
    {
        // For monthly addons, always bill
        if ($addon->billing_period === 'monthly') {
            return true;
        }

        // For quarterly, only bill at quarter start
        if ($addon->billing_period === 'quarterly') {
            $periodStart = Carbon::parse($period['start']);
            return in_array($periodStart->month, [1, 4, 7, 10]);
        }

        // For yearly, only bill at year start
        if ($addon->billing_period === 'yearly') {
            $periodStart = Carbon::parse($period['start']);
            return $periodStart->month === 1;
        }

        return true;
    }

    /**
     * Calculate total tax amount from items.
     */
    protected function calculateTaxAmount(array $items, float $discountAmount = 0): float
    {
        $totalBeforeDiscount = array_sum(array_column($items, 'total'));
        if ($totalBeforeDiscount == 0) {
            return 0;
        }

        $discountRatio = $discountAmount / $totalBeforeDiscount;
        $taxAmount = 0;

        foreach ($items as $item) {
            $itemTotal = $item['total'];
            $itemDiscount = $itemTotal * $discountRatio;
            $taxableAmount = $itemTotal - $itemDiscount;
            $taxRate = $item['tax_rate'] ?? 20;
            $taxAmount += $taxableAmount * ($taxRate / 100);
        }

        return round($taxAmount, 2);
    }

    /**
     * Update addon next billing dates after invoice generation.
     */
    protected function updateAddonBillingDates(Contract $contract, array $period): void
    {
        $periodEnd = Carbon::parse($period['end']);

        $contract->addons()
            ->where('status', 'active')
            ->each(function ($addon) use ($periodEnd) {
                $addon->update([
                    'next_billing_date' => $this->calculateNextBillingDate($addon, $periodEnd),
                ]);
            });
    }

    /**
     * Calculate the next billing date for an addon.
     */
    protected function calculateNextBillingDate($addon, Carbon $currentPeriodEnd): Carbon
    {
        switch ($addon->billing_period) {
            case 'monthly':
                return $currentPeriodEnd->copy()->addMonth()->startOfMonth();
            case 'quarterly':
                return $currentPeriodEnd->copy()->addMonths(3)->startOfMonth();
            case 'yearly':
                return $currentPeriodEnd->copy()->addYear()->startOfYear();
            default:
                return $currentPeriodEnd->copy()->addMonth()->startOfMonth();
        }
    }

    /**
     * Get the invoice period for a contract based on billing frequency.
     */
    public function getInvoicePeriod(Contract $contract): array
    {
        $today = now();

        // Determine period based on billing frequency
        switch ($contract->billing_frequency) {
            case 'monthly':
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
                break;
            case 'quarterly':
                $quarter = ceil($today->month / 3);
                $start = Carbon::create($today->year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
                $end = Carbon::create($today->year, $quarter * 3, 1)->endOfMonth();
                break;
            case 'yearly':
                $start = $today->copy()->startOfYear();
                $end = $today->copy()->endOfYear();
                break;
            default:
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    /**
     * Generate a unique invoice number.
     */
    public function generateInvoiceNumber(Contract $contract): string
    {
        $year = now()->year;
        $month = now()->month;

        $sequence = Invoice::where('tenant_id', $contract->tenant_id)
            ->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->count() + 1;

        return sprintf('INV-%s-%02d-%04d', $year, $month, $sequence);
    }

    /**
     * Send invoice to customer (mark as sent).
     */
    public function sendInvoice(Invoice $invoice): bool
    {
        $invoice->update([
            'status' => 'sent',
        ]);

        // TODO: Send email to customer

        return true;
    }

    /**
     * Record payment for an invoice.
     */
    public function recordPayment(Invoice $invoice, float $amount, string $method = 'bank_transfer'): void
    {
        $invoice->recordPayment($amount);

        // Create payment record
        \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $amount,
            'payment_method' => $method,
            'paid_at' => now(),
            'reference' => 'Manual payment',
        ]);
    }

    /**
     * Send payment reminder for overdue invoices.
     */
    public function sendPaymentReminders(?int $tenantId = null): array
    {
        $overdue = Invoice::overdue();

        if ($tenantId) {
            $overdue = $overdue->where('tenant_id', $tenantId);
        }

        $reminders = [];

        foreach ($overdue->get() as $invoice) {
            try {
                $invoice->sendReminder();

                // TODO: Send email reminder

                $reminders[] = [
                    'invoice_id' => $invoice->id,
                    'sent' => true,
                ];
            } catch (\Exception $e) {
                $reminders[] = [
                    'invoice_id' => $invoice->id,
                    'sent' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $reminders;
    }
}
