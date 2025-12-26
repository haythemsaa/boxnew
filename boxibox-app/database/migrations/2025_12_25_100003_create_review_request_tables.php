<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Google Review Request System - Demandes d'avis automatiques
     */
    public function up(): void
    {
        // Review requests
        if (!Schema::hasTable('review_requests')) {
        Schema::create('review_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('booking_id')->nullable();

            // Customer info
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();

            // Request details
            $table->enum('trigger', [
                'move_in',           // After move-in
                'contract_renewal',  // After renewal
                'support_resolved',  // After support ticket resolved
                'manual',            // Manually triggered
                'scheduled'          // Scheduled campaign
            ])->default('move_in');

            $table->integer('delay_days')->default(7);
            $table->datetime('scheduled_at');
            $table->datetime('sent_at')->nullable();

            // Status
            $table->enum('status', [
                'pending',       // Waiting to be sent
                'sent',          // Email/SMS sent
                'clicked',       // Link clicked
                'reviewed',      // Review submitted
                'skipped',       // Customer skipped
                'unsubscribed',  // Customer opted out
                'failed'         // Delivery failed
            ])->default('pending');

            // Tracking
            $table->enum('channel', ['email', 'sms', 'both'])->default('email');
            $table->string('email_message_id')->nullable();
            $table->string('sms_message_id')->nullable();
            $table->string('tracking_token')->unique();
            $table->datetime('clicked_at')->nullable();
            $table->datetime('reviewed_at')->nullable();

            // Review details (if captured)
            $table->string('review_platform')->nullable();
            $table->string('external_review_id')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review_text')->nullable();

            // Retry logic
            $table->integer('send_attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->datetime('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'scheduled_at']);
            $table->index('customer_email');
            $table->index('tracking_token');
        });
        }

        // Review request settings per site
        if (!Schema::hasTable('review_request_settings')) {
        Schema::create('review_request_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(true);

            // Triggers
            $table->boolean('trigger_on_move_in')->default(true);
            $table->integer('move_in_delay_days')->default(7);
            $table->boolean('trigger_on_renewal')->default(false);
            $table->integer('renewal_delay_days')->default(3);
            $table->boolean('trigger_on_support_resolved')->default(false);

            // Channels
            $table->boolean('send_email')->default(true);
            $table->boolean('send_sms')->default(false);

            // Review platforms
            $table->string('google_place_id')->nullable();
            $table->string('google_review_url')->nullable();
            $table->string('trustpilot_url')->nullable();
            $table->string('facebook_page_url')->nullable();
            $table->string('primary_platform')->default('google');

            // Templates
            $table->string('email_subject')->nullable();
            $table->text('email_template')->nullable();
            $table->text('sms_template')->nullable();

            // Limits
            $table->integer('max_requests_per_customer')->default(2);
            $table->integer('min_days_between_requests')->default(90);
            $table->boolean('skip_if_negative_interaction')->default(true);

            // Incentives (optional)
            $table->boolean('offer_incentive')->default(false);
            $table->string('incentive_type')->nullable();
            $table->decimal('incentive_value', 10, 2)->nullable();
            $table->text('incentive_description')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'site_id']);
        });
        }

        // Customer review opt-outs
        if (!Schema::hasTable('review_opt_outs')) {
        Schema::create('review_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('customer_email');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->datetime('opted_out_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'customer_email']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_opt_outs');
        Schema::dropIfExists('review_request_settings');
        Schema::dropIfExists('review_requests');
    }
};
