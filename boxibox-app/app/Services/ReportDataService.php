<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\CustomReport;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    /**
     * Generate report data based on report type
     */
    public function generateReportData(CustomReport $report, int $tenantId, array $filters = []): array
    {
        return match ($report->type) {
            'rent_roll' => $this->getRentRollData($tenantId, $filters),
            'revenue' => $this->getRevenueData($tenantId, $filters),
            'occupancy' => $this->getOccupancyData($tenantId, $filters),
            'aging' => $this->getAgingData($tenantId, $filters),
            'activity' => $this->getActivityData($tenantId, $filters),
            default => $this->getCustomData($report, $tenantId, $filters),
        };
    }

    /**
     * Get rent roll data
     */
    public function getRentRollData(int $tenantId, array $filters = []): array
    {
        $query = Contract::with([
                'customer:id,first_name,last_name,company_name,email,phone,type',
                'box:id,number,name,length,width',
                'site:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        // Apply filters
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        return $query->get()
            ->map(fn($c) => [
                'contract' => $c->contract_number,
                'customer' => $c->customer?->full_name ?? '',
                'email' => $c->customer?->email ?? '',
                'phone' => $c->customer?->phone ?? '',
                'site' => $c->site?->name ?? '',
                'box' => $c->box?->number ?? $c->box?->name ?? '',
                'size_m2' => $c->box ? round($c->box->length * $c->box->width, 2) : 0,
                'monthly_rent' => $c->monthly_price ?? 0,
                'start_date' => $c->start_date?->format('d/m/Y'),
                'end_date' => $c->end_date?->format('d/m/Y') ?? 'Indéterminé',
            ])
            ->toArray();
    }

    /**
     * Get revenue data
     */
    public function getRevenueData(int $tenantId, array $filters = []): array
    {
        $query = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with(['invoice.customer', 'invoice.contract.site']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['site_id'])) {
            $query->whereHas('invoice.contract', function ($q) use ($filters) {
                $q->where('site_id', $filters['site_id']);
            });
        }

        return $query->get()
            ->map(fn($p) => [
                'date' => $p->paid_at?->format('d/m/Y') ?? '',
                'reference' => $p->payment_number ?? $p->reference ?? '',
                'customer' => $p->invoice?->customer?->full_name ?? '',
                'invoice' => $p->invoice?->invoice_number ?? '',
                'site' => $p->invoice?->contract?->site?->name ?? '',
                'amount' => $p->amount ?? 0,
                'method' => $p->payment_method ?? '',
            ])
            ->toArray();
    }

    /**
     * Get occupancy data
     */
    public function getOccupancyData(int $tenantId, array $filters = []): array
    {
        $query = Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))
            ->with([
                'site:id,name',
                'contracts' => fn($q) => $q->where('status', 'active')->with('customer:id,first_name,last_name,company_name,type'),
            ]);

        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()
            ->map(fn($b) => [
                'site' => $b->site?->name ?? '',
                'box' => $b->number ?? $b->name ?? '',
                'size_m2' => round($b->length * $b->width, 2),
                'volume_m3' => round($b->volume ?? 0, 2),
                'base_price' => $b->base_price ?? 0,
                'current_price' => $b->current_price ?? 0,
                'status' => $b->status === 'occupied' ? 'Occupé' : ($b->status === 'available' ? 'Disponible' : ucfirst($b->status)),
                'customer' => $b->contracts->first()?->customer?->full_name ?? '',
            ])
            ->toArray();
    }

    /**
     * Get aging (overdue invoices) data
     */
    public function getAgingData(int $tenantId, array $filters = []): array
    {
        $query = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', now())
            ->with(['customer', 'contract.site']);

        if (!empty($filters['site_id'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('site_id', $filters['site_id']);
            });
        }

        return $query->orderBy('due_date')
            ->get()
            ->map(fn($i) => [
                'invoice' => $i->invoice_number ?? '',
                'customer' => $i->customer?->full_name ?? '',
                'site' => $i->contract?->site?->name ?? '',
                'total' => $i->total ?? 0,
                'paid' => $i->paid_amount ?? 0,
                'remaining' => ($i->total ?? 0) - ($i->paid_amount ?? 0),
                'due_date' => $i->due_date?->format('d/m/Y') ?? '',
                'days_overdue' => $i->due_date ? $i->due_date->diffInDays(now()) : 0,
                'aging_bucket' => $this->getAgingBucket($i->due_date),
            ])
            ->toArray();
    }

    /**
     * Get aging bucket for an invoice
     */
    protected function getAgingBucket($dueDate): string
    {
        if (!$dueDate) {
            return 'Inconnu';
        }

        $daysOverdue = $dueDate->diffInDays(now());

        return match (true) {
            $daysOverdue <= 30 => '0-30 jours',
            $daysOverdue <= 60 => '31-60 jours',
            $daysOverdue <= 90 => '61-90 jours',
            default => '90+ jours',
        };
    }

    /**
     * Get activity data
     */
    public function getActivityData(int $tenantId, array $filters = []): array
    {
        // Activity logs for the tenant
        $activities = collect();

        // Recent contracts
        $recentContracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer:id,first_name,last_name,company_name,type', 'box:id,number,name'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn($c) => [
                'date' => $c->created_at?->format('d/m/Y H:i') ?? '',
                'type' => 'Contrat',
                'action' => 'Nouveau contrat ' . $c->contract_number,
                'customer' => $c->customer?->full_name ?? '',
                'box' => $c->box?->number ?? $c->box?->name ?? '',
                'value' => $c->monthly_price ?? 0,
            ]);

        // Recent payments
        $recentPayments = Payment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with(['invoice.customer'])
            ->latest('paid_at')
            ->limit(100)
            ->get()
            ->map(fn($p) => [
                'date' => $p->paid_at?->format('d/m/Y H:i') ?? '',
                'type' => 'Paiement',
                'action' => 'Paiement reçu',
                'customer' => $p->invoice?->customer?->full_name ?? '',
                'box' => '',
                'value' => $p->amount ?? 0,
            ]);

        return $activities
            ->merge($recentContracts)
            ->merge($recentPayments)
            ->sortByDesc('date')
            ->values()
            ->take(100)
            ->toArray();
    }

    /**
     * Get custom report data
     */
    public function getCustomData(CustomReport $report, int $tenantId, array $filters = []): array
    {
        // For custom reports, we need to build the query dynamically
        // based on the report configuration
        return [];
    }

    /**
     * Get cash flow projection
     */
    public function getCashFlowProjection(int $tenantId, int $months = 3): array
    {
        $projection = [];

        // Get MRR (Monthly Recurring Revenue)
        $mrr = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');

        // Get expected payments from pending invoices
        $expectedPayments = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addMonths($months))
            ->selectRaw('DATE_FORMAT(due_date, "%Y-%m") as month, SUM(total - paid_amount) as expected')
            ->groupBy('month')
            ->pluck('expected', 'month')
            ->toArray();

        // Get overdue amounts
        $overdueAmount = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', now())
            ->selectRaw('SUM(total - paid_amount) as total')
            ->value('total') ?? 0;

        for ($i = 0; $i < $months; $i++) {
            $date = now()->addMonths($i);
            $monthKey = $date->format('Y-m');

            $projection[] = [
                'month' => $date->format('M Y'),
                'month_key' => $monthKey,
                'mrr' => $mrr,
                'expected_payments' => $expectedPayments[$monthKey] ?? 0,
                'total_expected' => $mrr + ($expectedPayments[$monthKey] ?? 0),
            ];
        }

        return [
            'projection' => $projection,
            'mrr' => $mrr,
            'overdue' => $overdueAmount,
            'total_expected_3m' => array_sum(array_column($projection, 'total_expected')),
        ];
    }

    /**
     * Get available report types
     */
    public function getReportTypes(): array
    {
        return [
            ['value' => 'rent_roll', 'label' => 'Rent Roll', 'description' => 'Liste des contrats actifs avec loyers'],
            ['value' => 'revenue', 'label' => 'Revenus', 'description' => 'Détail des paiements reçus'],
            ['value' => 'occupancy', 'label' => 'Occupation', 'description' => 'État des boxes et occupation'],
            ['value' => 'aging', 'label' => 'Impayés', 'description' => 'Factures en retard de paiement'],
            ['value' => 'activity', 'label' => 'Activité', 'description' => 'Historique des actions récentes'],
            ['value' => 'custom', 'label' => 'Personnalisé', 'description' => 'Rapport sur mesure'],
        ];
    }

    /**
     * Get available columns for custom reports
     */
    public function getAvailableColumns(): array
    {
        return [
            'contracts' => [
                ['value' => 'contract_number', 'label' => 'N° Contrat'],
                ['value' => 'customer_name', 'label' => 'Client'],
                ['value' => 'site_name', 'label' => 'Site'],
                ['value' => 'box_number', 'label' => 'Box'],
                ['value' => 'monthly_price', 'label' => 'Loyer mensuel'],
                ['value' => 'start_date', 'label' => 'Date début'],
                ['value' => 'end_date', 'label' => 'Date fin'],
                ['value' => 'status', 'label' => 'Statut'],
            ],
            'invoices' => [
                ['value' => 'invoice_number', 'label' => 'N° Facture'],
                ['value' => 'customer_name', 'label' => 'Client'],
                ['value' => 'total', 'label' => 'Total'],
                ['value' => 'paid_amount', 'label' => 'Payé'],
                ['value' => 'due_date', 'label' => 'Échéance'],
                ['value' => 'status', 'label' => 'Statut'],
            ],
            'payments' => [
                ['value' => 'payment_date', 'label' => 'Date'],
                ['value' => 'customer_name', 'label' => 'Client'],
                ['value' => 'amount', 'label' => 'Montant'],
                ['value' => 'method', 'label' => 'Méthode'],
                ['value' => 'reference', 'label' => 'Référence'],
            ],
            'boxes' => [
                ['value' => 'site_name', 'label' => 'Site'],
                ['value' => 'box_number', 'label' => 'Numéro'],
                ['value' => 'size', 'label' => 'Taille'],
                ['value' => 'price', 'label' => 'Prix'],
                ['value' => 'status', 'label' => 'Statut'],
            ],
        ];
    }
}
