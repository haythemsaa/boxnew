<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copilot Conversations
        if (!Schema::hasTable('copilot_conversations')) {
            Schema::create('copilot_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('title')->default('Nouvelle conversation');
                $table->json('context')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'user_id', 'is_active']);
            });
        }

        // Copilot Messages
        if (!Schema::hasTable('copilot_messages')) {
            Schema::create('copilot_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('copilot_conversations')->onDelete('cascade');
                $table->enum('role', ['user', 'assistant', 'system'])->default('user');
                $table->text('content');
                $table->json('actions')->nullable();
                $table->json('context')->nullable();
                $table->string('intent')->nullable();
                $table->enum('feedback', ['positive', 'negative'])->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
            });
        }

        // Payment Reminders (for Collection Agent)
        if (!Schema::hasTable('payment_reminders')) {
            Schema::create('payment_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->unsignedTinyInteger('stage')->default(1);
                $table->enum('type', ['email', 'sms', 'call', 'letter'])->default('email');
                $table->timestamp('sent_at')->nullable();
                $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'responded'])->default('pending');
                $table->text('response')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'invoice_id', 'stage']);
                $table->index(['customer_id', 'status']);
            });
        }

        // AI Agent Logs
        if (!Schema::hasTable('ai_agent_logs')) {
            Schema::create('ai_agent_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('agent_name');
                $table->enum('status', ['running', 'completed', 'failed'])->default('running');
                $table->json('input_data')->nullable();
                $table->json('output_data')->nullable();
                $table->json('alerts_generated')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'agent_name', 'created_at']);
            });
        }

        // AI Insights Cache
        if (!Schema::hasTable('ai_insights')) {
            Schema::create('ai_insights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('type'); // churn_prediction, revenue_forecast, pricing_recommendation
                $table->json('data');
                $table->timestamp('valid_until');
                $table->timestamps();

                $table->unique(['tenant_id', 'type']);
                $table->index(['tenant_id', 'valid_until']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('ai_agent_logs');
        Schema::dropIfExists('payment_reminders');
        Schema::dropIfExists('copilot_messages');
        Schema::dropIfExists('copilot_conversations');
    }
};
