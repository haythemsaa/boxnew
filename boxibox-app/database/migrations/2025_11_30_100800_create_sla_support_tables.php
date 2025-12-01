<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans de support/SLA
        Schema::create('support_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // SLA Garanties
            $table->decimal('uptime_guarantee', 5, 2)->default(99.0); // 99.0%
            $table->integer('response_time_minutes')->default(480); // 8h par défaut
            $table->integer('resolution_time_hours')->nullable();

            // Support inclus
            $table->boolean('email_support')->default(true);
            $table->boolean('phone_support')->default(false);
            $table->boolean('chat_support')->default(false);
            $table->boolean('dedicated_manager')->default(false);
            $table->integer('training_hours_included')->default(0);

            // Horaires support
            $table->json('support_hours')->nullable(); // {"mon-fri": "9:00-18:00", "sat": "9:00-12:00"}
            $table->boolean('weekend_support')->default(false);
            $table->boolean('holiday_support')->default(false);
            $table->boolean('support_24_7')->default(false);

            // Limites
            $table->integer('max_tickets_month')->nullable();
            $table->integer('max_users')->nullable();
            $table->integer('max_sites')->nullable();

            // Tarification
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);

            $table->timestamps();
        });

        // Abonnements des tenants aux plans de support
        Schema::create('tenant_support_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('support_plans');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending'])->default('active');

            // Account Manager dédié
            $table->foreignId('account_manager_id')->nullable()->constrained('users');

            // Personnalisations
            $table->json('custom_sla')->nullable(); // Overrides du plan
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Tickets de support
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_support_subscriptions');
            $table->foreignId('created_by')->constrained('users');

            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('category', ['technical', 'billing', 'feature_request', 'bug', 'question', 'other'])->default('question');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed'])->default('open');

            // Assignation
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('assigned_team')->nullable();

            // SLA tracking
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('sla_response_met')->nullable();
            $table->boolean('sla_resolution_met')->nullable();
            $table->integer('sla_response_time_minutes')->nullable();
            $table->integer('sla_resolution_time_minutes')->nullable();

            // Satisfaction
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_feedback')->nullable();

            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_at']);
        });

        // Messages des tickets
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();

            $table->text('content');
            $table->boolean('is_internal_note')->default(false);

            $table->json('attachments')->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        // Uptime monitoring
        Schema::create('uptime_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('endpoint_url');

            $table->enum('status', ['up', 'down', 'degraded'])->default('up');
            $table->integer('response_time_ms')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['service_name', 'checked_at']);
        });

        // Agrégation uptime par période
        Schema::create('uptime_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();

            $table->date('report_date');
            $table->enum('period', ['daily', 'weekly', 'monthly']);

            $table->decimal('uptime_percentage', 5, 2);
            $table->integer('total_checks');
            $table->integer('successful_checks');
            $table->integer('failed_checks');
            $table->integer('avg_response_time_ms');

            $table->json('incidents')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'report_date', 'period']);
        });

        // Incidents et maintenance planifiée
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');

            $table->enum('type', ['incident', 'maintenance', 'degradation']);
            $table->enum('severity', ['minor', 'major', 'critical']);
            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved'])->default('investigating');

            $table->json('affected_services')->nullable();
            $table->json('affected_tenants')->nullable(); // null = tous

            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('scheduled_start')->nullable(); // Pour maintenance
            $table->timestamp('scheduled_end')->nullable();

            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            $table->index(['status', 'started_at']);
        });

        // Mises à jour d'incidents
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');

            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved']);
            $table->text('message');

            $table->timestamps();
        });

        // Base de connaissances support
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();

            $table->string('category')->nullable();
            $table->json('tags')->nullable();

            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('order')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);

            $table->foreignId('author_id')->constrained('users');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['category', 'is_public']);
        });

        // Crédits SLA (remboursements)
        Schema::create('sla_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('tenant_support_subscriptions');
            $table->foreignId('incident_id')->nullable()->constrained();

            $table->decimal('credit_amount', 10, 2);
            $table->decimal('uptime_actual', 5, 2);
            $table->decimal('uptime_guaranteed', 5, 2);

            $table->date('credit_period_start');
            $table->date('credit_period_end');

            $table->enum('status', ['pending', 'approved', 'applied', 'rejected'])->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('applied_to_invoice')->nullable()->constrained('invoices');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_credits');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('uptime_reports');
        Schema::dropIfExists('uptime_checks');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('tenant_support_subscriptions');
        Schema::dropIfExists('support_plans');
    }
};
