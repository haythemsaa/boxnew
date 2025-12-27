<?php

namespace Tests\Unit\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomReport;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Tenant;
use App\Services\ReportDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportDataService $service;
    protected Tenant $tenant;
    protected Site $site;
    protected Customer $customer;
    protected Box $box;
    protected Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportDataService();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create site
        $this->site = Site::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'type' => 'individual',
        ]);

        // Create box
        $this->box = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'number' => 'BOX-001',
            'length' => 3,
            'width' => 2,
            'height' => 2.5,
            'status' => 'occupied',
            'base_price' => 100,
            'current_price' => 100,
        ]);

        // Create active contract
        $this->contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'active',
            'monthly_price' => 100,
            'start_date' => now()->subMonths(3),
        ]);
    }

    public function test_get_report_types_returns_expected_types(): void
    {
        $types = $this->service->getReportTypes();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);

        $typeValues = array_column($types, 'value');
        $this->assertContains('rent_roll', $typeValues);
        $this->assertContains('revenue', $typeValues);
        $this->assertContains('occupancy', $typeValues);
        $this->assertContains('aging', $typeValues);
    }

    public function test_get_available_columns_returns_expected_structure(): void
    {
        $columns = $this->service->getAvailableColumns();

        $this->assertIsArray($columns);
        $this->assertArrayHasKey('contracts', $columns);
        $this->assertArrayHasKey('invoices', $columns);
        $this->assertArrayHasKey('payments', $columns);
        $this->assertArrayHasKey('boxes', $columns);
    }

    public function test_get_rent_roll_data_returns_active_contracts(): void
    {
        $data = $this->service->getRentRollData($this->tenant->id);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($this->contract->contract_number, $data[0]['contract']);
        $this->assertEquals('Jean Dupont', $data[0]['customer']);
        $this->assertEquals($this->site->name, $data[0]['site']);
        $this->assertEquals(100, $data[0]['monthly_rent']);
    }

    public function test_get_rent_roll_data_filters_by_site(): void
    {
        // Create another site with a contract
        $otherSite = Site::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherBox = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $otherSite->id,
            'status' => 'occupied',
        ]);
        Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $otherSite->id,
            'box_id' => $otherBox->id,
            'status' => 'active',
        ]);

        // Filter by original site
        $data = $this->service->getRentRollData($this->tenant->id, ['site_id' => $this->site->id]);

        $this->assertCount(1, $data);
        $this->assertEquals($this->site->name, $data[0]['site']);
    }

    public function test_get_occupancy_data_returns_all_boxes(): void
    {
        $data = $this->service->getOccupancyData($this->tenant->id);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($this->site->name, $data[0]['site']);
        $this->assertEquals('BOX-001', $data[0]['box']);
        $this->assertEquals(6.0, $data[0]['size_m2']); // 3 * 2
        $this->assertEquals('Occupé', $data[0]['status']);
        $this->assertEquals('Jean Dupont', $data[0]['customer']);
    }

    public function test_get_occupancy_data_filters_by_status(): void
    {
        // Create available box
        Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'status' => 'available',
        ]);

        // Filter by occupied status
        $data = $this->service->getOccupancyData($this->tenant->id, ['status' => 'occupied']);

        $this->assertCount(1, $data);
        $this->assertEquals('Occupé', $data[0]['status']);
    }

    public function test_get_aging_data_returns_overdue_invoices(): void
    {
        // Create overdue invoice
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(15),
            'total' => 100,
            'paid_amount' => 0,
        ]);

        $data = $this->service->getAgingData($this->tenant->id);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals(100, $data[0]['total']);
        $this->assertEquals(100, $data[0]['remaining']);
        $this->assertEquals('0-30 jours', $data[0]['aging_bucket']);
    }

    public function test_get_aging_data_calculates_correct_aging_buckets(): void
    {
        // Create invoices with different aging
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(45),
            'total' => 100,
        ]);

        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(75),
            'total' => 200,
        ]);

        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'status' => 'overdue',
            'due_date' => now()->subDays(100),
            'total' => 300,
        ]);

        $data = $this->service->getAgingData($this->tenant->id);

        $this->assertCount(3, $data);

        $buckets = array_column($data, 'aging_bucket');
        $this->assertContains('31-60 jours', $buckets);
        $this->assertContains('61-90 jours', $buckets);
        $this->assertContains('90+ jours', $buckets);
    }

    public function test_get_revenue_data_returns_completed_payments(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'status' => 'paid',
            'total' => 100,
        ]);

        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'amount' => 100,
            'paid_at' => now(),
        ]);

        $data = $this->service->getRevenueData($this->tenant->id);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals(100, $data[0]['amount']);
    }

    public function test_get_revenue_data_filters_by_date_range(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
        ]);

        // Payment from last month
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'amount' => 100,
            'paid_at' => now()->subMonth(),
        ]);

        // Payment from this month
        Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'amount' => 200,
            'paid_at' => now(),
        ]);

        // Filter by this month only
        $data = $this->service->getRevenueData($this->tenant->id, [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]);

        $this->assertCount(1, $data);
        $this->assertEquals(200, $data[0]['amount']);
    }

    public function test_get_cash_flow_projection_returns_expected_structure(): void
    {
        $data = $this->service->getCashFlowProjection($this->tenant->id);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('projection', $data);
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('overdue', $data);
        $this->assertArrayHasKey('total_expected_3m', $data);

        // MRR should equal active contract monthly price
        $this->assertEquals(100, $data['mrr']);

        // Projection should have 3 months
        $this->assertCount(3, $data['projection']);
    }

    public function test_generate_report_data_dispatches_to_correct_method(): void
    {
        $report = CustomReport::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'rent_roll',
        ]);

        $data = $this->service->generateReportData($report, $this->tenant->id);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('contract', $data[0]);
    }

    public function test_data_respects_tenant_isolation(): void
    {
        // Create data for another tenant
        $otherTenant = Tenant::factory()->create();
        $otherSite = Site::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherBox = Box::factory()->create([
            'tenant_id' => $otherTenant->id,
            'site_id' => $otherSite->id,
            'status' => 'occupied',
        ]);
        Contract::factory()->create([
            'tenant_id' => $otherTenant->id,
            'site_id' => $otherSite->id,
            'box_id' => $otherBox->id,
            'status' => 'active',
        ]);

        // Request data for original tenant
        $data = $this->service->getRentRollData($this->tenant->id);

        // Should only see original tenant's contract
        $this->assertCount(1, $data);
        $this->assertEquals($this->contract->contract_number, $data[0]['contract']);
    }
}
