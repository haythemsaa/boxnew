<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Collection;

class ExcelExportService
{
    /**
     * Export customers to CSV
     */
    public function exportCustomers(int $tenantId): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['contracts', 'invoices'])
            ->get();

        $data = [
            ['ID', 'Type', 'Nom complet', 'Société', 'SIRET', 'Email', 'Téléphone', 'Adresse', 'Code postal', 'Ville', 'Statut', 'Contrats actifs', 'Revenu total', 'Balance', 'Date création']
        ];

        foreach ($customers as $customer) {
            $data[] = [
                $customer->id,
                $customer->type === 'individual' ? 'Particulier' : 'Entreprise',
                $customer->full_name,
                $customer->company_name ?? '',
                $customer->siret ?? '',
                $customer->email,
                $customer->phone ?? '',
                $customer->address ?? '',
                $customer->postal_code ?? '',
                $customer->city ?? '',
                $this->translateStatus($customer->status),
                $customer->contracts()->where('status', 'active')->count(),
                number_format($customer->total_revenue, 2, ',', ' ') . ' €',
                number_format($customer->outstanding_balance, 2, ',', ' ') . ' €',
                $customer->created_at->format('d/m/Y'),
            ];
        }

        return [
            'data' => $data,
            'filename' => 'clients_' . date('Y-m-d_His') . '.csv',
        ];
    }

    /**
     * Export invoices to CSV
     */
    public function exportInvoices(int $tenantId, ?string $status = null): array
    {
        $query = Invoice::where('tenant_id', $tenantId)->with('customer');

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $data = [
            ['N° Facture', 'Client', 'Type', 'Date facture', 'Date échéance', 'Période début', 'Période fin', 'Montant HT', 'TVA', 'Montant TTC', 'Payé', 'Reste à payer', 'Statut', 'Relances']
        ];

        foreach ($invoices as $invoice) {
            $data[] = [
                $invoice->invoice_number,
                $invoice->customer->full_name,
                $this->translateInvoiceType($invoice->type),
                $invoice->invoice_date->format('d/m/Y'),
                $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '',
                $invoice->period_start ? $invoice->period_start->format('d/m/Y') : '',
                $invoice->period_end ? $invoice->period_end->format('d/m/Y') : '',
                number_format($invoice->subtotal, 2, ',', ' ') . ' €',
                number_format($invoice->tax_amount, 2, ',', ' ') . ' €',
                number_format($invoice->total, 2, ',', ' ') . ' €',
                number_format($invoice->paid_amount, 2, ',', ' ') . ' €',
                number_format($invoice->remaining_amount, 2, ',', ' ') . ' €',
                $this->translateInvoiceStatus($invoice->status),
                $invoice->reminder_count,
            ];
        }

        return [
            'data' => $data,
            'filename' => 'factures_' . date('Y-m-d_His') . '.csv',
        ];
    }

    /**
     * Export contracts to CSV
     */
    public function exportContracts(int $tenantId, ?string $status = null): array
    {
        $query = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box']);

        if ($status) {
            $query->where('status', $status);
        }

        $contracts = $query->orderBy('start_date', 'desc')->get();

        $data = [
            ['N° Contrat', 'Client', 'Box', 'Type', 'Début', 'Fin', 'Loyer mensuel', 'Dépôt', 'Statut', 'Récurrent', 'Jour facturation', 'Total facturé']
        ];

        foreach ($contracts as $contract) {
            $data[] = [
                $contract->contract_number,
                $contract->customer->full_name,
                $contract->box ? $contract->box->number : 'N/A',
                $this->translateContractType($contract->type),
                $contract->start_date->format('d/m/Y'),
                $contract->end_date ? $contract->end_date->format('d/m/Y') : 'En cours',
                number_format($contract->monthly_rate, 2, ',', ' ') . ' €',
                number_format($contract->deposit_amount, 2, ',', ' ') . ' €',
                $this->translateContractStatus($contract->status),
                $contract->is_recurring ? 'Oui' : 'Non',
                $contract->billing_day ?? 'N/A',
                number_format($contract->total_billed, 2, ',', ' ') . ' €',
            ];
        }

        return [
            'data' => $data,
            'filename' => 'contrats_' . date('Y-m-d_His') . '.csv',
        ];
    }

    /**
     * Export payments to CSV
     */
    public function exportPayments(int $tenantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Payment::where('tenant_id', $tenantId)
            ->with(['customer', 'invoice']);

        if ($startDate) {
            $query->whereDate('paid_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('paid_at', '<=', $endDate);
        }

        $payments = $query->orderBy('paid_at', 'desc')->get();

        $data = [
            ['ID', 'Date', 'Client', 'Facture', 'Type', 'Méthode', 'Montant', 'Statut', 'Référence']
        ];

        foreach ($payments as $payment) {
            $data[] = [
                $payment->id,
                $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : 'N/A',
                $payment->customer->full_name,
                $payment->invoice ? $payment->invoice->invoice_number : 'N/A',
                $this->translatePaymentType($payment->type),
                $this->translatePaymentMethod($payment->payment_method),
                number_format($payment->amount, 2, ',', ' ') . ' €',
                $this->translatePaymentStatus($payment->status),
                $payment->reference ?? '',
            ];
        }

        return [
            'data' => $data,
            'filename' => 'paiements_' . date('Y-m-d_His') . '.csv',
        ];
    }

    /**
     * Generate CSV file from data
     */
    public function generateCSV(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($data as $row) {
            fputcsv($output, $row, ';'); // Use semicolon for French Excel
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // Translation helpers
    private function translateStatus(string $status): string
    {
        return match($status) {
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'suspended' => 'Suspendu',
            'blocked' => 'Bloqué',
            default => $status,
        };
    }

    private function translateInvoiceType(string $type): string
    {
        return match($type) {
            'rental' => 'Location',
            'deposit' => 'Caution',
            'fees' => 'Frais',
            'other' => 'Autre',
            default => $type,
        };
    }

    private function translateInvoiceStatus(string $status): string
    {
        return match($status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
            'partial' => 'Partiellement payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => $status,
        };
    }

    private function translateContractType(string $type): string
    {
        return match($type) {
            'monthly' => 'Mensuel',
            'fixed_term' => 'Durée fixe',
            'temporary' => 'Temporaire',
            default => $type,
        };
    }

    private function translateContractStatus(string $status): string
    {
        return match($status) {
            'draft' => 'Brouillon',
            'active' => 'Actif',
            'expired' => 'Expiré',
            'terminated' => 'Résilié',
            'suspended' => 'Suspendu',
            default => $status,
        };
    }

    private function translatePaymentType(string $type): string
    {
        return match($type) {
            'payment' => 'Paiement',
            'refund' => 'Remboursement',
            'deposit' => 'Acompte',
            default => $type,
        };
    }

    private function translatePaymentMethod(string $method): string
    {
        return match($method) {
            'cash' => 'Espèces',
            'check' => 'Chèque',
            'card' => 'Carte',
            'transfer' => 'Virement',
            'sepa' => 'SEPA',
            default => $method,
        };
    }

    private function translatePaymentStatus(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }
}
