<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Service d'export Excel avance avec mise en forme professionnelle
 *
 * Fonctionnalites:
 * - Export multi-onglets (Sites, Boxes, Clients, Contrats, Factures)
 * - Mise en forme professionnelle avec couleurs et styles
 * - Formules de calcul integrees
 * - Filtres automatiques
 * - Support de l'export vers XLSX et PDF
 */
class SpreadsheetExportService
{
    protected Spreadsheet $spreadsheet;
    protected Tenant $tenant;

    // Couleurs de la charte BoxiBox
    protected array $colors = [
        'primary' => '3B82F6',      // Bleu
        'secondary' => '1E40AF',     // Bleu fonce
        'success' => '10B981',       // Vert
        'warning' => 'F59E0B',       // Orange
        'danger' => 'EF4444',        // Rouge
        'header' => '1E3A8A',        // Bleu header
        'headerText' => 'FFFFFF',    // Blanc
        'evenRow' => 'F3F4F6',       // Gris clair
        'oddRow' => 'FFFFFF',        // Blanc
    ];

    /**
     * Exporter le rapport complet multi-onglets
     */
    public function exportFullReport(Tenant $tenant, array $options = []): string
    {
        $this->tenant = $tenant;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);

        // Onglet Resume
        $this->createSummarySheet();

        // Onglets de donnees
        if ($options['include_sites'] ?? true) {
            $this->createSitesSheet();
        }

        if ($options['include_boxes'] ?? true) {
            $this->createBoxesSheet();
        }

        if ($options['include_customers'] ?? true) {
            $this->createCustomersSheet();
        }

        if ($options['include_contracts'] ?? true) {
            $this->createContractsSheet();
        }

        if ($options['include_invoices'] ?? true) {
            $this->createInvoicesSheet();
        }

        if ($options['include_payments'] ?? true) {
            $this->createPaymentsSheet();
        }

        // Revenir au premier onglet
        $this->spreadsheet->setActiveSheetIndex(0);

        // Generer le fichier
        $filename = 'BoxiBox_Export_' . $tenant->slug . '_' . date('Y-m-d_His') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Creer l'onglet Resume
     */
    protected function createSummarySheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Resume');

        // En-tete
        $sheet->setCellValue('A1', 'RAPPORT BOXIBOX');
        $sheet->setCellValue('A2', $this->tenant->name);
        $sheet->setCellValue('A3', 'Genere le ' . now()->format('d/m/Y a H:i'));

        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $this->colors['secondary']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Statistiques generales
        $row = 5;
        $sheet->setCellValue('A' . $row, 'STATISTIQUES GENERALES');
        $this->applyHeaderStyle($sheet, 'A' . $row . ':B' . $row);
        $row++;

        $stats = $this->calculateStats();

        foreach ($stats as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        // Auto-width
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    /**
     * Calculer les statistiques generales
     */
    protected function calculateStats(): array
    {
        $tenantId = $this->tenant->id;

        $totalSites = Site::where('tenant_id', $tenantId)->count();
        $totalBoxes = Box::where('tenant_id', $tenantId)->count();
        $availableBoxes = Box::where('tenant_id', $tenantId)->where('status', 'available')->count();
        $occupiedBoxes = Box::where('tenant_id', $tenantId)->where('status', 'occupied')->count();
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();
        $activeContracts = Contract::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $monthlyRevenue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total');
        $yearlyRevenue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereYear('invoice_date', now()->year)
            ->sum('total');
        $overdueAmount = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->sum('total');
        $occupancyRate = $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0;

        return [
            'Sites' => $totalSites,
            'Boxes total' => $totalBoxes,
            'Boxes disponibles' => $availableBoxes,
            'Boxes occupes' => $occupiedBoxes,
            'Taux d\'occupation' => $occupancyRate . '%',
            'Clients' => $totalCustomers,
            'Contrats actifs' => $activeContracts,
            'CA du mois' => number_format($monthlyRevenue, 2, ',', ' ') . ' EUR',
            'CA de l\'annee' => number_format($yearlyRevenue, 2, ',', ' ') . ' EUR',
            'Impayes' => number_format($overdueAmount, 2, ',', ' ') . ' EUR',
        ];
    }

    /**
     * Creer l'onglet Sites
     */
    protected function createSitesSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Sites');

        $headers = ['ID', 'Code', 'Nom', 'Adresse', 'Code postal', 'Ville', 'Telephone', 'Email', 'Boxes', 'Occupes', 'Taux occupation', 'CA mensuel', 'Statut'];
        $this->writeHeaders($sheet, $headers);

        $sites = Site::where('tenant_id', $this->tenant->id)
            ->withCount(['boxes', 'boxes as occupied_count' => function ($q) {
                $q->where('status', 'occupied');
            }])
            ->get();

        $row = 2;
        foreach ($sites as $site) {
            $occupancyRate = $site->boxes_count > 0
                ? round(($site->occupied_count / $site->boxes_count) * 100, 1)
                : 0;

            $monthlyRevenue = Invoice::where('tenant_id', $this->tenant->id)
                ->whereHas('contract', fn($q) => $q->whereHas('box', fn($b) => $b->where('site_id', $site->id)))
                ->where('status', 'paid')
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->sum('total');

            $data = [
                $site->id,
                $site->code,
                $site->name,
                $site->address ?? '',
                $site->postal_code ?? '',
                $site->city ?? '',
                $site->phone ?? '',
                $site->email ?? '',
                $site->boxes_count,
                $site->occupied_count,
                $occupancyRate . '%',
                number_format($monthlyRevenue, 2, ',', ' '),
                $site->is_active ? 'Actif' : 'Inactif',
            ];

            $this->writeRow($sheet, $data, $row);
            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));
    }

    /**
     * Creer l'onglet Boxes
     */
    protected function createBoxesSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Boxes');

        $headers = ['ID', 'Site', 'Numero', 'Nom', 'Etage', 'Type', 'Surface m2', 'Volume m3', 'Prix mensuel', 'Statut', 'Client actuel', 'Contrat actif'];
        $this->writeHeaders($sheet, $headers);

        $boxes = Box::where('tenant_id', $this->tenant->id)
            ->with(['site', 'currentContract.customer'])
            ->orderBy('site_id')
            ->orderBy('number')
            ->get();

        $row = 2;
        foreach ($boxes as $box) {
            $currentContract = $box->currentContract;
            $customer = $currentContract?->customer;

            $data = [
                $box->id,
                $box->site?->name ?? 'N/A',
                $box->number,
                $box->name ?? '',
                $box->floor ?? '',
                $box->type ?? 'standard',
                $box->size_m2 ?? '',
                $box->volume ?? '',
                $box->current_price ?? $box->base_price ?? 0,
                $this->translateBoxStatus($box->status),
                $customer ? $customer->full_name : '',
                $currentContract?->contract_number ?? '',
            ];

            $this->writeRow($sheet, $data, $row);

            // Colorer selon le statut
            $statusColor = match ($box->status) {
                'available' => $this->colors['success'],
                'occupied' => $this->colors['primary'],
                'reserved' => $this->colors['warning'],
                'maintenance' => $this->colors['danger'],
                default => null,
            };

            if ($statusColor) {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $statusColor],
                    ],
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                ]);
            }

            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));
    }

    /**
     * Creer l'onglet Clients
     */
    protected function createCustomersSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Clients');

        $headers = ['ID', 'Type', 'Civilite', 'Prenom', 'Nom', 'Societe', 'Email', 'Telephone', 'Adresse', 'Code postal', 'Ville', 'Contrats actifs', 'CA total', 'Solde du', 'Statut', 'Cree le'];
        $this->writeHeaders($sheet, $headers);

        $customers = Customer::where('tenant_id', $this->tenant->id)
            ->withCount(['contracts as active_contracts_count' => fn($q) => $q->where('status', 'active')])
            ->orderBy('last_name')
            ->get();

        $row = 2;
        foreach ($customers as $customer) {
            $data = [
                $customer->id,
                $customer->type === 'individual' ? 'Particulier' : 'Entreprise',
                $customer->civility ?? '',
                $customer->first_name,
                $customer->last_name,
                $customer->company_name ?? '',
                $customer->email,
                $customer->phone ?? $customer->mobile ?? '',
                $customer->address ?? '',
                $customer->postal_code ?? '',
                $customer->city ?? '',
                $customer->active_contracts_count,
                $customer->total_revenue ?? 0,
                $customer->outstanding_balance ?? 0,
                $this->translateStatus($customer->status),
                $customer->created_at->format('d/m/Y'),
            ];

            $this->writeRow($sheet, $data, $row);

            // Format monetaire
            $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');

            // Colorer le solde du si > 0
            if (($customer->outstanding_balance ?? 0) > 0) {
                $sheet->getStyle('N' . $row)->getFont()->getColor()->setRGB($this->colors['danger']);
            }

            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));
    }

    /**
     * Creer l'onglet Contrats
     */
    protected function createContractsSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Contrats');

        $headers = ['N Contrat', 'Client', 'Site', 'Box', 'Type', 'Debut', 'Fin', 'Loyer mensuel', 'Depot de garantie', 'Jour facturation', 'Statut', 'Total facture'];
        $this->writeHeaders($sheet, $headers);

        $contracts = Contract::where('tenant_id', $this->tenant->id)
            ->with(['customer', 'box.site'])
            ->orderBy('start_date', 'desc')
            ->get();

        $row = 2;
        foreach ($contracts as $contract) {
            $data = [
                $contract->contract_number,
                $contract->customer?->full_name ?? 'N/A',
                $contract->box?->site?->name ?? 'N/A',
                $contract->box?->number ?? 'N/A',
                $this->translateContractType($contract->type),
                $contract->start_date->format('d/m/Y'),
                $contract->end_date ? $contract->end_date->format('d/m/Y') : 'Indeterminee',
                $contract->monthly_rate ?? $contract->monthly_price ?? 0,
                $contract->deposit_amount ?? 0,
                $contract->billing_day ?? 1,
                $this->translateContractStatus($contract->status),
                $contract->invoices()->sum('total'),
            ];

            $this->writeRow($sheet, $data, $row);

            // Format monetaire
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');

            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));
    }

    /**
     * Creer l'onglet Factures
     */
    protected function createInvoicesSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Factures');

        $headers = ['N Facture', 'Client', 'Contrat', 'Type', 'Date facture', 'Date echeance', 'Montant HT', 'TVA', 'Montant TTC', 'Paye', 'Reste a payer', 'Statut', 'Relances'];
        $this->writeHeaders($sheet, $headers);

        $invoices = Invoice::where('tenant_id', $this->tenant->id)
            ->with(['customer', 'contract'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        $row = 2;
        foreach ($invoices as $invoice) {
            $data = [
                $invoice->invoice_number,
                $invoice->customer?->full_name ?? 'N/A',
                $invoice->contract?->contract_number ?? '',
                $this->translateInvoiceType($invoice->type),
                $invoice->invoice_date->format('d/m/Y'),
                $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '',
                $invoice->subtotal ?? 0,
                $invoice->tax_amount ?? 0,
                $invoice->total ?? 0,
                $invoice->paid_amount ?? 0,
                ($invoice->total ?? 0) - ($invoice->paid_amount ?? 0),
                $this->translateInvoiceStatus($invoice->status),
                $invoice->reminder_count ?? 0,
            ];

            $this->writeRow($sheet, $data, $row);

            // Format monetaire
            for ($col = 7; $col <= 11; $col++) {
                $colLetter = chr(64 + $col);
                $sheet->getStyle($colLetter . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            }

            // Colorer selon le statut
            $statusColor = match ($invoice->status) {
                'paid' => $this->colors['success'],
                'overdue' => $this->colors['danger'],
                'partial' => $this->colors['warning'],
                default => null,
            };

            if ($statusColor) {
                $sheet->getStyle('L' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $statusColor],
                    ],
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                ]);
            }

            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));

        // Ajouter des totaux
        $sheet->setCellValue('F' . $row, 'TOTAUX:');
        $sheet->setCellValue('G' . $row, '=SUM(G2:G' . ($row - 1) . ')');
        $sheet->setCellValue('H' . $row, '=SUM(H2:H' . ($row - 1) . ')');
        $sheet->setCellValue('I' . $row, '=SUM(I2:I' . ($row - 1) . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J2:J' . ($row - 1) . ')');
        $sheet->setCellValue('K' . $row, '=SUM(K2:K' . ($row - 1) . ')');

        $sheet->getStyle('F' . $row . ':K' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->colors['evenRow']],
            ],
        ]);

        for ($col = 7; $col <= 11; $col++) {
            $colLetter = chr(64 + $col);
            $sheet->getStyle($colLetter . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
        }
    }

    /**
     * Creer l'onglet Paiements
     */
    protected function createPaymentsSheet(): void
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle('Paiements');

        $headers = ['ID', 'Date', 'Client', 'Facture', 'Methode', 'Montant', 'Reference', 'Statut'];
        $this->writeHeaders($sheet, $headers);

        $payments = Payment::where('tenant_id', $this->tenant->id)
            ->with(['customer', 'invoice'])
            ->orderBy('paid_at', 'desc')
            ->get();

        $row = 2;
        foreach ($payments as $payment) {
            $data = [
                $payment->id,
                $payment->paid_at ? Carbon::parse($payment->paid_at)->format('d/m/Y') : '',
                $payment->customer?->full_name ?? ($payment->invoice?->customer?->full_name ?? 'N/A'),
                $payment->invoice?->invoice_number ?? '',
                $this->translatePaymentMethod($payment->payment_method ?? $payment->method ?? ''),
                $payment->amount ?? 0,
                $payment->reference ?? '',
                $this->translatePaymentStatus($payment->status ?? 'completed'),
            ];

            $this->writeRow($sheet, $data, $row);

            // Format monetaire
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');

            $row++;
        }

        $this->applyTableStyle($sheet, count($headers), $row - 1);
        $this->autoSizeColumns($sheet, count($headers));

        // Total
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, '=SUM(F2:F' . ($row - 1) . ')');
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
    }

    /**
     * Ecrire les en-tetes
     */
    protected function writeHeaders(Worksheet $sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $lastCol = chr(64 + count($headers));
        $this->applyHeaderStyle($sheet, 'A1:' . $lastCol . '1');

        // Activer les filtres
        $sheet->setAutoFilter('A1:' . $lastCol . '1');
    }

    /**
     * Ecrire une ligne de donnees
     */
    protected function writeRow(Worksheet $sheet, array $data, int $row): void
    {
        $col = 'A';
        foreach ($data as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
    }

    /**
     * Appliquer le style d'en-tete
     */
    protected function applyHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $this->colors['headerText']],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->colors['header']],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    /**
     * Appliquer le style tableau
     */
    protected function applyTableStyle(Worksheet $sheet, int $colCount, int $lastRow): void
    {
        $lastCol = chr(64 + $colCount);

        // Bordures
        $sheet->getStyle('A1:' . $lastCol . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Lignes alternees
        for ($row = 2; $row <= $lastRow; $row++) {
            $bgColor = $row % 2 === 0 ? $this->colors['evenRow'] : $this->colors['oddRow'];
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
            ]);
        }

        // Figer la premiere ligne
        $sheet->freezePane('A2');
    }

    /**
     * Auto-dimensionner les colonnes
     */
    protected function autoSizeColumns(Worksheet $sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $col = chr(64 + $i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // Traductions
    protected function translateStatus(string $status): string
    {
        return match ($status) {
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'suspended' => 'Suspendu',
            'blocked' => 'Bloque',
            default => $status,
        };
    }

    protected function translateBoxStatus(string $status): string
    {
        return match ($status) {
            'available' => 'Disponible',
            'occupied' => 'Occupe',
            'reserved' => 'Reserve',
            'maintenance' => 'Maintenance',
            default => $status,
        };
    }

    protected function translateInvoiceType(string $type): string
    {
        return match ($type) {
            'rental' => 'Location',
            'invoice' => 'Facture',
            'deposit' => 'Caution',
            'fees' => 'Frais',
            'credit_note' => 'Avoir',
            'proforma' => 'Proforma',
            default => $type,
        };
    }

    protected function translateInvoiceStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyee',
            'paid' => 'Payee',
            'partial' => 'Partiel',
            'overdue' => 'En retard',
            'cancelled' => 'Annulee',
            default => $status,
        };
    }

    protected function translateContractType(string $type): string
    {
        return match ($type) {
            'monthly' => 'Mensuel',
            'fixed_term' => 'Duree fixe',
            'temporary' => 'Temporaire',
            default => $type,
        };
    }

    protected function translateContractStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'active' => 'Actif',
            'expired' => 'Expire',
            'terminated' => 'Resilie',
            'suspended' => 'Suspendu',
            default => $status,
        };
    }

    protected function translatePaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Especes',
            'check' => 'Cheque',
            'card' => 'Carte',
            'bank_transfer' => 'Virement',
            'transfer' => 'Virement',
            'sepa' => 'SEPA',
            'stripe' => 'Stripe',
            default => $method,
        };
    }

    protected function translatePaymentStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'completed' => 'Complete',
            'failed' => 'Echoue',
            'cancelled' => 'Annule',
            'refunded' => 'Rembourse',
            default => $status,
        };
    }

    /**
     * Export Rent Roll (etat des lieux locatifs)
     */
    public function exportRentRoll(Tenant $tenant, ?int $siteId = null): string
    {
        $this->tenant = $tenant;
        $this->spreadsheet = new Spreadsheet();

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Rent Roll');

        // En-tete
        $sheet->setCellValue('A1', 'RENT ROLL - ETAT DES LOCATIONS');
        $sheet->setCellValue('A2', $tenant->name);
        $sheet->setCellValue('A3', 'Au ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = [
            'Site', 'Box', 'Surface m2', 'Client', 'Type client', 'N Contrat',
            'Debut', 'Fin', 'Duree (mois)', 'Loyer mensuel', 'Annuel', 'Paye ce mois',
            'Solde du', 'Statut'
        ];

        $row = 5;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A5:N5');

        // Donnees
        $query = Contract::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with(['customer', 'box.site', 'invoices' => function ($q) {
                $q->whereMonth('invoice_date', now()->month)
                    ->whereYear('invoice_date', now()->year);
            }]);

        if ($siteId) {
            $query->whereHas('box', fn($q) => $q->where('site_id', $siteId));
        }

        $contracts = $query->orderBy('id')->get();

        $row = 6;
        $totalMonthly = 0;
        $totalAnnual = 0;
        $totalPaid = 0;
        $totalDue = 0;

        foreach ($contracts as $contract) {
            $monthlyRent = $contract->monthly_rate ?? $contract->monthly_price ?? 0;
            $annualRent = $monthlyRent * 12;
            $paidThisMonth = $contract->invoices->where('status', 'paid')->sum('total');
            $dueAmount = $contract->customer?->outstanding_balance ?? 0;
            $duration = $contract->start_date->diffInMonths(now());

            $data = [
                $contract->box?->site?->name ?? 'N/A',
                $contract->box?->number ?? 'N/A',
                $contract->box?->size_m2 ?? '',
                $contract->customer?->full_name ?? 'N/A',
                $contract->customer?->type === 'company' ? 'Entreprise' : 'Particulier',
                $contract->contract_number,
                $contract->start_date->format('d/m/Y'),
                $contract->end_date ? $contract->end_date->format('d/m/Y') : 'Indeterminee',
                $duration,
                $monthlyRent,
                $annualRent,
                $paidThisMonth,
                $dueAmount,
                $dueAmount > 0 ? 'Impaye' : 'A jour',
            ];

            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }

            // Format monetaire
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');
            $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');

            // Colorer si impaye
            if ($dueAmount > 0) {
                $sheet->getStyle('N' . $row)->getFont()->getColor()->setRGB($this->colors['danger']);
            }

            $totalMonthly += $monthlyRent;
            $totalAnnual += $annualRent;
            $totalPaid += $paidThisMonth;
            $totalDue += $dueAmount;

            $row++;
        }

        // Totaux
        $sheet->setCellValue('I' . $row, 'TOTAUX:');
        $sheet->setCellValue('J' . $row, $totalMonthly);
        $sheet->setCellValue('K' . $row, $totalAnnual);
        $sheet->setCellValue('L' . $row, $totalPaid);
        $sheet->setCellValue('M' . $row, $totalDue);

        $sheet->getStyle('I' . $row . ':N' . $row)->getFont()->setBold(true);
        $sheet->getStyle('J' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('#,##0.00\ "EUR"');

        $this->applyTableStyle($sheet, 14, $row);
        $this->autoSizeColumns($sheet, 14);

        // Generer
        $filename = 'RentRoll_' . $tenant->slug . '_' . date('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
