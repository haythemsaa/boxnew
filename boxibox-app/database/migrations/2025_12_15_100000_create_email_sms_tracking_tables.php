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
        // Email tracking table
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('tracking_id')->unique(); // UUID for tracking
            $table->string('recipient_email');
            $table->string('recipient_type')->default('lead'); // lead, prospect, customer
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('email_type'); // invoice, reminder, campaign, welcome, etc.
            $table->string('subject');
            $table->string('status')->default('sent'); // sent, delivered, opened, clicked, bounced, complained
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_type')->nullable(); // hard, soft
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->json('clicks')->nullable(); // Array of clicked links
            $table->json('metadata')->nullable();
            $table->string('provider')->nullable(); // mailgun, sendinblue, ses, etc.
            $table->string('provider_message_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'recipient_email']);
            $table->index(['tenant_id', 'recipient_type', 'recipient_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('tracking_id');
        });

        // Email link tracking table
        Schema::create('email_link_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_tracking_id')->constrained('email_tracking')->onDelete('cascade');
            $table->string('link_id')->unique(); // UUID for link tracking
            $table->string('original_url');
            $table->string('link_name')->nullable(); // CTA name, e.g., "View Invoice"
            $table->integer('click_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->json('click_details')->nullable(); // Array of click timestamps, IPs, etc.
            $table->timestamps();

            $table->index('link_id');
        });

        // SMS tracking table
        Schema::create('sms_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('tracking_id')->unique();
            $table->string('recipient_phone');
            $table->string('recipient_type')->default('lead'); // lead, prospect, customer
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('sms_type'); // reminder, campaign, notification, etc.
            $table->text('message');
            $table->string('status')->default('sent'); // sent, delivered, failed, replied
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->text('reply_message')->nullable();
            $table->string('provider')->nullable(); // twilio, vonage, ovh, etc.
            $table->string('provider_message_id')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'recipient_phone']);
            $table->index(['tenant_id', 'recipient_type', 'recipient_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('tracking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_link_tracking');
        Schema::dropIfExists('email_tracking');
        Schema::dropIfExists('sms_tracking');
    }
};
