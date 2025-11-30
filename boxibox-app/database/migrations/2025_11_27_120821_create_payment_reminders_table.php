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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['pre_due', 'on_due', 'overdue', 'custom'])->default('overdue');
            $table->integer('level')->default(1); // Niveau de relance (1, 2, 3...)
            $table->integer('days_before_due')->nullable(); // Pour relances avant échéance
            $table->integer('days_after_due')->nullable(); // Pour relances après échéance
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->enum('method', ['email', 'sms', 'letter', 'both'])->default('email');
            $table->decimal('amount_due', 12, 2)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Pour stocker des infos supplémentaires
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
