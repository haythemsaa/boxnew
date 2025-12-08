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
        // Webhook Endpoints (for Zapier, Make, etc.)
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret_key')->nullable();
            $table->json('events'); // ['contract.created', 'invoice.paid', etc.]
            $table->json('headers')->nullable(); // Custom headers
            $table->boolean('is_active')->default(true);
            $table->boolean('verify_ssl')->default(true);
            $table->integer('retry_count')->default(3);
            $table->integer('timeout')->default(30); // seconds
            $table->timestamp('last_triggered_at')->nullable();
            $table->string('last_status')->nullable(); // success, failed
            $table->text('last_error')->nullable();
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('successful_calls')->default(0);
            $table->unsignedInteger('failed_calls')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });

        // Webhook Delivery Logs
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // contract.created, invoice.paid, etc.
            $table->string('event_id'); // unique event identifier
            $table->json('payload'); // Data sent to webhook
            $table->integer('attempt')->default(1);
            $table->string('status'); // pending, success, failed
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->float('duration')->nullable(); // in seconds
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
            $table->index('event_id');
            $table->index('created_at');
        });

        // API Keys for external integrations
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->string('secret', 64);
            $table->json('permissions'); // ['read:contracts', 'write:invoices', etc.]
            $table->json('ip_whitelist')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('total_requests')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index('key');
        });

        // Integration Connections (Zapier, Make, etc.)
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // zapier, make, n8n, custom
            $table->string('name');
            $table->string('connection_id')->nullable(); // Provider-specific ID
            $table->json('config')->nullable();
            $table->json('credentials')->nullable(); // Encrypted
            $table->string('status')->default('pending'); // pending, connected, error
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
