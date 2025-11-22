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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

            // Invoice Details
            $table->string('invoice_number')->unique();
            $table->enum('type', ['invoice', 'credit_note', 'proforma'])->default('invoice');
            $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();

            // Period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);

            // Currency
            $table->string('currency', 3)->default('EUR');

            // Line Items (JSON)
            $table->json('items');

            // Documents
            $table->string('pdf_path')->nullable();

            // Reminders
            $table->integer('reminder_count')->default(0);
            $table->date('last_reminder_sent')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Recurring
            $table->boolean('is_recurring')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
