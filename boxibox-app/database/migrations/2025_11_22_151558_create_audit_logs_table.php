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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // Store user name for historical record
            $table->string('auditable_type'); // Model type (Contract, Invoice, etc.)
            $table->unsignedBigInteger('auditable_id'); // Model ID
            $table->string('event'); // created, updated, deleted, restored
            $table->json('old_values')->nullable(); // Original values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->text('description')->nullable(); // Human-readable description
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
