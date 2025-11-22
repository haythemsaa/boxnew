<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring {--dry-run : Run without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring invoices for active contracts based on billing frequency';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        $this->info('Checking for contracts that need invoice generation...');

        // Get active contracts
        $contracts = Contract::where('status', 'active')
            ->with(['customer', 'box', 'site'])
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('No active contracts found.');
            return Command::SUCCESS;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            // Check if invoice generation is needed
            if (!$this->shouldGenerateInvoice($contract)) {
                $skipped++;
                continue;
            }

            $customerName = $contract->customer->type === 'company'
                ? $contract->customer->company_name
                : "{$contract->customer->first_name} {$contract->customer->last_name}";

            if ($dryRun) {
                $this->line("  [DRY RUN] Would generate invoice for: {$contract->contract_number} - {$customerName}");
                $generated++;
                continue;
            }

            // Generate the invoice
            try {
                $invoice = $this->generateInvoice($contract);

                $this->line("  ✓ Generated invoice {$invoice->invoice_number} for contract {$contract->contract_number}");
                $this->line("    Customer: {$customerName}");
                $this->line("    Amount: €{$invoice->total}");
                $this->newLine();

                $generated++;

                Log::info('Recurring invoice generated', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_id' => $contract->customer_id,
                    'amount' => $invoice->total,
                ]);

            } catch (\Exception $e) {
                $this->error("  ✗ Failed to generate invoice for contract {$contract->contract_number}: {$e->getMessage()}");
                Log::error('Failed to generate recurring invoice', [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Generated: {$generated}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Total contracts checked: {$contracts->count()}");

        return Command::SUCCESS;
    }

    /**
     * Determine if an invoice should be generated for the contract.
     */
    private function shouldGenerateInvoice(Contract $contract): bool
    {
        // Don't generate if there's no billing frequency
        if (!$contract->billing_frequency) {
            return false;
        }

        // Get the next billing date based on billing frequency and billing day
        $billingDay = $contract->billing_day ?? 1;
        $today = now();

        // Check if today is the billing day
        if ($today->day != $billingDay) {
            return false;
        }

        // Check if an invoice already exists for this period
        $periodStart = $today->copy()->startOfMonth();
        $periodEnd = $today->copy()->endOfMonth();

        // For quarterly, check last 3 months
        if ($contract->billing_frequency === 'quarterly') {
            $periodStart = $today->copy()->subMonths(2)->startOfMonth();
        }

        // For semi-annual, check last 6 months
        if ($contract->billing_frequency === 'semi_annual') {
            $periodStart = $today->copy()->subMonths(5)->startOfMonth();
        }

        // For annual, check last 12 months
        if ($contract->billing_frequency === 'annual') {
            $periodStart = $today->copy()->subMonths(11)->startOfMonth();
        }

        $existingInvoice = Invoice::where('contract_id', $contract->id)
            ->where('type', 'invoice')
            ->where('period_start', '>=', $periodStart)
            ->where('period_end', '<=', $periodEnd)
            ->exists();

        return !$existingInvoice;
    }

    /**
     * Generate an invoice for the contract.
     */
    private function generateInvoice(Contract $contract): Invoice
    {
        $today = now();

        // Calculate period based on billing frequency
        $periodStart = $today->copy()->startOfMonth();
        $periodEnd = $today->copy()->endOfMonth();
        $multiplier = 1;

        switch ($contract->billing_frequency) {
            case 'quarterly':
                $periodEnd = $today->copy()->addMonths(2)->endOfMonth();
                $multiplier = 3;
                break;
            case 'semi_annual':
                $periodEnd = $today->copy()->addMonths(5)->endOfMonth();
                $multiplier = 6;
                break;
            case 'annual':
                $periodEnd = $today->copy()->addMonths(11)->endOfMonth();
                $multiplier = 12;
                break;
        }

        // Calculate amounts
        $subtotal = $contract->monthly_price * $multiplier;
        $taxRate = 20; // Default VAT rate
        $taxAmount = ($subtotal * $taxRate) / 100;
        $total = $subtotal + $taxAmount;

        // Create line items
        $items = [
            [
                'description' => "Box rental - {$contract->box->name} ({$contract->billing_frequency})",
                'quantity' => $multiplier,
                'unit_price' => $contract->monthly_price,
                'total' => $subtotal,
            ],
        ];

        // Generate invoice number
        $invoiceNumber = 'INV-' . strtoupper(substr(uniqid(), -8));

        // Calculate due date (30 days from invoice date)
        $dueDate = $today->copy()->addDays(30);

        return Invoice::create([
            'tenant_id' => $contract->tenant_id,
            'customer_id' => $contract->customer_id,
            'contract_id' => $contract->id,
            'invoice_number' => $invoiceNumber,
            'type' => 'invoice',
            'status' => 'draft',
            'invoice_date' => $today,
            'due_date' => $dueDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'currency' => 'EUR',
            'is_recurring' => true,
            'notes' => "Automatically generated recurring invoice for contract {$contract->contract_number}",
        ]);
    }
}
