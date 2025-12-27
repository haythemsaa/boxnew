<?php

namespace Tests\Unit\Models;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Site $site;
    protected Customer $customer;
    protected Box $box;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->site = Site::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->box = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'status' => 'available',
        ]);
    }

    public function test_calculate_final_price_with_no_discount(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
        ]);

        $this->assertEquals(100, $contract->calculateFinalPrice());
    }

    public function test_calculate_final_price_with_percentage_discount(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 100,
            'discount_percentage' => 10,
            'discount_amount' => 0,
        ]);

        $this->assertEquals(90, $contract->calculateFinalPrice());
    }

    public function test_calculate_final_price_with_fixed_discount(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 15,
        ]);

        $this->assertEquals(85, $contract->calculateFinalPrice());
    }

    public function test_calculate_final_price_caps_percentage_at_30(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 100,
            'discount_percentage' => 50, // Trying to apply 50% discount
            'discount_amount' => 0,
        ]);

        // Should be capped at 30% = 70
        $this->assertEquals(70, $contract->calculateFinalPrice());
    }

    public function test_calculate_final_price_caps_combined_discounts_at_30(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 100,
            'discount_percentage' => 20, // 20% = 20
            'discount_amount' => 20, // + 20 = 40 total
        ]);

        // Total discount would be 40, but should be capped at 30
        $this->assertEquals(70, $contract->calculateFinalPrice());
    }

    public function test_calculate_final_price_never_goes_negative(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'monthly_price' => 50,
            'discount_percentage' => 30,
            'discount_amount' => 100, // Trying to apply huge fixed discount
        ]);

        // Final price should never be negative
        $this->assertGreaterThanOrEqual(0, $contract->calculateFinalPrice());
    }

    public function test_contract_creates_with_valid_data(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'active',
            'monthly_price' => 150,
            'start_date' => now(),
        ]);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'active',
            'monthly_price' => 150,
        ]);
    }

    public function test_contract_belongs_to_tenant(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
        ]);

        $this->assertEquals($this->tenant->id, $contract->tenant_id);
        $this->assertInstanceOf(Tenant::class, $contract->tenant);
    }

    public function test_contract_belongs_to_customer(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
        ]);

        $this->assertEquals($this->customer->id, $contract->customer_id);
        $this->assertInstanceOf(Customer::class, $contract->customer);
    }

    public function test_contract_belongs_to_box(): void
    {
        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
        ]);

        $this->assertEquals($this->box->id, $contract->box_id);
        $this->assertInstanceOf(Box::class, $contract->box);
    }

    public function test_contract_active_scope(): void
    {
        Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'active',
        ]);

        $terminatedBox = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
        ]);

        Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $terminatedBox->id,
            'status' => 'terminated',
        ]);

        $activeContracts = Contract::where('tenant_id', $this->tenant->id)
            ->where('status', 'active')
            ->count();

        $this->assertEquals(1, $activeContracts);
    }

    public function test_contract_expiring_soon_scope(): void
    {
        // Contract ending in 15 days
        Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'active',
            'end_date' => now()->addDays(15),
        ]);

        $anotherBox = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
        ]);

        // Contract ending in 60 days
        Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $anotherBox->id,
            'status' => 'active',
            'end_date' => now()->addDays(60),
        ]);

        $expiringSoon = Contract::where('tenant_id', $this->tenant->id)
            ->expiringSoon(30)
            ->count();

        $this->assertEquals(1, $expiringSoon);
    }
}
