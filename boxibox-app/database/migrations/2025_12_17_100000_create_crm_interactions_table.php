<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Polymorphic relation to Lead, Prospect, or Customer
            $table->morphs('interactable');

            // Who performed the interaction
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Type of interaction
            $table->enum('type', [
                'call',           // Appel téléphonique
                'email',          // Email envoyé
                'email_received', // Email reçu
                'meeting',        // Rendez-vous
                'visit',          // Visite sur site
                'sms',            // SMS envoyé
                'sms_received',   // SMS reçu
                'note',           // Note interne
                'task',           // Tâche
                'status_change',  // Changement de statut
                'quote',          // Devis envoyé
                'contract',       // Contrat signé
                'payment',        // Paiement reçu
                'reminder',       // Relance
                'whatsapp',       // WhatsApp
                'chat',           // Chat en ligne
                'other',          // Autre
            ])->default('note');

            // Interaction details
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->text('outcome')->nullable(); // Résultat de l'interaction

            // Call-specific fields
            $table->enum('direction', ['inbound', 'outbound'])->nullable();
            $table->integer('duration_seconds')->nullable();

            // Task/Meeting scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_completed')->default(false);

            // Priority and status
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();

            // Related entities
            $table->foreignId('related_contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->unsignedBigInteger('related_quote_id')->nullable(); // No FK constraint as quotes table may not exist

            // Reminder
            $table->timestamp('reminder_at')->nullable();
            $table->boolean('reminder_sent')->default(false);

            // Metadata for additional info
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['tenant_id', 'interactable_type', 'interactable_id'], 'crm_int_entity_idx');
            $table->index(['tenant_id', 'type'], 'crm_int_type_idx');
            $table->index(['tenant_id', 'user_id'], 'crm_int_user_idx');
            $table->index(['scheduled_at'], 'crm_int_scheduled_idx');
            $table->index(['reminder_at', 'reminder_sent'], 'crm_int_reminder_idx');
        });

        // Create a table for interaction attachments
        Schema::create('crm_interaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_interaction_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interaction_attachments');
        Schema::dropIfExists('crm_interactions');
    }
};
