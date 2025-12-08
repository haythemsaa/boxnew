<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow de relance impayés
        Schema::create('overdue_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Étapes du workflow
        Schema::create('overdue_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('overdue_workflows')->cascadeOnDelete();
            $table->integer('days_overdue');
            $table->enum('action_type', ['email', 'sms', 'letter', 'lock_box', 'late_fee', 'legal_notice', 'lien_process']);
            $table->string('template_name')->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->decimal('fee_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Actions exécutées
        Schema::create('overdue_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('overdue_workflow_steps')->nullOnDelete();
            $table->enum('action_type', ['email', 'sms', 'letter', 'lock_box', 'unlock_box', 'late_fee', 'legal_notice', 'lien_process', 'auction']);
            $table->enum('status', ['pending', 'executed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->text('result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['contract_id', 'action_type']);
        });

        // Processus d'abandon (lien sale)
        Schema::create('abandonment_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->enum('status', ['initiated', 'notice_sent', 'waiting_period', 'inventory', 'auction_scheduled', 'auction_completed', 'closed', 'cancelled'])->default('initiated');
            $table->decimal('amount_owed', 10, 2);
            $table->date('last_payment_date')->nullable();
            $table->date('notice_sent_date')->nullable();
            $table->date('legal_deadline')->nullable();
            $table->date('auction_date')->nullable();
            $table->text('inventory_description')->nullable();
            $table->json('inventory_photos')->nullable();
            $table->decimal('auction_proceeds', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Frais de retard
        Schema::create('late_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->integer('days_overdue');
            $table->enum('status', ['pending', 'applied', 'waived', 'paid'])->default('pending');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('waiver_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_fees');
        Schema::dropIfExists('abandonment_cases');
        Schema::dropIfExists('overdue_actions');
        Schema::dropIfExists('overdue_workflow_steps');
        Schema::dropIfExists('overdue_workflows');
    }
};
