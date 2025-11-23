<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Note: gateway_payment_id and gateway_customer_id already exist in create_payments_table
            $table->string('payment_intent_id')->nullable(); // Stripe payment intent
            $table->boolean('auto_pay')->default(false); // Automatic payment enabled
            $table->integer('retry_count')->default(0); // Number of retry attempts
            $table->timestamp('next_retry_at')->nullable(); // Next retry date
            $table->json('gateway_metadata')->nullable(); // Additional gateway data
        });

        // Create payment methods table for saved payment methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // stripe, paypal, etc.
            $table->string('gateway_method_id'); // Gateway payment method ID
            $table->string('type'); // card, sepa_debit, paypal, etc.
            $table->string('brand')->nullable(); // visa, mastercard, etc.
            $table->string('last4')->nullable(); // Last 4 digits
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_intent_id',
                'auto_pay',
                'retry_count',
                'next_retry_at',
                'gateway_metadata'
            ]);
        });
    }
};
