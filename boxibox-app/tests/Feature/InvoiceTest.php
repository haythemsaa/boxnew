<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Customer $customer;
    protected Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_user_can_view_invoices_index(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('tenant.invoices.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Invoices/Index'));
    }

    public function test_user_can_create_invoice(): void
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'type' => 'standard',
            'status' => 'draft',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => 100.00,
            'tax_rate' => 20.00,
            'tax_amount' => 20.00,
            'total' => 120.00,
            'notes' => 'Test invoice',
        ];

        $response = $this->post(route('tenant.invoices.store'), $invoiceData);

        $response->assertRedirect(route('tenant.invoices.index'));
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'total' => 120.00,
        ]);
    }

    public function test_invoice_number_is_auto_generated(): void
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'type' => 'standard',
            'status' => 'draft',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => 100.00,
            'tax_rate' => 20.00,
            'tax_amount' => 20.00,
            'total' => 120.00,
        ];

        $this->post(route('tenant.invoices.store'), $invoiceData);

        $invoice = Invoice::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
    }

    public function test_credit_note_has_cn_prefix(): void
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'type' => 'credit_note',
            'status' => 'draft',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => -50.00,
            'tax_rate' => 20.00,
            'tax_amount' => -10.00,
            'total' => -60.00,
        ];

        $this->post(route('tenant.invoices.store'), $invoiceData);

        $invoice = Invoice::where('customer_id', $this->customer->id)
            ->where('type', 'credit_note')
            ->first();
        $this->assertStringStartsWith('CN-', $invoice->invoice_number);
    }

    public function test_user_cannot_view_other_tenant_invoices(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('tenant.invoices.show', $otherInvoice));

        $response->assertStatus(403);
    }

    public function test_invoice_stats_are_calculated_correctly(): void
    {
        $this->actingAs($this->user);

        // Create invoices with different statuses
        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
        ]);

        Invoice::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'overdue',
            'total' => 100,
        ]);

        $response = $this->get(route('tenant.invoices.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('stats')
            ->where('stats.paid', 3)
            ->where('stats.overdue', 2)
        );
    }
}
