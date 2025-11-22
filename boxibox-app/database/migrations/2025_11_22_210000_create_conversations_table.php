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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('set null');

            // Message data
            $table->json('messages'); // Array of {role: 'user'|'assistant', content: string, timestamp}

            // Intent detection
            $table->string('detected_intent')->nullable(); // pricing, sizing, booking, visit
            $table->json('extracted_entities')->nullable(); // {email, phone, size, budget}

            // Status
            $table->enum('status', ['active', 'converted', 'abandoned', 'archived'])->default('active');
            $table->boolean('lead_created')->default(false);

            // Metadata
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('referrer')->nullable();

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('customer_id');
            $table->index('lead_id');
            $table->index('status');
            $table->index('detected_intent');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
