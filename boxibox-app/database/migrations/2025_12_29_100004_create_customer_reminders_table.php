<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Related entities (polymorphic)
            $table->string('remindable_type')->nullable(); // Contract, Invoice, Booking, etc.
            $table->unsignedBigInteger('remindable_id')->nullable();

            // Reminder details
            $table->enum('type', [
                'contract_expiry',      // Fin de contrat
                'invoice_due',          // Facture à payer
                'payment_failed',       // Paiement échoué
                'booking_confirmation', // Réservation à confirmer
                'visit_scheduled',      // Visite planifiée
                'document_required',    // Document requis
                'insurance_renewal',    // Renouvellement assurance
                'custom'                // Rappel personnalisé
            ]);
            $table->string('title');
            $table->text('message');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Scheduling
            $table->timestamp('remind_at');
            $table->timestamp('reminded_at')->nullable();

            // Repeat settings
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->unsignedInteger('recurrence_interval')->default(1); // every X days/weeks/months
            $table->timestamp('recurrence_end')->nullable();
            $table->unsignedInteger('occurrence_count')->default(0);
            $table->unsignedInteger('max_occurrences')->nullable();

            // Notification channels
            $table->json('channels')->default('["in_app"]'); // in_app, email, sms, push

            // Status
            $table->enum('status', ['pending', 'sent', 'dismissed', 'snoozed', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Tracking
            $table->boolean('email_sent')->default(false);
            $table->boolean('sms_sent')->default(false);
            $table->boolean('push_sent')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Action
            $table->string('action_url')->nullable(); // Deep link to action
            $table->string('action_label')->nullable(); // Button text

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index(['remind_at', 'status']);
            $table->index(['remindable_type', 'remindable_id']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_reminders');
    }
};
