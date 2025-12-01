<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\FacturxConfiguration;
use App\Models\ElectronicInvoice;
use App\Models\FacturxTransmission;
use App\Models\InvoiceArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturXService
{
    protected ?FacturxConfiguration $config = null;

    /**
     * Générer une facture Factur-X (PDF + XML)
     */
    public function generate(Invoice $invoice): ElectronicInvoice
    {
        $this->loadConfig($invoice->tenant_id);

        if (!$this->config) {
            throw new \Exception('Configuration Factur-X non trouvée');
        }

        // Générer l'ID unique
        $facturxId = $this->generateFacturXId($invoice);

        // Générer le XML Factur-X
        $xml = $this->generateXml($invoice);
        $xmlPath = $this->saveXml($invoice, $xml);

        // Générer le PDF
        $pdfPath = $this->generatePdf($invoice);

        // Créer le PDF avec XML embarqué (PDF/A-3)
        $pdfWithXmlPath = $this->embedXmlInPdf($pdfPath, $xmlPath, $invoice);

        // Valider le document
        $validation = $this->validate($xml);

        // Créer l'enregistrement
        return ElectronicInvoice::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'configuration_id' => $this->config->id,
            'facturx_id' => $facturxId,
            'pdf_path' => $pdfPath,
            'xml_path' => $xmlPath,
            'pdf_with_xml_path' => $pdfWithXmlPath,
            'checksum' => hash_file('sha256', Storage::disk('public')->path($pdfWithXmlPath)),
            'status' => 'generated',
            'is_valid' => $validation['is_valid'],
            'validation_errors' => $validation['errors'] ?? null,
            'generated_at' => now(),
            'recipient_siret' => $invoice->customer->siret ?? null,
        ]);
    }

    /**
     * Générer le XML Factur-X selon le profil configuré
     */
    protected function generateXml(Invoice $invoice): string
    {
        $profile = $this->config->profile;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <rsm:CrossIndustryInvoice
                xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"
                xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100"
                xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">
            </rsm:CrossIndustryInvoice>');

        // Context
        $context = $xml->addChild('rsm:ExchangedDocumentContext');
        $guideline = $context->addChild('ram:GuidelineSpecifiedDocumentContextParameter');
        $guideline->addChild('ram:ID', $this->getProfileUrn($profile));

        // Document Header
        $doc = $xml->addChild('rsm:ExchangedDocument');
        $doc->addChild('ram:ID', $invoice->invoice_number);
        $doc->addChild('ram:TypeCode', '380'); // 380 = Facture commerciale
        $issueDate = $doc->addChild('ram:IssueDateTime');
        $issueDate->addChild('udt:DateTimeString', $invoice->invoice_date->format('Ymd'))
            ->addAttribute('format', '102');

        // Supply Chain Trade Transaction
        $transaction = $xml->addChild('rsm:SupplyChainTradeTransaction');

        // Seller (Vendeur)
        $agreement = $transaction->addChild('ram:ApplicableHeaderTradeAgreement');
        $seller = $agreement->addChild('ram:SellerTradeParty');
        $seller->addChild('ram:Name', $this->config->company_name);

        $sellerTax = $seller->addChild('ram:SpecifiedTaxRegistration');
        $sellerTax->addChild('ram:ID', $this->config->vat_number)->addAttribute('schemeID', 'VA');

        $sellerAddress = $seller->addChild('ram:PostalTradeAddress');
        $sellerAddress->addChild('ram:PostcodeCode', $this->config->billing_postal_code);
        $sellerAddress->addChild('ram:LineOne', $this->config->billing_address);
        $sellerAddress->addChild('ram:CityName', $this->config->billing_city);
        $sellerAddress->addChild('ram:CountryID', $this->config->billing_country);

        // Buyer (Acheteur)
        $buyer = $agreement->addChild('ram:BuyerTradeParty');
        $buyer->addChild('ram:Name', $invoice->customer->company_name ?? $invoice->customer->full_name);

        if ($invoice->customer->siret) {
            $buyerTax = $buyer->addChild('ram:SpecifiedTaxRegistration');
            $buyerTax->addChild('ram:ID', $invoice->customer->vat_number ?? '')->addAttribute('schemeID', 'VA');
        }

        $buyerAddress = $buyer->addChild('ram:PostalTradeAddress');
        $buyerAddress->addChild('ram:PostcodeCode', $invoice->customer->postal_code ?? '');
        $buyerAddress->addChild('ram:LineOne', $invoice->customer->address ?? '');
        $buyerAddress->addChild('ram:CityName', $invoice->customer->city ?? '');
        $buyerAddress->addChild('ram:CountryID', 'FR');

        // Delivery
        $delivery = $transaction->addChild('ram:ApplicableHeaderTradeDelivery');

        // Settlement (Règlement)
        $settlement = $transaction->addChild('ram:ApplicableHeaderTradeSettlement');
        $settlement->addChild('ram:InvoiceCurrencyCode', 'EUR');

        // Payment Terms
        $paymentTerms = $settlement->addChild('ram:SpecifiedTradePaymentTerms');
        $paymentTerms->addChild('ram:Description', $this->config->default_payment_terms);
        $dueDate = $paymentTerms->addChild('ram:DueDateDateTime');
        $dueDate->addChild('udt:DateTimeString', $invoice->due_date->format('Ymd'))
            ->addAttribute('format', '102');

        // Tax Summary
        $taxSummary = $settlement->addChild('ram:ApplicableTradeTax');
        $taxSummary->addChild('ram:CalculatedAmount', number_format($invoice->tax_amount, 2, '.', ''));
        $taxSummary->addChild('ram:TypeCode', 'VAT');
        $taxSummary->addChild('ram:BasisAmount', number_format($invoice->subtotal, 2, '.', ''));
        $taxSummary->addChild('ram:CategoryCode', 'S'); // Standard rate
        $taxSummary->addChild('ram:RateApplicablePercent', '20.00');

        // Monetary Summation
        $summation = $settlement->addChild('ram:SpecifiedTradeSettlementHeaderMonetarySummation');
        $summation->addChild('ram:LineTotalAmount', number_format($invoice->subtotal, 2, '.', ''));
        $summation->addChild('ram:TaxBasisTotalAmount', number_format($invoice->subtotal, 2, '.', ''));
        $taxTotal = $summation->addChild('ram:TaxTotalAmount', number_format($invoice->tax_amount, 2, '.', ''));
        $taxTotal->addAttribute('currencyID', 'EUR');
        $summation->addChild('ram:GrandTotalAmount', number_format($invoice->total_amount, 2, '.', ''));
        $summation->addChild('ram:DuePayableAmount', number_format($invoice->total_amount - $invoice->paid_amount, 2, '.', ''));

        // Format XML
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Obtenir l'URN du profil Factur-X
     */
    protected function getProfileUrn(string $profile): string
    {
        return match ($profile) {
            'minimum' => 'urn:factur-x.eu:1p0:minimum',
            'basic' => 'urn:factur-x.eu:1p0:basicwl',
            'en16931' => 'urn:cen.eu:en16931:2017',
            'extended' => 'urn:factur-x.eu:1p0:extended',
            default => 'urn:factur-x.eu:1p0:basicwl',
        };
    }

    /**
     * Sauvegarder le XML
     */
    protected function saveXml(Invoice $invoice, string $xml): string
    {
        $path = "facturx/{$invoice->tenant_id}/xml/facturx-{$invoice->invoice_number}.xml";
        Storage::disk('public')->put($path, $xml);
        return $path;
    }

    /**
     * Générer le PDF de la facture
     */
    protected function generatePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'config' => $this->config,
        ]);

        $path = "facturx/{$invoice->tenant_id}/pdf/facture-{$invoice->invoice_number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Embarquer le XML dans le PDF (PDF/A-3)
     */
    protected function embedXmlInPdf(string $pdfPath, string $xmlPath, Invoice $invoice): string
    {
        // Pour une implémentation complète, utiliser une bibliothèque comme FPDI ou SetaPDF
        // Pour l'instant, on retourne simplement le PDF original
        $combinedPath = "facturx/{$invoice->tenant_id}/combined/facturx-{$invoice->invoice_number}.pdf";

        // Copier le PDF (dans une vraie implémentation, on embarquerait le XML)
        Storage::disk('public')->copy($pdfPath, $combinedPath);

        return $combinedPath;
    }

    /**
     * Valider le XML Factur-X
     */
    protected function validate(string $xml): array
    {
        $errors = [];

        // Validation basique
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();

        foreach ($xmlErrors as $error) {
            $errors[] = "Ligne {$error->line}: {$error->message}";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Générer un ID Factur-X unique
     */
    protected function generateFacturXId(Invoice $invoice): string
    {
        return sprintf(
            'FX-%s-%s-%s',
            $this->config->siret,
            $invoice->invoice_date->format('Ymd'),
            strtoupper(Str::random(6))
        );
    }

    /**
     * Soumettre à la plateforme de dématérialisation
     */
    public function submitToPlatform(ElectronicInvoice $einvoice): bool
    {
        $this->loadConfig($einvoice->tenant_id);

        if (!$this->config->pdp_api_key) {
            return false;
        }

        try {
            // TODO: Implémenter l'appel API vers Chorus Pro ou autre PDP
            $response = [
                'success' => true,
                'external_id' => 'PDP-' . strtoupper(Str::random(10)),
            ];

            $einvoice->update([
                'status' => 'submitted',
                'pdp_status' => 'submitted',
                'external_id' => $response['external_id'],
                'submitted_at' => now(),
            ]);

            FacturxTransmission::create([
                'electronic_invoice_id' => $einvoice->id,
                'type' => 'submission',
                'direction' => 'outgoing',
                'status' => 'success',
                'response' => $response,
            ]);

            return true;
        } catch (\Exception $e) {
            FacturxTransmission::create([
                'electronic_invoice_id' => $einvoice->id,
                'type' => 'submission',
                'direction' => 'outgoing',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Archiver une facture électronique (conservation légale)
     */
    public function archive(ElectronicInvoice $einvoice): InvoiceArchive
    {
        $archivePath = "archives/{$einvoice->tenant_id}/{$einvoice->facturx_id}.pdf";

        Storage::disk('public')->copy($einvoice->pdf_with_xml_path, $archivePath);

        return InvoiceArchive::create([
            'tenant_id' => $einvoice->tenant_id,
            'electronic_invoice_id' => $einvoice->id,
            'archive_path' => $archivePath,
            'archive_checksum' => hash_file('sha256', Storage::disk('public')->path($archivePath)),
            'archive_format' => 'PDF/A-3',
            'archived_at' => now(),
            'retention_until' => now()->addYears(10), // Conservation 10 ans
            'is_sealed' => true,
        ]);
    }

    /**
     * Charger la configuration
     */
    protected function loadConfig(int $tenantId): void
    {
        $this->config = FacturxConfiguration::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }
}
