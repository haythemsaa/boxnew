<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

            // Payment Details
            $table->string('payment_number')->unique();
            $table->enum('type', ['payment', 'refund', 'deposit'])->default('payment');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');

            // Amounts
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Payment Method
            $table->enum('method', ['card', 'bank_transfer', 'cash', 'cheque', 'sepa', 'stripe', 'paypal'])->default('card');
            $table->enum('gateway', ['stripe', 'paypal', 'sepa', 'manual'])->default('stripe');

            // Gateway Info
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_customer_id')->nullable();
            $table->json('gateway_response')->nullable();

            // Card Info
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();

            // Dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Refund
            $table->foreignId('refund_for_payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->decimal('refunded_amount', 10, 2)->default(0);

            // Failure Info
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('gateway_payment_id');
            $table->index('payment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
