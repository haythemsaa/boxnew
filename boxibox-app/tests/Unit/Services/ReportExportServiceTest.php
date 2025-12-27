<?php

namespace Tests\Unit\Services;

use App\Models\CustomReport;
use App\Models\ReportHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportExportService $service;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportExportService();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_generate_csv_creates_valid_csv(): void
    {
        $data = [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'amount' => 100],
            ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'amount' => 200],
        ];

        $csv = $this->service->generateCsv($data);

        $this->assertStringContainsString('name,email,amount', $csv);
        $this->assertStringContainsString('Jean Dupont', $csv);
        $this->assertStringContainsString('Marie Martin', $csv);
        $this->assertStringContainsString('100', $csv);
        $this->assertStringContainsString('200', $csv);
    }

    public function test_generate_csv_handles_empty_data(): void
    {
        $csv = $this->service->generateCsv([]);

        $this->assertEquals('', $csv);
    }

    public function test_generate_csv_escapes_special_characters(): void
    {
        $data = [
            ['name' => 'Jean "Le Boss" Dupont', 'note' => 'Test, avec virgule'],
        ];

        $csv = $this->service->generateCsv($data);

        // CSV should properly escape quotes and handle commas
        $this->assertNotEmpty($csv);
        $lines = explode("\n", trim($csv));
        $this->assertCount(2, $lines); // Header + 1 data row
    }

    public function test_get_content_type_returns_correct_types(): void
    {
        $this->assertEquals('text/csv', $this->service->getContentType('csv'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $this->service->getContentType('xlsx'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $this->service->getContentType('excel'));
        $this->assertEquals('application/pdf', $this->service->getContentType('pdf'));
        $this->assertEquals('text/plain', $this->service->getContentType('unknown'));
    }

    public function test_get_file_extension_returns_correct_extensions(): void
    {
        $this->assertEquals('csv', $this->service->getFileExtension('csv'));
        $this->assertEquals('xlsx', $this->service->getFileExtension('xlsx'));
        $this->assertEquals('xlsx', $this->service->getFileExtension('excel'));
        $this->assertEquals('pdf', $this->service->getFileExtension('pdf'));
    }

    public function test_export_report_creates_history_record_on_success(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Report',
        ]);

        $data = [
            ['name' => 'Test', 'value' => 100],
        ];

        $result = $this->service->exportReport($report, $data, 'csv');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
        $this->assertEquals('text/csv', $result['content_type']);
        $this->assertStringContainsString('Test Report', $result['filename']);
        $this->assertStringContainsString('.csv', $result['filename']);

        // Check history was created
        $this->assertDatabaseHas('report_history', [
            'custom_report_id' => $report->id,
            'format' => 'csv',
            'row_count' => 1,
            'status' => 'completed',
        ]);
    }

    public function test_export_report_records_generation_time(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            ['name' => 'Test', 'value' => 100],
        ];

        $this->service->exportReport($report, $data, 'csv');

        $history = ReportHistory::where('custom_report_id', $report->id)->first();

        $this->assertNotNull($history);
        $this->assertGreaterThan(0, $history->generation_time_ms);
    }

    public function test_export_report_stores_parameters_used(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [['name' => 'Test']];
        $filters = ['date_from' => '2025-01-01', 'site_id' => 5];

        $this->service->exportReport($report, $data, 'csv', $filters);

        $history = ReportHistory::where('custom_report_id', $report->id)->first();

        $this->assertNotNull($history);
        $this->assertEquals($filters, $history->parameters_used);
    }

    public function test_export_report_generates_correct_filename(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Monthly Revenue Report',
        ]);

        $result = $this->service->exportReport($report, [['test' => 1]], 'csv');

        $this->assertStringContainsString('Monthly Revenue Report', $result['filename']);
        $this->assertStringContainsString(now()->format('Y-m-d'), $result['filename']);
        $this->assertStringEndsWith('.csv', $result['filename']);
    }

    public function test_export_report_with_xlsx_format(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            ['name' => 'Test 1', 'amount' => 100],
            ['name' => 'Test 2', 'amount' => 200],
        ];

        $result = $this->service->exportReport($report, $data, 'xlsx');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $result['content_type']
        );
    }

    public function test_generate_excel_creates_valid_spreadsheet(): void
    {
        $data = [
            ['name' => 'Jean Dupont', 'email' => 'jean@example.com', 'amount' => 100],
            ['name' => 'Marie Martin', 'email' => 'marie@example.com', 'amount' => 200],
        ];

        $content = $this->service->generateExcel($data);

        // Excel file should start with PK (ZIP signature)
        $this->assertStringStartsWith('PK', $content);
        $this->assertNotEmpty($content);
    }

    public function test_generate_excel_handles_empty_data(): void
    {
        $content = $this->service->generateExcel([]);

        $this->assertEquals('', $content);
    }

    public function test_export_different_formats_produce_different_content(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [['name' => 'Test', 'value' => 100]];

        $csvResult = $this->service->exportReport($report, $data, 'csv');
        $xlsxResult = $this->service->exportReport($report, $data, 'xlsx');

        $this->assertNotEquals($csvResult['content'], $xlsxResult['content']);
        $this->assertNotEquals($csvResult['content_type'], $xlsxResult['content_type']);
    }
}
