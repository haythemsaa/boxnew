<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BulkInvoiceController extends Controller
{
    /**
     * Display the bulk invoicing interface.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Get active contracts without invoice for current period
        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        $contracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['customer', 'box', 'site'])
            ->get();

        // Check which contracts need invoicing
        $contractsToInvoice = $contracts->filter(function ($contract) use ($currentMonth, $nextMonth) {
            // Check if invoice exists for this period
            $invoiceExists = Invoice::where('contract_id', $contract->id)
                ->where('period_start', '>=', $currentMonth)
                ->where('period_start', '<', $nextMonth)
                ->exists();

            return !$invoiceExists;
        });

        // Stats
        $stats = [
            'total_active_contracts' => $contracts->count(),
            'contracts_to_invoice' => $contractsToInvoice->count(),
            'total_monthly_revenue' => $contractsToInvoice->sum('monthly_price'),
            'already_invoiced' => $contracts->count() - $contractsToInvoice->count(),
        ];

        return Inertia::render('Tenant/BulkInvoicing/Index', [
            'contracts' => $contractsToInvoice->values(),
            'stats' => $stats,
            'currentPeriod' => [
                'start' => $currentMonth->format('Y-m-d'),
                'end' => $currentMonth->endOfMonth()->format('Y-m-d'),
                'label' => $currentMonth->translatedFormat('F Y'),
            ],
        ]);
    }

    /**
     * Preview invoices to be generated.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:contracts,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'invoice_date' => 'required|date',
            'due_days' => 'required|integer|min:0|max:90',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $contracts = Contract::where('tenant_id', $tenantId)
            ->whereIn('id', $validated['contract_ids'])
            ->with(['customer', 'box', 'site'])
            ->get();

        $preview = $contracts->map(function ($contract) use ($validated) {
            $subtotal = $contract->monthly_price ?? 0;
            $taxRate = $contract->tax_rate ?? 20;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;

            // Get customer name safely
            $customerName = '-';
            if ($contract->customer) {
                $customerName = $contract->customer->full_name ??
                    ($contract->customer->first_name . ' ' . $contract->customer->last_name);
            }

            return [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number ?? '-',
                'customer_name' => $customerName,
                'customer_email' => $contract->customer?->email ?? '-',
                'box_code' => $contract->box?->code ?? 'N/A',
                'box_size' => $contract->box?->size ?? '-',
                'site_name' => $contract->site?->name ?? '-',
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'subtotal' => round($subtotal, 2),
                'tax_rate' => $taxRate,
                'tax_amount' => round($taxAmount, 2),
                'total' => round($total, 2),
            ];
        });

        return response()->json([
            'invoices' => $preview,
            'summary' => [
                'count' => $preview->count(),
                'total_ht' => $preview->sum('subtotal'),
                'total_tax' => $preview->sum('tax_amount'),
                'total_ttc' => $preview->sum('total'),
            ],
        ]);
    }

    /**
     * Generate bulk invoices.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:contracts,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'invoice_date' => 'required|date',
            'due_days' => 'required|integer|min:0|max:90',
            'send_email' => 'boolean',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $invoicesCreated = 0;

        $contracts = Contract::where('tenant_id', $tenantId)
            ->whereIn('id', $validated['contract_ids'])
            ->with(['customer', 'box', 'site'])
            ->get();

        foreach ($contracts as $contract) {
            // Check if invoice already exists for this period
            $existingInvoice = Invoice::where('contract_id', $contract->id)
                ->where('period_start', $validated['period_start'])
                ->exists();

            if ($existingInvoice) {
                continue;
            }

            $subtotal = $contract->monthly_price ?? 0;
            $taxRate = $contract->tax_rate ?? 20;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;

            // Build invoice items
            $boxCode = $contract->box?->code ?? 'N/A';
            $boxSize = $contract->box?->size ?? '';
            $items = [
                [
                    'description' => "Location box {$boxCode}" . ($boxSize ? " ({$boxSize} m²)" : ''),
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'total' => $subtotal,
                ],
            ];

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'customer_id' => $contract->customer_id,
                'contract_id' => $contract->id,
                'type' => 'invoice',
                'status' => 'sent',
                'invoice_date' => $validated['invoice_date'],
                'due_date' => Carbon::parse($validated['invoice_date'])->addDays($validated['due_days']),
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'items' => $items,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid_amount' => 0,
                'notes' => "Facture générée automatiquement - Location box {$boxCode}",
            ]);

            $invoicesCreated++;

            // TODO: Send email if requested
            if ($validated['send_email'] ?? false) {
                // Send invoice email
            }
        }

        return redirect()->route('tenant.bulk-invoicing.index')
            ->with('success', "{$invoicesCreated} facture(s) générée(s) avec succès.");
    }
}
