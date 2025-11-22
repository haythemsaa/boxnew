<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Box;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Generate Revenue Report
     */
    public function generateRevenueReport(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->get();

        $invoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_collected' => $payments->sum('amount'),
                'total_invoiced' => $invoices->sum('total'),
                'total_outstanding' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('balance'),
                'payment_count' => $payments->count(),
                'invoice_count' => $invoices->count(),
            ],
            'by_method' => $payments->groupBy('method')->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ]),
            'by_month' => $this->groupByMonth($payments, 'paid_at'),
        ];
    }

    /**
     * Generate Occupancy Report
     */
    public function generateOccupancyReport(?int $siteId = null): array
    {
        $query = Box::where('tenant_id', $this->tenantId);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $boxes = $query->get();

        $byStatus = $boxes->groupBy('status')->map->count();
        $byCategory = $boxes->groupBy('size_category')->map->count();

        $totalBoxes = $boxes->count();
        $occupiedBoxes = $boxes->where('status', 'occupied')->count();
        $occupancyRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        return [
            'summary' => [
                'total_units' => $totalBoxes,
                'occupied_units' => $occupiedBoxes,
                'available_units' => $boxes->where('status', 'available')->count(),
                'occupancy_rate' => round($occupancyRate, 2),
            ],
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'revenue_potential' => [
                'current_mrr' => $boxes->where('status', 'occupied')->sum('current_price'),
                'potential_mrr' => $boxes->sum('current_price'),
                'lost_revenue' => $boxes->where('status', 'available')->sum('current_price'),
            ],
        ];
    }

    /**
     * Generate Customer Report
     */
    public function generateCustomerReport(Carbon $startDate, Carbon $endDate): array
    {
        $customers = Customer::where('tenant_id', $this->tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $allCustomers = Customer::where('tenant_id', $this->tenantId)->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'new_customers' => $customers->count(),
                'total_customers' => $allCustomers->count(),
                'active_customers' => $allCustomers->where('status', 'active')->count(),
            ],
            'by_type' => $customers->groupBy('type')->map->count(),
            'by_status' => $allCustomers->groupBy('status')->map->count(),
            'top_customers' => $this->getTopCustomers(10),
        ];
    }

    /**
     * Generate Move-In/Move-Out Report
     */
    public function generateMoveReport(Carbon $startDate, Carbon $endDate): array
    {
        $moveIns = Contract::where('tenant_id', $this->tenantId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with(['customer', 'box'])
            ->get();

        $moveOuts = Contract::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['expired', 'cancelled'])
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->with(['customer', 'box'])
            ->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'move_ins' => $moveIns->count(),
                'move_outs' => $moveOuts->count(),
                'net_change' => $moveIns->count() - $moveOuts->count(),
            ],
            'move_ins_detail' => $moveIns->map(fn($contract) => [
                'date' => $contract->start_date->format('Y-m-d'),
                'customer' => $contract->customer->type === 'company'
                    ? $contract->customer->company_name
                    : $contract->customer->first_name . ' ' . $contract->customer->last_name,
                'box' => $contract->box->name,
                'monthly_price' => $contract->monthly_price,
            ]),
            'move_outs_detail' => $moveOuts->map(fn($contract) => [
                'date' => $contract->updated_at->format('Y-m-d'),
                'customer' => $contract->customer->type === 'company'
                    ? $contract->customer->company_name
                    : $contract->customer->first_name . ' ' . $contract->customer->last_name,
                'box' => $contract->box->name,
                'reason' => $contract->cancellation_reason,
            ]),
        ];
    }

    /**
     * Generate Expiring Contracts Report
     */
    public function generateExpiringContractsReport(int $days = 30): array
    {
        $expiringContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now())
            ->with(['customer', 'box', 'site'])
            ->orderBy('end_date')
            ->get();

        return [
            'summary' => [
                'total_expiring' => $expiringContracts->count(),
                'potential_revenue_loss' => $expiringContracts->sum('monthly_price'),
            ],
            'contracts' => $expiringContracts->map(fn($contract) => [
                'contract_number' => $contract->contract_number,
                'customer' => $contract->customer->type === 'company'
                    ? $contract->customer->company_name
                    : $contract->customer->first_name . ' ' . $contract->customer->last_name,
                'customer_email' => $contract->customer->email,
                'customer_phone' => $contract->customer->phone,
                'box' => $contract->box->name,
                'site' => $contract->site->name,
                'end_date' => $contract->end_date->format('Y-m-d'),
                'days_until_expiry' => now()->diffInDays($contract->end_date),
                'monthly_price' => $contract->monthly_price,
                'auto_renew' => $contract->auto_renew,
            ]),
        ];
    }

    /**
     * Generate Overdue Invoices Report
     */
    public function generateOverdueInvoicesReport(): array
    {
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'overdue')
            ->with(['customer', 'contract'])
            ->orderBy('due_date')
            ->get();

        return [
            'summary' => [
                'total_overdue' => $overdueInvoices->count(),
                'total_amount' => $overdueInvoices->sum('balance'),
                'average_days_overdue' => $overdueInvoices->avg(fn($inv) => now()->diffInDays($inv->due_date)),
            ],
            'invoices' => $overdueInvoices->map(fn($invoice) => [
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer->type === 'company'
                    ? $invoice->customer->company_name
                    : $invoice->customer->first_name . ' ' . $invoice->customer->last_name,
                'customer_email' => $invoice->customer->email,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'days_overdue' => now()->diffInDays($invoice->due_date),
                'balance' => $invoice->balance,
                'reminder_count' => $invoice->reminder_count,
            ]),
        ];
    }

    /**
     * Get top customers by revenue
     */
    protected function getTopCustomers(int $limit = 10): array
    {
        return Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->limit($limit)
            ->with('customer')
            ->get()
            ->map(fn($payment) => [
                'customer' => $payment->customer->type === 'company'
                    ? $payment->customer->company_name
                    : $payment->customer->first_name . ' ' . $payment->customer->last_name,
                'total_paid' => $payment->total_paid,
            ])
            ->toArray();
    }

    /**
     * Group data by month
     */
    protected function groupByMonth($collection, string $dateField): array
    {
        return $collection->groupBy(function ($item) use ($dateField) {
            return Carbon::parse($item->$dateField)->format('Y-m');
        })->map(fn($group) => [
            'count' => $group->count(),
            'total' => $group->sum('amount'),
        ])->toArray();
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV(array $data, string $filename): string
    {
        $filepath = storage_path('app/reports/' . $filename);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Write headers
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));

            // Write data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }

        fclose($file);

        return $filepath;
    }
}
