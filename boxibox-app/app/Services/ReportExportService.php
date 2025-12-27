<?php

namespace App\Services;

use App\Models\CustomReport;
use App\Models\ReportHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportExportService
{
    /**
     * Generate CSV content from data array
     */
    public function generateCsv(array $data, array $columns = []): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        $headers = array_keys($data[0] ?? []);
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate Excel content from data array
     */
    public function generateExcel(array $data, array $columns = []): string
    {
        if (empty($data)) {
            return '';
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', ucfirst(str_replace('_', ' ', $header)));
            $col++;
        }

        // Style headers
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-width columns
        foreach (range('A', $lastCol) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Generate file content
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Generate PDF content from data array
     */
    public function generatePdf(array $data, CustomReport $report): string
    {
        if (empty($data)) {
            return '';
        }

        // Build HTML table
        $html = $this->buildPdfHtml($data, $report);

        // Generate PDF using Dompdf
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->output();
    }

    /**
     * Build HTML for PDF generation
     */
    protected function buildPdfHtml(array $data, CustomReport $report): string
    {
        $headers = array_keys($data[0] ?? []);

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; }
                h1 { color: #1E3A8A; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background: #1E3A8A; color: white; padding: 8px; text-align: left; }
                td { border: 1px solid #ddd; padding: 6px; }
                tr:nth-child(even) { background: #f9f9f9; }
                .header { margin-bottom: 20px; }
                .footer { margin-top: 20px; font-size: 9px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($report->name) . '</h1>
                <p>Généré le ' . now()->format('d/m/Y H:i') . '</p>
            </div>
            <table>
                <thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $header))) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>
            <div class="footer">
                <p>Total: ' . count($data) . ' enregistrements</p>
            </div>
        </body></html>';

        return $html;
    }

    /**
     * Get content type for a format
     */
    public function getContentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'xlsx', 'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'text/plain',
        };
    }

    /**
     * Get file extension for a format
     */
    public function getFileExtension(string $format): string
    {
        return match ($format) {
            'xlsx', 'excel' => 'xlsx',
            default => $format,
        };
    }

    /**
     * Export report and record history
     */
    public function exportReport(CustomReport $report, array $data, string $format, array $filters = []): array
    {
        $startTime = microtime(true);

        try {
            $content = match ($format) {
                'csv' => $this->generateCsv($data, $report->columns ?? []),
                'xlsx', 'excel' => $this->generateExcel($data, $report->columns ?? []),
                'pdf' => $this->generatePdf($data, $report),
                default => $this->generateCsv($data, $report->columns ?? []),
            };

            $generationTime = (microtime(true) - $startTime) * 1000;

            // Record success in history
            ReportHistory::create([
                'custom_report_id' => $report->id,
                'generated_by' => Auth::id(),
                'format' => $format,
                'row_count' => count($data),
                'generation_time_ms' => $generationTime,
                'parameters_used' => $filters,
                'status' => 'completed',
            ]);

            $filename = $report->name . '_' . now()->format('Y-m-d') . '.' . $this->getFileExtension($format);

            return [
                'success' => true,
                'content' => $content,
                'filename' => $filename,
                'content_type' => $this->getContentType($format),
            ];

        } catch (\Exception $e) {
            $generationTime = (microtime(true) - $startTime) * 1000;

            // Record failure in history
            ReportHistory::create([
                'custom_report_id' => $report->id,
                'generated_by' => Auth::id(),
                'format' => $format,
                'row_count' => 0,
                'generation_time_ms' => $generationTime,
                'parameters_used' => $filters,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Failed to export report #{$report->id}: " . $e->getMessage(), [
                'report_id' => $report->id,
                'format' => $format,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Une erreur est survenue lors de la génération du rapport.',
            ];
        }
    }
}
