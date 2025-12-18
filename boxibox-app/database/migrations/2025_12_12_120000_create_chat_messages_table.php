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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id', 50)->index();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for retrieving conversation history
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id', 50)->unique();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active, closed, handed_off
            $table->string('channel')->default('web'); // web, widget, api
            $table->integer('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('chatbot_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('intent')->nullable();
            $table->integer('total_messages')->default(0);
            $table->integer('faq_responses')->default(0);
            $table->integer('ai_responses')->default(0);
            $table->integer('handoffs')->default(0);
            $table->decimal('avg_confidence', 5, 2)->default(0);
            $table->integer('unique_conversations')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date', 'intent']);
            $table->index(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_analytics');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_messages');
    }
};
