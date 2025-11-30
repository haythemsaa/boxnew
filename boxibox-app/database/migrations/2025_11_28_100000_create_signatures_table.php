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
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Signature details
            $table->enum('type', ['contract', 'mandate'])->default('contract');
            $table->enum('status', ['pending', 'sent', 'viewed', 'signed', 'refused', 'expired'])->default('pending');
            $table->string('email_sent_to')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Document info
            $table->string('document_path')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->string('signature_token')->unique()->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Tracking
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['contract_id']);
            $table->index(['customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
