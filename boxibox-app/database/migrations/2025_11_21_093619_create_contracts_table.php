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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');

            // Contract Details
            $table->string('contract_number')->unique();
            $table->enum('status', ['draft', 'pending_signature', 'active', 'expired', 'terminated', 'cancelled'])->default('draft');
            $table->enum('type', ['standard', 'short_term', 'long_term'])->default('standard');

            // Dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->integer('notice_period_days')->default(30);

            // Renewal
            $table->boolean('auto_renew')->default(true);
            $table->enum('renewal_period', ['monthly', 'quarterly', 'yearly'])->default('monthly');

            // Pricing
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);

            // Discounts
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);

            // Payment
            $table->enum('billing_frequency', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('billing_day')->default(1);
            $table->enum('payment_method', ['card', 'bank_transfer', 'cash', 'sepa'])->default('card');
            $table->boolean('auto_pay')->default(false);

            // Access
            $table->string('access_code', 10)->nullable();
            $table->boolean('key_given')->default(false);
            $table->boolean('key_returned')->default(false);

            // Signature
            $table->boolean('signed_by_customer')->default(false);
            $table->timestamp('customer_signed_at')->nullable();
            $table->boolean('signed_by_staff')->default(false);
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Documents
            $table->string('pdf_path')->nullable();

            // Termination
            $table->enum('termination_reason', ['customer_request', 'non_payment', 'breach', 'end_of_term', 'other'])->nullable();
            $table->text('termination_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'box_id']);
            $table->index('contract_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
