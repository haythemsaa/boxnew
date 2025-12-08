<?php

namespace App\Services;

use App\Models\AccountingProvider;
use App\Models\AccountingConnection;
use App\Models\AccountingSyncLog;
use App\Models\AccountingEntityMapping;
use App\Models\AccountingEntry;
use App\Models\AccountingEntryLine;
use App\Models\AccountingJournal;
use App\Models\FecExport;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AccountingIntegrationService
{
    protected ?AccountingConnection $connection = null;

    /**
     * Synchroniser les factures vers le logiciel comptable
     */
    public function syncInvoices(AccountingConnection $connection, ?Carbon $since = null): array
    {
        $this->connection = $connection;
        $since = $since ?? $connection->last_sync_at ?? now()->subMonth();

        $log = AccountingSyncLog::create([
            'connection_id' => $connection->id,
            'sync_type' => 'invoices',
            'direction' => 'export',
            'status' => 'started',
            'started_at' => now(),
        ]);

        $invoices = Invoice::where('tenant_id', $connection->tenant_id)
            ->where('updated_at', '>=', $since)
            ->get();

        $succeeded = 0;
        $failed = 0;
        $errors = [];

        foreach ($invoices as $invoice) {
            try {
                $result = $this->exportInvoice($invoice);
                if ($result['success']) {
                    $succeeded++;
                    $this->recordMapping('invoice', $invoice->id, $result['external_id']);
                } else {
                    $failed++;
                    $errors[] = "Facture {$invoice->invoice_number}: {$result['error']}";
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Facture {$invoice->invoice_number}: {$e->getMessage()}";
            }
        }

        $log->update([
            'status' => $failed > 0 ? 'partial' : 'completed',
            'records_processed' => $invoices->count(),
            'records_succeeded' => $succeeded,
            'records_failed' => $failed,
            'errors' => $errors,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($log->started_at),
        ]);

        $connection->update(['last_sync_at' => now()]);

        return [
            'total' => $invoices->count(),
            'succeeded' => $succeeded,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Exporter une facture vers le provider
     */
    protected function exportInvoice(Invoice $invoice): array
    {
        $provider = $this->connection->provider->slug;

        return match ($provider) {
            'quickbooks' => $this->exportToQuickBooks($invoice),
            'sage' => $this->exportToSage($invoice),
            'xero' => $this->exportToXero($invoice),
            default => ['success' => false, 'error' => 'Provider non supporté'],
        };
    }

    /**
     * Export vers QuickBooks
     */
    protected function exportToQuickBooks(Invoice $invoice): array
    {
        // Simulation - en production, utiliser l'API QuickBooks
        $data = [
            'Line' => [
                [
                    'Amount' => $invoice->total,
                    'Description' => "Facture {$invoice->invoice_number}",
                    'DetailType' => 'SalesItemLineDetail',
                    'SalesItemLineDetail' => [
                        'ItemRef' => ['value' => '1'],
                        'Qty' => 1,
                        'UnitPrice' => $invoice->total,
                    ],
                ],
            ],
            'CustomerRef' => [
                'value' => $this->getExternalCustomerId($invoice->customer_id) ?? $invoice->customer_id,
            ],
            'TxnDate' => $invoice->invoice_date->format('Y-m-d'),
            'DueDate' => $invoice->due_date->format('Y-m-d'),
        ];

        // Appel API QuickBooks
        // $response = Http::withToken($this->connection->access_token)->post(...);

        return [
            'success' => true,
            'external_id' => 'QB-' . $invoice->id,
        ];
    }

    /**
     * Export vers Sage
     */
    protected function exportToSage(Invoice $invoice): array
    {
        // Créer une écriture comptable
        $journal = AccountingJournal::firstOrCreate(
            ['tenant_id' => $this->connection->tenant_id, 'code' => 'VT'],
            ['name' => 'Journal des Ventes', 'type' => 'sales']
        );

        $entry = AccountingEntry::create([
            'tenant_id' => $this->connection->tenant_id,
            'journal_id' => $journal->id,
            'entry_number' => $invoice->invoice_number,
            'entry_date' => $invoice->invoice_date,
            'accounting_date' => $invoice->invoice_date,
            'document_type' => 'invoice',
            'document_id' => $invoice->id,
            'document_reference' => $invoice->invoice_number,
            'description' => "Facture client {$invoice->customer->full_name}",
        ]);

        // Ligne client (Débit)
        AccountingEntryLine::create([
            'entry_id' => $entry->id,
            'account_code' => '411000', // Clients
            'account_label' => 'Clients',
            'debit' => $invoice->total,
            'credit' => 0,
            'partner_reference' => $invoice->customer_id,
            'label' => "Facture {$invoice->invoice_number}",
        ]);

        // Ligne TVA (Crédit)
        if ($invoice->tax_amount > 0) {
            AccountingEntryLine::create([
                'entry_id' => $entry->id,
                'account_code' => '445710', // TVA collectée
                'account_label' => 'TVA collectée',
                'debit' => 0,
                'credit' => $invoice->tax_amount,
                'tax_code' => 'TVA20',
                'tax_amount' => $invoice->tax_amount,
            ]);
        }

        // Ligne produit (Crédit)
        AccountingEntryLine::create([
            'entry_id' => $entry->id,
            'account_code' => '706000', // Prestations de services
            'account_label' => 'Prestations de services',
            'debit' => 0,
            'credit' => $invoice->subtotal,
            'label' => "Location box",
        ]);

        return [
            'success' => true,
            'external_id' => $entry->id,
        ];
    }

    /**
     * Export vers Xero
     */
    protected function exportToXero(Invoice $invoice): array
    {
        // Simulation
        return [
            'success' => true,
            'external_id' => 'XERO-' . $invoice->id,
        ];
    }

    /**
     * Synchroniser les paiements
     */
    public function syncPayments(AccountingConnection $connection, ?Carbon $since = null): array
    {
        $this->connection = $connection;
        $since = $since ?? $connection->last_sync_at ?? now()->subMonth();

        $payments = Payment::whereHas('invoice', fn($q) => $q->where('tenant_id', $connection->tenant_id))
            ->where('updated_at', '>=', $since)
            ->get();

        $succeeded = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            try {
                $this->createPaymentEntry($payment);
                $succeeded++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return ['succeeded' => $succeeded, 'failed' => $failed];
    }

    /**
     * Créer une écriture de paiement
     */
    protected function createPaymentEntry(Payment $payment): AccountingEntry
    {
        $journal = AccountingJournal::firstOrCreate(
            ['tenant_id' => $this->connection->tenant_id, 'code' => 'BQ'],
            ['name' => 'Journal de Banque', 'type' => 'bank']
        );

        $entry = AccountingEntry::create([
            'tenant_id' => $this->connection->tenant_id,
            'journal_id' => $journal->id,
            'entry_number' => 'PAY-' . $payment->id,
            'entry_date' => $payment->payment_date,
            'accounting_date' => $payment->payment_date,
            'document_type' => 'payment',
            'document_id' => $payment->id,
            'description' => "Règlement {$payment->invoice->invoice_number}",
        ]);

        // Débit Banque
        AccountingEntryLine::create([
            'entry_id' => $entry->id,
            'account_code' => '512000',
            'account_label' => 'Banque',
            'debit' => $payment->amount,
            'credit' => 0,
        ]);

        // Crédit Client
        AccountingEntryLine::create([
            'entry_id' => $entry->id,
            'account_code' => '411000',
            'account_label' => 'Clients',
            'debit' => 0,
            'credit' => $payment->amount,
            'partner_reference' => $payment->invoice->customer_id,
        ]);

        return $entry;
    }

    /**
     * Générer le Fichier des Écritures Comptables (FEC)
     */
    public function generateFEC(int $tenantId, int $fiscalYear): FecExport
    {
        $startDate = Carbon::createFromDate($fiscalYear, 1, 1);
        $endDate = Carbon::createFromDate($fiscalYear, 12, 31);

        $export = FecExport::create([
            'tenant_id' => $tenantId,
            'fiscal_year' => $fiscalYear,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'status' => 'generating',
        ]);

        try {
            $entries = AccountingEntry::where('tenant_id', $tenantId)
                ->whereBetween('accounting_date', [$startDate, $endDate])
                ->with('lines', 'journal')
                ->orderBy('accounting_date')
                ->orderBy('entry_number')
                ->get();

            $lines = [];
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($entries as $entry) {
                foreach ($entry->lines as $line) {
                    $lines[] = [
                        'JournalCode' => $entry->journal->code,
                        'JournalLib' => $entry->journal->name,
                        'EcritureNum' => $entry->entry_number,
                        'EcritureDate' => $entry->entry_date->format('Ymd'),
                        'CompteNum' => $line->account_code,
                        'CompteLib' => $line->account_label,
                        'CompAuxNum' => $line->partner_reference ?? '',
                        'CompAuxLib' => '',
                        'PieceRef' => $entry->document_reference ?? '',
                        'PieceDate' => $entry->entry_date->format('Ymd'),
                        'EcritureLib' => $line->label ?? $entry->description,
                        'Debit' => number_format($line->debit, 2, ',', ''),
                        'Credit' => number_format($line->credit, 2, ',', ''),
                        'EcritureLet' => '',
                        'DateLet' => '',
                        'ValidDate' => $entry->validated_at?->format('Ymd') ?? '',
                        'Montantdevise' => '',
                        'Idevise' => '',
                    ];

                    $totalDebit += $line->debit;
                    $totalCredit += $line->credit;
                }
            }

            // Générer le fichier FEC
            $fileName = "FEC{$tenantId}{$fiscalYear}.txt";
            $content = $this->formatFEC($lines);

            $path = "fec/{$tenantId}/{$fileName}";
            Storage::disk('local')->put($path, $content);

            $export->update([
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => strlen($content),
                'checksum' => hash('sha256', $content),
                'entries_count' => count($lines),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'ready',
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            $export->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $export;
    }

    /**
     * Formater le FEC selon les normes françaises
     */
    protected function formatFEC(array $lines): string
    {
        $header = implode("\t", [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
            'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise'
        ]);

        $rows = [$header];

        foreach ($lines as $line) {
            $rows[] = implode("\t", array_values($line));
        }

        return implode("\n", $rows);
    }

    /**
     * Enregistrer un mapping d'entité
     */
    protected function recordMapping(string $entityType, int $localId, string $externalId): void
    {
        AccountingEntityMapping::updateOrCreate(
            [
                'connection_id' => $this->connection->id,
                'entity_type' => $entityType,
                'local_id' => $localId,
            ],
            [
                'external_id' => $externalId,
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]
        );
    }

    /**
     * Obtenir l'ID externe d'un client
     */
    protected function getExternalCustomerId(int $customerId): ?string
    {
        $mapping = AccountingEntityMapping::where('connection_id', $this->connection->id)
            ->where('entity_type', 'customer')
            ->where('local_id', $customerId)
            ->first();

        return $mapping?->external_id;
    }

    /**
     * Vérifier la connexion OAuth
     */
    public function checkConnection(AccountingConnection $connection): bool
    {
        if (!$connection->access_token) {
            return false;
        }

        if ($connection->token_expires_at && $connection->token_expires_at->isPast()) {
            return $this->refreshToken($connection);
        }

        return true;
    }

    /**
     * Rafraîchir le token OAuth
     */
    protected function refreshToken(AccountingConnection $connection): bool
    {
        // TODO: Implémenter le refresh token selon le provider
        return false;
    }
}
