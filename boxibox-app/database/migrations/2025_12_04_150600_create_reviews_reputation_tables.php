<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avis clients
        if (!Schema::hasTable('customer_reviews')) {
            Schema::create('customer_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->string('source')->default('internal');
                $table->string('external_id')->nullable();
                $table->integer('rating');
                $table->text('comment')->nullable();
                $table->text('response')->nullable();
                $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('responded_at')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_public')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'rating']);
            });
        }

        // Demandes d'avis
        if (!Schema::hasTable('review_requests')) {
            Schema::create('review_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->string('token')->unique();
                $table->enum('status', ['pending', 'sent', 'opened', 'completed', 'expired'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->integer('reminder_count')->default(0);
                $table->timestamps();
            });
        }

        // NPS (Net Promoter Score)
        if (!Schema::hasTable('nps_surveys')) {
            Schema::create('nps_surveys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->string('token')->unique();
                $table->integer('score')->nullable();
                $table->text('feedback')->nullable();
                $table->string('category')->nullable();
                $table->enum('status', ['sent', 'opened', 'completed', 'expired'])->default('sent');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // Réclamations
        if (!Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('complaint_number')->unique();
                $table->string('subject');
                $table->text('description');
                $table->enum('category', ['billing', 'access', 'cleanliness', 'security', 'staff', 'facility', 'contract', 'other']);
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'pending_customer', 'resolved', 'closed', 'escalated'])->default('open');
                $table->text('resolution')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('satisfaction_score')->nullable();
                $table->text('customer_feedback')->nullable();
                $table->json('attachments')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // Historique des réclamations
        if (!Schema::hasTable('complaint_history')) {
            Schema::create('complaint_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->text('comment')->nullable();
                $table->string('old_status')->nullable();
                $table->string('new_status')->nullable();
                $table->timestamps();
            });
        }

        // Enquêtes de satisfaction
        if (!Schema::hasTable('satisfaction_surveys')) {
            Schema::create('satisfaction_surveys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('trigger', ['after_checkin', 'after_checkout', 'after_payment', 'after_support', 'periodic', 'manual']);
                $table->integer('trigger_days')->nullable();
                $table->json('questions');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Réponses aux enquêtes
        if (!Schema::hasTable('survey_responses')) {
            Schema::create('survey_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('token')->unique();
                $table->json('answers')->nullable();
                $table->enum('status', ['sent', 'started', 'completed'])->default('sent');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('satisfaction_surveys');
        Schema::dropIfExists('complaint_history');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('nps_surveys');
        Schema::dropIfExists('review_requests');
        Schema::dropIfExists('customer_reviews');
    }
};
