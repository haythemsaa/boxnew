<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal d'audit complet
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('user_type')->nullable();
                $table->string('action');
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('url')->nullable();
                $table->string('method')->nullable();
                $table->json('tags')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
                $table->index(['auditable_type', 'auditable_id']);
                $table->index(['user_id', 'created_at']);
                $table->index('action');
            });
        }

        // Consentements RGPD
        if (!Schema::hasTable('gdpr_consents')) {
            Schema::create('gdpr_consents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->string('consent_type');
                $table->boolean('is_granted')->default(false);
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->text('consent_text')->nullable();
                $table->string('version')->nullable();
                $table->timestamp('granted_at')->nullable();
                $table->timestamp('withdrawn_at')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'consent_type']);
            });
        }

        // Demandes de droits RGPD
        if (!Schema::hasTable('gdpr_requests')) {
            Schema::create('gdpr_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('request_number')->unique();
                $table->string('requester_email');
                $table->string('requester_name');
                $table->enum('type', ['access', 'rectification', 'erasure', 'portability', 'restriction', 'objection']);
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('deadline_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('response')->nullable();
                $table->json('data_exported')->nullable();
                $table->json('data_deleted')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // Politiques de rétention des données
        if (!Schema::hasTable('data_retention_policies')) {
            Schema::create('data_retention_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('data_type');
                $table->integer('retention_days');
                $table->enum('action_after_retention', ['delete', 'anonymize', 'archive']);
                $table->boolean('is_active')->default(true);
                $table->text('legal_basis')->nullable();
                $table->timestamps();
            });
        }

        // Exécution des politiques de rétention
        if (!Schema::hasTable('data_retention_logs')) {
            Schema::create('data_retention_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('policy_id')->constrained('data_retention_policies')->cascadeOnDelete();
                $table->timestamp('executed_at')->nullable();
                $table->integer('records_processed')->default(0);
                $table->integer('records_deleted')->default(0);
                $table->integer('records_anonymized')->default(0);
                $table->integer('records_archived')->default(0);
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        // Registre des traitements
        if (!Schema::hasTable('processing_register')) {
            Schema::create('processing_register', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('purpose');
                $table->text('data_categories');
                $table->text('data_subjects');
                $table->text('recipients')->nullable();
                $table->text('transfers_outside_eu')->nullable();
                $table->text('retention_period');
                $table->text('security_measures');
                $table->string('legal_basis');
                $table->string('responsible_person')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Violations de données
        if (!Schema::hasTable('data_breaches')) {
            Schema::create('data_breaches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference_number')->unique();
                $table->timestamp('discovered_at')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->text('description');
                $table->text('data_affected');
                $table->integer('records_affected')->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical']);
                $table->enum('status', ['investigating', 'contained', 'resolved', 'reported'])->default('investigating');
                $table->boolean('authority_notified')->default(false);
                $table->timestamp('authority_notified_at')->nullable();
                $table->boolean('subjects_notified')->default(false);
                $table->timestamp('subjects_notified_at')->nullable();
                $table->text('containment_measures')->nullable();
                $table->text('remediation_actions')->nullable();
                $table->text('lessons_learned')->nullable();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Accès aux données sensibles
        if (!Schema::hasTable('sensitive_data_access')) {
            Schema::create('sensitive_data_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('data_type');
                $table->unsignedBigInteger('record_id');
                $table->string('access_type');
                $table->text('reason')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });
        }

        // Export de données
        if (!Schema::hasTable('data_exports')) {
            Schema::create('data_exports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('gdpr_request_id')->nullable();
                $table->enum('type', ['customer_data', 'contracts', 'invoices', 'full_export', 'audit_logs']);
                $table->enum('format', ['json', 'csv', 'pdf', 'zip']);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired'])->default('pending');
                $table->string('file_path')->nullable();
                $table->integer('file_size')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('data_exports');
        Schema::dropIfExists('sensitive_data_access');
        Schema::dropIfExists('data_breaches');
        Schema::dropIfExists('processing_register');
        Schema::dropIfExists('data_retention_logs');
        Schema::dropIfExists('data_retention_policies');
        Schema::dropIfExists('gdpr_requests');
        Schema::dropIfExists('gdpr_consents');
        Schema::dropIfExists('audit_logs');
    }
};
