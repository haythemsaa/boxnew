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
        // Tenant Email Providers
        Schema::create('tenant_email_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider'); // mailgun, sendgrid, ses, smtp, postmark
            $table->json('credentials')->nullable(); // Encrypted credentials
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('settings')->nullable(); // Provider-specific settings
            $table->unsignedInteger('emails_sent_count')->default(0);
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Tenant SMS Providers
        Schema::create('tenant_sms_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider'); // twilio, vonage, messagebird, ovh
            $table->json('credentials')->nullable(); // Encrypted credentials
            $table->string('sender_id')->nullable(); // Sender name or number
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('settings')->nullable(); // Provider-specific settings
            $table->unsignedInteger('sms_sent_count')->default(0);
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Email/SMS sending logs linked to providers
        Schema::create('email_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_email_provider_id')->nullable()->constrained()->onDelete('set null');
            $table->string('message_id')->nullable(); // Provider message ID
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject');
            $table->string('status'); // queued, sent, delivered, bounced, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_type')->nullable(); // hard, soft
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('message_id');
        });

        Schema::create('sms_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_sms_provider_id')->nullable()->constrained()->onDelete('set null');
            $table->string('message_id')->nullable(); // Provider message ID
            $table->string('to_phone');
            $table->text('message');
            $table->unsignedInteger('segments')->default(1);
            $table->string('status'); // queued, sent, delivered, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_send_logs');
        Schema::dropIfExists('email_send_logs');
        Schema::dropIfExists('tenant_sms_providers');
        Schema::dropIfExists('tenant_email_providers');
    }
};
