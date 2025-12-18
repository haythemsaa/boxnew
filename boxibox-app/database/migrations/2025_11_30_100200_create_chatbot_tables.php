<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuration du chatbot par tenant
        Schema::create('chatbot_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name')->default('BoxBot');
            $table->string('avatar_url')->nullable();
            $table->string('welcome_message')->default('Bonjour ! Comment puis-je vous aider ?');
            $table->text('system_prompt')->nullable();

            // Apparence
            $table->string('primary_color')->default('#4f46e5');
            $table->string('position')->default('bottom-right'); // bottom-right, bottom-left
            $table->boolean('show_on_mobile')->default(true);

            // Fonctionnalités activées
            $table->boolean('can_book')->default(true);
            $table->boolean('can_quote')->default(true);
            $table->boolean('can_check_availability')->default(true);
            $table->boolean('can_answer_faq')->default(true);
            $table->boolean('can_transfer_to_human')->default(true);

            // Horaires de disponibilité humaine
            $table->json('human_availability')->nullable(); // {"mon": {"start": "09:00", "end": "18:00"}, ...}
            $table->string('transfer_email')->nullable();
            $table->string('transfer_phone')->nullable();

            // Langues supportées
            $table->json('languages')->nullable(); // ["fr", "en", "es", "de"]
            $table->string('default_language')->default('fr');

            // Intégrations
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->string('whatsapp_api_key')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Base de connaissances FAQ
        Schema::create('chatbot_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('category')->nullable();
            $table->string('question');
            $table->text('answer');
            $table->json('keywords')->nullable(); // Pour améliorer la recherche
            $table->string('language')->default('fr');

            $table->integer('usage_count')->default(0);
            $table->decimal('helpfulness_score', 3, 2)->default(0); // Score basé sur feedback

            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'language', 'is_active']);
        });

        // Conversations chatbot
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('session_id')->unique();
            $table->string('channel')->default('web'); // web, whatsapp, widget
            $table->string('language')->default('fr');

            // Informations visiteur
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();

            // État de la conversation
            $table->enum('status', ['active', 'waiting_human', 'transferred', 'resolved', 'abandoned'])->default('active');
            $table->boolean('is_human_takeover')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('transferred_at')->nullable();

            // Contexte
            $table->json('context')->nullable(); // {"intent": "booking", "box_size": "10m2", ...}
            $table->string('current_intent')->nullable();

            // Métriques
            $table->integer('message_count')->default(0);
            $table->decimal('satisfaction_score', 3, 2)->nullable();
            $table->boolean('converted_to_lead')->default(false);
            $table->boolean('converted_to_booking')->default(false);

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['session_id']);
        });

        // Messages de conversation
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();

            $table->enum('sender_type', ['bot', 'user', 'agent']);
            $table->foreignId('agent_id')->nullable()->constrained('users');

            $table->text('content');
            $table->enum('content_type', ['text', 'image', 'button', 'carousel', 'quick_reply', 'form'])->default('text');
            $table->json('attachments')->nullable();
            $table->json('quick_replies')->nullable();
            $table->json('buttons')->nullable();

            // Pour les réponses du bot
            $table->string('intent_detected')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->foreignId('knowledge_base_id')->nullable()->constrained('chatbot_knowledge_base');

            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        // Intentions et entrainement
        Schema::create('chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // booking, pricing, availability, faq, support
            $table->string('display_name');
            $table->text('description')->nullable();

            $table->json('training_phrases')->nullable(); // Phrases d'exemple
            $table->json('responses')->nullable(); // Réponses possibles
            $table->json('actions')->nullable(); // Actions à déclencher

            $table->boolean('requires_human')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            $table->timestamps();
        });

        // Suggestions de taille de box (calculateur conversationnel)
        Schema::create('chatbot_size_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();

            $table->json('items_selected')->nullable(); // [{"name": "lit double", "volume": 2.5}, ...]
            $table->decimal('total_volume', 8, 2);
            $table->decimal('recommended_size', 8, 2); // m²
            $table->string('recommended_box_type')->nullable();

            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->boolean('converted_to_booking')->default(false);

            $table->timestamps();
        });

        // Analytics chatbot
        Schema::create('chatbot_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('analytics_date');

            $table->integer('total_conversations')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_received')->default(0);

            $table->integer('conversations_resolved_by_bot')->default(0);
            $table->integer('conversations_transferred')->default(0);
            $table->integer('conversations_abandoned')->default(0);

            $table->integer('leads_generated')->default(0);
            $table->integer('bookings_generated')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            $table->decimal('avg_response_time_seconds', 8, 2)->nullable();
            $table->decimal('avg_satisfaction_score', 3, 2)->nullable();
            $table->decimal('bot_resolution_rate', 5, 2)->default(0);

            $table->json('top_intents')->nullable();
            $table->json('top_unanswered_questions')->nullable();

            $table->timestamps();
            $table->unique(['tenant_id', 'analytics_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_analytics');
        Schema::dropIfExists('chatbot_size_recommendations');
        Schema::dropIfExists('chatbot_intents');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('chatbot_knowledge_base');
        Schema::dropIfExists('chatbot_configurations');
    }
};
