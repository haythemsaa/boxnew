<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Call Tracking Settings
        if (!Schema::hasTable('call_tracking_settings')) {
            Schema::create('call_tracking_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

                $table->boolean('is_enabled')->default(false);
                $table->string('provider')->default('twilio'); // twilio, callrail, ringcentral
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->string('account_sid')->nullable();

                // Recording settings
                $table->boolean('record_calls')->default(true);
                $table->integer('recording_retention_days')->default(90);
                $table->boolean('transcribe_calls')->default(false);

                // Notifications
                $table->boolean('notify_missed_calls')->default(true);
                $table->boolean('notify_after_hours')->default(true);
                $table->string('notification_email')->nullable();
                $table->string('notification_phone')->nullable();

                // Business hours
                $table->json('business_hours')->nullable();
                $table->string('timezone')->default('Europe/Paris');

                // Auto-response
                $table->boolean('enable_voicemail')->default(true);
                $table->text('voicemail_greeting')->nullable();
                $table->boolean('enable_sms_autoresponse')->default(false);
                $table->text('sms_autoresponse_message')->nullable();

                $table->timestamps();

                $table->unique('tenant_id');
            });
        }

        // Tracking Phone Numbers
        if (!Schema::hasTable('tracking_numbers')) {
            Schema::create('tracking_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

                $table->string('phone_number')->unique();
                $table->string('friendly_name')->nullable();
                $table->string('forward_to'); // Destination number
                $table->boolean('is_active')->default(true);

                // Source tracking
                $table->string('source')->nullable(); // website, google_ads, facebook, billboard, etc.
                $table->string('medium')->nullable();
                $table->string('campaign')->nullable();

                // Usage type
                $table->enum('number_type', ['local', 'toll_free', 'mobile'])->default('local');
                $table->boolean('sms_enabled')->default(true);
                $table->boolean('mms_enabled')->default(false);

                // Provider info
                $table->string('provider_number_sid')->nullable();
                $table->decimal('monthly_cost', 8, 2)->nullable();

                // Stats (cached)
                $table->integer('total_calls')->default(0);
                $table->integer('total_sms')->default(0);
                $table->datetime('last_call_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->index('source');
            });
        }

        // Call Records
        if (!Schema::hasTable('call_records')) {
            Schema::create('call_records', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('tracking_number_id')->constrained('tracking_numbers')->onDelete('cascade');
                $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

                // Call details
                $table->string('call_sid')->unique()->nullable();
                $table->string('from_number');
                $table->string('to_number');
                $table->enum('direction', ['inbound', 'outbound'])->default('inbound');

                // Timing
                $table->datetime('started_at');
                $table->datetime('answered_at')->nullable();
                $table->datetime('ended_at')->nullable();
                $table->integer('ring_duration_seconds')->nullable();
                $table->integer('talk_duration_seconds')->nullable();
                $table->integer('total_duration_seconds')->nullable();

                // Status
                $table->enum('status', [
                    'ringing',
                    'in_progress',
                    'completed',
                    'busy',
                    'no_answer',
                    'failed',
                    'cancelled',
                    'voicemail'
                ])->default('ringing');

                // Recording
                $table->boolean('was_recorded')->default(false);
                $table->string('recording_url')->nullable();
                $table->string('recording_path')->nullable();
                $table->integer('recording_duration_seconds')->nullable();

                // Transcription
                $table->boolean('was_transcribed')->default(false);
                $table->text('transcription')->nullable();
                $table->decimal('transcription_confidence', 5, 2)->nullable();

                // AI Analysis (optional)
                $table->string('sentiment')->nullable(); // positive, neutral, negative
                $table->json('keywords')->nullable();
                $table->string('call_intent')->nullable(); // inquiry, booking, support, complaint
                $table->decimal('lead_score', 5, 2)->nullable();

                // Source tracking
                $table->string('source')->nullable();
                $table->string('medium')->nullable();
                $table->string('campaign')->nullable();

                // Caller info
                $table->string('caller_city')->nullable();
                $table->string('caller_region')->nullable();
                $table->string('caller_country')->nullable();

                // Staff handling
                $table->foreignId('answered_by')->nullable()->constrained('users')->onDelete('set null');
                $table->boolean('was_transferred')->default(false);
                $table->string('transferred_to')->nullable();

                // Follow-up
                $table->boolean('requires_callback')->default(false);
                $table->datetime('callback_scheduled_at')->nullable();
                $table->boolean('callback_completed')->default(false);
                $table->text('notes')->nullable();

                // Conversion tracking
                $table->boolean('converted')->default(false);
                $table->foreignId('converted_booking_id')->nullable();
                $table->foreignId('converted_contract_id')->nullable();
                $table->decimal('converted_value', 10, 2)->nullable();

                $table->json('raw_data')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status', 'started_at']);
                $table->index(['from_number']);
                $table->index(['tracking_number_id', 'started_at']);
            });
        }

        // SMS Records
        if (!Schema::hasTable('sms_records')) {
            Schema::create('sms_records', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('tracking_number_id')->nullable()->constrained('tracking_numbers')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

                $table->string('message_sid')->nullable();
                $table->string('from_number');
                $table->string('to_number');
                $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
                $table->text('body');
                $table->json('media_urls')->nullable();
                $table->integer('num_segments')->default(1);

                $table->enum('status', [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'failed',
                    'received',
                    'read'
                ])->default('queued');

                $table->string('error_code')->nullable();
                $table->string('error_message')->nullable();

                $table->decimal('cost', 8, 4)->nullable();

                // Source
                $table->string('source')->nullable();
                $table->string('campaign')->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'direction', 'created_at']);
                $table->index(['from_number', 'to_number']);
            });
        }

        // Call Analytics (daily aggregation)
        if (!Schema::hasTable('call_analytics')) {
            Schema::create('call_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
                $table->date('date');
                $table->string('source')->nullable();

                // Call metrics
                $table->integer('total_calls')->default(0);
                $table->integer('answered_calls')->default(0);
                $table->integer('missed_calls')->default(0);
                $table->integer('voicemail_calls')->default(0);
                $table->integer('unique_callers')->default(0);

                // Duration
                $table->integer('total_talk_seconds')->default(0);
                $table->integer('avg_talk_seconds')->nullable();
                $table->integer('avg_ring_seconds')->nullable();
                $table->integer('avg_wait_seconds')->nullable();

                // Performance
                $table->decimal('answer_rate', 5, 2)->nullable();
                $table->decimal('avg_response_time_seconds', 10, 2)->nullable();

                // Conversions
                $table->integer('converted_calls')->default(0);
                $table->decimal('conversion_rate', 5, 2)->nullable();
                $table->decimal('total_revenue', 12, 2)->default(0);

                // SMS
                $table->integer('total_sms_in')->default(0);
                $table->integer('total_sms_out')->default(0);

                // Costs
                $table->decimal('total_cost', 10, 2)->default(0);

                $table->timestamps();

                $table->unique(['tenant_id', 'site_id', 'date', 'source'], 'call_analytics_unique');
                $table->index(['tenant_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('call_analytics');
        Schema::dropIfExists('sms_records');
        Schema::dropIfExists('call_records');
        Schema::dropIfExists('tracking_numbers');
        Schema::dropIfExists('call_tracking_settings');
    }
};
