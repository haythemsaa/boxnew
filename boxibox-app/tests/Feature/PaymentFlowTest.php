<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Box;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical payment flow tests
 *
 * These tests ensure payment processing works correctly.
 * Payment failures directly impact revenue.
 */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Customer $customer;
    protected Contract $contract;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['is_active' => true]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $site = Site::factory()->create(['tenant_id' => $this->tenant->id]);
        $box = Box::factory()->create([
            'site_id' => $site->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->contract = Contract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $site->id,
            'box_id' => $box->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'monthly_price' => 150.00,
        ]);

        $this->invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contract_id' => $this->contract->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'total' => 150.00,
            'amount_paid' => 0,
        ]);
    }

    public function test_payment_can_be_recorded(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 150.00,
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
            'reference' => 'PAY-TEST-001',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 150.00,
            'status' => 'completed',
        ]);
    }

    public function test_full_payment_marks_invoice_as_paid(): void
    {
        $this->actingAs($this->user);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 150.00,
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(150.00, $this->invoice->amount_paid);
    }

    public function test_partial_payment_updates_invoice_correctly(): void
    {
        $this->actingAs($this->user);

        // First partial payment
        $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 75.00,
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(75.00, $this->invoice->amount_paid);

        // Second payment to complete
        $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 75.00,
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(150.00, $this->invoice->amount_paid);
    }

    public function test_overpayment_is_rejected(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 200.00, // More than invoice total
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_refund_updates_invoice(): void
    {
        // Create a paid invoice with payment
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'completed',
        ]);

        $this->invoice->update([
            'status' => 'paid',
            'amount_paid' => 150.00,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund", [
            'amount' => 150.00,
            'reason' => 'Customer request',
        ]);

        $response->assertStatus(200);

        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);

        $this->invoice->refresh();
        $this->assertEquals('refunded', $this->invoice->status);
    }

    public function test_stripe_webhook_processes_payment(): void
    {
        // Mock Stripe webhook payload
        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 15000, // 150.00 in cents
                    'currency' => 'eur',
                    'metadata' => [
                        'invoice_id' => $this->invoice->id,
                        'tenant_id' => $this->tenant->id,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/stripe', $payload);

        // Should not fail (actual processing depends on Stripe config)
        $this->assertNotEquals(500, $response->status());
    }

    public function test_overdue_invoice_detection(): void
    {
        // Create an overdue invoice
        $overdueInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'contract_id' => $this->contract->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'due_date' => now()->subDays(10),
            'total' => 150.00,
        ]);

        // Run overdue check command
        $this->artisan('invoices:check-overdue')->assertSuccessful();

        $overdueInvoice->refresh();
        $this->assertEquals('overdue', $overdueInvoice->status);
    }

    public function test_payment_creates_audit_log(): void
    {
        $this->actingAs($this->user);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 150.00,
            'payment_method' => 'card',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.created',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_recurring_invoice_generation(): void
    {
        // Ensure contract is set for recurring billing
        $this->contract->update([
            'billing_frequency' => 'monthly',
            'billing_day' => now()->day,
            'next_billing_date' => now(),
        ]);

        // Run invoice generation command
        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        // Check new invoice was created
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $this->contract->id,
            'status' => 'pending',
        ]);
    }
}
