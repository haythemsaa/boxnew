<?php

namespace App\Services;

use App\Models\FecExport;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service pour generer le Fichier des Ecritures Comptables (FEC)
 * Conforme a l'article A.47 A-1 du Livre des procedures fiscales francais
 *
 * Format: Fichier texte a structure tabulee (separateur |)
 * Encodage: ISO-8859-15 ou UTF-8
 * Nom: SirenFECAAAAMMJJ.txt
 */
class FecExportService
{
    // Colonnes obligatoires du FEC (18 colonnes)
    private const FEC_COLUMNS = [
        'JournalCode',
        'JournalLib',
        'EcritureNum',
        'EcritureDate',
        'CompteNum',
        'CompteLib',
        'CompAuxNum',
        'CompAuxLib',
        'PieceRef',
        'PieceDate',
        'EcritureLib',
        'Debit',
        'Credit',
        'EcritureLet',
        'DateLet',
        'ValidDate',
        'Montantdevise',
        'Idevise',
    ];

    // Comptes comptables PCG
    private const ACCOUNTS = [
        'revenue'           => ['code' => '706000', 'label' => 'Prestations de services'],
        'revenue_storage'   => ['code' => '706100', 'label' => 'Location de boxes de stockage'],
        'revenue_insurance' => ['code' => '706200', 'label' => 'Assurances locataires'],
        'vat_collected'     => ['code' => '445710', 'label' => 'TVA collectee'],
        'vat_20'            => ['code' => '445711', 'label' => 'TVA collectee 20%'],
        'customer'          => ['code' => '411000', 'label' => 'Clients'],
        'bank'              => ['code' => '512000', 'label' => 'Banque'],
        'cash'              => ['code' => '531000', 'label' => 'Caisse'],
        'discount'          => ['code' => '665000', 'label' => 'Escomptes accordes'],
    ];

    // Journaux comptables
    private const JOURNALS = [
        'VE' => 'Ventes',
        'BQ' => 'Banque',
        'CA' => 'Caisse',
        'OD' => 'Operations diverses',
    ];

    protected int $entryNumber = 0;
    protected float $totalDebit = 0;
    protected float $totalCredit = 0;

    /**
     * Generer un export FEC pour une periode donnee
     */
    public function generate(Tenant $tenant, int $fiscalYear, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): FecExport
    {
        $periodStart = $periodStart ?? Carbon::createFromDate($fiscalYear, 1, 1)->startOfDay();
        $periodEnd = $periodEnd ?? Carbon::createFromDate($fiscalYear, 12, 31)->endOfDay();

        $fecExport = FecExport::create([
            'tenant_id' => $tenant->id,
            'fiscal_year' => $fiscalYear,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'generating',
            'generated_by' => auth()->id(),
        ]);

        try {
            $content = $this->generateContent($tenant, $periodStart, $periodEnd);

            $siren = $this->getSiren($tenant);
            $fileName = sprintf('%sFEC%s.txt', $siren, $periodEnd->format('Ymd'));
            $filePath = "fec/{$tenant->id}/{$fileName}";

            Storage::put($filePath, $content);
            $checksum = md5($content);

            $fecExport->update([
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($content),
                'checksum' => $checksum,
                'entries_count' => $this->entryNumber,
                'total_debit' => $this->totalDebit,
                'total_credit' => $this->totalCredit,
                'status' => 'ready',
                'generated_at' => now(),
            ]);

            Log::info("FEC generated successfully", [
                'tenant_id' => $tenant->id,
                'fiscal_year' => $fiscalYear,
                'entries' => $this->entryNumber,
            ]);

        } catch (\Exception $e) {
            $fecExport->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("FEC generation failed", [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $fecExport->fresh();
    }

    /**
     * Generer le contenu du fichier FEC
     */
    protected function generateContent(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): string
    {
        $this->entryNumber = 0;
        $this->totalDebit = 0;
        $this->totalCredit = 0;

        $lines = [];
        $lines[] = implode('|', self::FEC_COLUMNS);

        // Factures de la periode
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('invoice_date', [$periodStart, $periodEnd])
            ->whereIn('status', ['sent', 'paid', 'overdue'])
            ->with(['customer', 'payments'])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            $this->entryNumber++;
            $lines = array_merge($lines, $this->generateSalesEntry($invoice));
        }

        // Paiements de la periode
        $payments = Payment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->with(['invoice.customer'])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $this->entryNumber++;
            $lines = array_merge($lines, $this->generatePaymentEntry($payment));
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generer les lignes pour une facture
     */
    protected function generateSalesEntry(Invoice $invoice): array
    {
        $lines = [];
        $entryDate = Carbon::parse($invoice->invoice_date)->format('Ymd');
        $customerCode = '411' . str_pad($invoice->customer_id, 5, '0', STR_PAD_LEFT);
        $customerLabel = $this->sanitize($invoice->customer->full_name ?? 'Client');

        // Debit client (TTC)
        $ttc = $invoice->total;
        $this->totalDebit += $ttc;

        $lines[] = $this->formatLine([
            'VE',
            self::JOURNALS['VE'],
            $this->formatEntryNumber(),
            $entryDate,
            $customerCode,
            $customerLabel,
            '',
            '',
            $invoice->invoice_number,
            $entryDate,
            $this->sanitize("Facture {$invoice->invoice_number}"),
            $this->formatAmount($ttc),
            '0,00',
            '',
            '',
            $entryDate,
            '',
            '',
        ]);

        // Credit produit (HT)
        $ht = $invoice->subtotal;
        $this->totalCredit += $ht;

        $lines[] = $this->formatLine([
            'VE',
            self::JOURNALS['VE'],
            $this->formatEntryNumber(),
            $entryDate,
            self::ACCOUNTS['revenue_storage']['code'],
            self::ACCOUNTS['revenue_storage']['label'],
            '',
            '',
            $invoice->invoice_number,
            $entryDate,
            $this->sanitize("Location box - {$invoice->invoice_number}"),
            '0,00',
            $this->formatAmount($ht),
            '',
            '',
            $entryDate,
            '',
            '',
        ]);

        // Credit TVA
        if ($invoice->tax_amount > 0) {
            $tva = $invoice->tax_amount;
            $this->totalCredit += $tva;

            $lines[] = $this->formatLine([
                'VE',
                self::JOURNALS['VE'],
                $this->formatEntryNumber(),
                $entryDate,
                self::ACCOUNTS['vat_20']['code'],
                self::ACCOUNTS['vat_20']['label'],
                '',
                '',
                $invoice->invoice_number,
                $entryDate,
                $this->sanitize("TVA 20% - {$invoice->invoice_number}"),
                '0,00',
                $this->formatAmount($tva),
                '',
                '',
                $entryDate,
                '',
                '',
            ]);
        }

        return $lines;
    }

    /**
     * Generer les lignes pour un paiement
     */
    protected function generatePaymentEntry(Payment $payment): array
    {
        $lines = [];
        $entryDate = Carbon::parse($payment->paid_at)->format('Ymd');

        $journalCode = $payment->method === 'cash' ? 'CA' : 'BQ';
        $accountCode = $payment->method === 'cash'
            ? self::ACCOUNTS['cash']['code']
            : self::ACCOUNTS['bank']['code'];
        $accountLabel = $payment->method === 'cash'
            ? self::ACCOUNTS['cash']['label']
            : self::ACCOUNTS['bank']['label'];

        $customerCode = '411' . str_pad($payment->invoice->customer_id ?? 0, 5, '0', STR_PAD_LEFT);
        $customerLabel = $this->sanitize($payment->invoice->customer->full_name ?? 'Client');

        // Debit banque/caisse
        $amount = $payment->amount;
        $this->totalDebit += $amount;

        $lines[] = $this->formatLine([
            $journalCode,
            self::JOURNALS[$journalCode],
            $this->formatEntryNumber(),
            $entryDate,
            $accountCode,
            $accountLabel,
            '',
            '',
            $payment->payment_number ?? $payment->id,
            $entryDate,
            $this->sanitize("Reglement " . ($payment->invoice->invoice_number ?? '')),
            $this->formatAmount($amount),
            '0,00',
            '',
            '',
            $entryDate,
            '',
            '',
        ]);

        // Credit client
        $this->totalCredit += $amount;

        $lines[] = $this->formatLine([
            $journalCode,
            self::JOURNALS[$journalCode],
            $this->formatEntryNumber(),
            $entryDate,
            $customerCode,
            $customerLabel,
            '',
            '',
            $payment->payment_number ?? $payment->id,
            $entryDate,
            $this->sanitize("Reglement " . ($payment->invoice->invoice_number ?? '')),
            '0,00',
            $this->formatAmount($amount),
            '',
            '',
            $entryDate,
            '',
            '',
        ]);

        return $lines;
    }

    protected function formatLine(array $values): string
    {
        return implode('|', $values);
    }

    protected function formatEntryNumber(): string
    {
        return str_pad($this->entryNumber, 10, '0', STR_PAD_LEFT);
    }

    protected function formatAmount(float $amount): string
    {
        return number_format(abs($amount), 2, ',', '');
    }

    protected function sanitize(?string $value): string
    {
        if (!$value) return '';
        $value = str_replace('|', ' ', $value);
        $value = preg_replace('/[\r\n]+/', ' ', $value);
        return trim($value);
    }

    protected function getSiren(Tenant $tenant): string
    {
        $siret = preg_replace('/[^0-9]/', '', $tenant->siret ?? '');
        if (strlen($siret) >= 9) {
            return substr($siret, 0, 9);
        }
        return '000000000';
    }

    public function download(FecExport $fecExport): array
    {
        if (!$fecExport->isReady()) {
            throw new \Exception("L'export FEC n'est pas pret au telechargement");
        }

        return [
            'content' => Storage::get($fecExport->file_path),
            'filename' => $fecExport->file_name,
            'mime_type' => 'text/plain',
        ];
    }

    public function validate(FecExport $fecExport): array
    {
        $errors = [];
        $warnings = [];

        if (abs($fecExport->total_debit - $fecExport->total_credit) > 0.01) {
            $errors[] = sprintf(
                "Desequilibre comptable: Debit = %.2f, Credit = %.2f",
                $fecExport->total_debit,
                $fecExport->total_credit
            );
        }

        if ($fecExport->entries_count === 0) {
            $warnings[] = "Le fichier FEC ne contient aucune ecriture";
        }

        if ($fecExport->file_path && Storage::exists($fecExport->file_path)) {
            $content = Storage::get($fecExport->file_path);
            if (md5($content) !== $fecExport->checksum) {
                $errors[] = "Le fichier a ete modifie (checksum invalide)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
