<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Site $site;
    protected Customer $customer;
    protected Box $box;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create user for tenant
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create site
        $this->site = Site::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create available box
        $this->box = Box::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'status' => 'available',
        ]);
    }

    public function test_user_can_view_contracts_index(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('tenant.contracts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Contracts/Index'));
    }

    public function test_user_can_create_contract(): void
    {
        $this->actingAs($this->user);

        $contractData = [
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'type' => 'standard',
            'status' => 'active',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'monthly_price' => 150.00,
            'deposit_amount' => 300.00,
            'payment_method' => 'bank_transfer',
            'billing_day' => 1,
            'auto_renew' => true,
            'signed_by_customer' => false,
            'signed_by_staff' => false,
        ];

        $response = $this->post(route('tenant.contracts.store'), $contractData);

        $response->assertRedirect(route('tenant.contracts.index'));
        $this->assertDatabaseHas('contracts', [
            'customer_id' => $this->customer->id,
            'box_id' => $this->box->id,
            'status' => 'active',
        ]);
    }

    public function test_creating_active_contract_updates_box_status(): void
    {
        $this->actingAs($this->user);

        $contractData = [
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'type' => 'standard',
            'status' => 'active',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'monthly_price' => 150.00,
            'deposit_amount' => 300.00,
            'payment_method' => 'bank_transfer',
            'billing_day' => 1,
            'auto_renew' => false,
            'signed_by_customer' => false,
            'signed_by_staff' => false,
        ];

        $this->post(route('tenant.contracts.store'), $contractData);

        $this->box->refresh();
        $this->assertEquals('occupied', $this->box->status);
    }

    public function test_user_cannot_view_other_tenant_contracts(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherContract = Contract::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('tenant.contracts.show', $otherContract));

        $response->assertStatus(403);
    }

    public function test_contract_termination_frees_box(): void
    {
        $this->actingAs($this->user);

        $contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'active',
        ]);

        $this->box->update(['status' => 'occupied']);

        $response = $this->post(route('tenant.contracts.terminate', $contract), [
            'termination_reason' => 'customer_request',
            'termination_notes' => 'Client requested termination',
            'effective_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $this->box->refresh();
        $this->assertEquals('available', $this->box->status);

        $contract->refresh();
        $this->assertEquals('terminated', $contract->status);
    }
}
