<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GoCardless customers (linked to our customers)
        Schema::create('gocardless_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('gocardless_id')->unique(); // GC customer ID
            $table->string('email')->nullable();
            $table->string('given_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('company_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
        });

        // SEPA Mandates
        Schema::create('sepa_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('gocardless_customer_id')->nullable()->constrained('gocardless_customers')->onDelete('set null');
            $table->string('gocardless_mandate_id')->unique();
            $table->string('gocardless_bank_account_id')->nullable();

            // Mandate details
            $table->string('reference')->nullable(); // SEPA mandate reference
            $table->string('scheme')->default('sepa_core'); // sepa_core, sepa_b2b
            $table->enum('status', [
                'pending_customer_approval',
                'pending_submission',
                'submitted',
                'active',
                'failed',
                'cancelled',
                'expired',
                'consumed',
                'blocked',
            ])->default('pending_customer_approval');

            // Bank account details (encrypted/masked)
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('iban_last4')->nullable(); // Only store last 4 digits
            $table->string('bic')->nullable();
            $table->string('country_code', 2)->nullable();

            // Dates
            $table->timestamp('next_possible_charge_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
        });

        // GoCardless Payments (linked to our invoices/payments)
        Schema::create('gocardless_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('sepa_mandate_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null'); // Our payment record

            $table->string('gocardless_payment_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('description')->nullable();

            // Payment status
            $table->enum('status', [
                'pending_customer_approval',
                'pending_submission',
                'submitted',
                'confirmed',
                'paid_out',
                'cancelled',
                'customer_approval_denied',
                'failed',
                'charged_back',
            ])->default('pending_submission');

            // Dates
            $table->date('charge_date')->nullable();
            $table->timestamp('paid_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Error handling
            $table->string('failure_reason')->nullable();
            $table->text('failure_description')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['charge_date']);
        });

        // Redirect flows (for mandate setup)
        Schema::create('gocardless_redirect_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('redirect_flow_id')->unique();
            $table->string('session_token');
            $table->string('success_redirect_url')->nullable();
            $table->enum('status', ['created', 'completed', 'expired', 'cancelled'])->default('created');
            $table->foreignId('created_mandate_id')->nullable()->constrained('sepa_mandates')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // GoCardless Events (webhook events)
        Schema::create('gocardless_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_id')->unique();
            $table->string('action');
            $table->string('resource_type');
            $table->string('resource_id');
            $table->json('links')->nullable();
            $table->json('details')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
            $table->index(['processed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gocardless_events');
        Schema::dropIfExists('gocardless_redirect_flows');
        Schema::dropIfExists('gocardless_payments');
        Schema::dropIfExists('sepa_mandates');
        Schema::dropIfExists('gocardless_customers');
    }
};
