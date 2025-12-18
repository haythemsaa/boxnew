<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment retry configurations per tenant
        Schema::create('payment_retry_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Retry strategy
            $table->integer('max_retries')->default(4);
            $table->json('retry_intervals'); // Days between retries: [1, 3, 7, 14]
            $table->json('retry_times'); // Best times to retry: ["09:00", "14:00", "18:00"]

            // Smart timing
            $table->boolean('use_smart_timing')->default(true);
            $table->boolean('avoid_weekends')->default(true);
            $table->boolean('avoid_holidays')->default(true);

            // Notification settings
            $table->boolean('notify_customer_before')->default(true);
            $table->integer('notify_hours_before')->default(24);
            $table->boolean('notify_customer_after_failure')->default(true);
            $table->boolean('notify_customer_after_success')->default(true);
            $table->boolean('notify_admin_after_all_failures')->default(true);

            // Card update
            $table->boolean('allow_card_update')->default(true);
            $table->integer('card_update_link_expiry_hours')->default(72);

            // Actions on final failure
            $table->string('final_failure_action')->default('suspend'); // 'suspend', 'downgrade', 'none'
            $table->integer('grace_period_days')->default(7);

            // Dunning messaging
            $table->json('escalation_messages')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('tenant_id');
        });

        // Individual payment retry attempts
        Schema::create('payment_retry_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Original payment info
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_method_id')->nullable();

            // Retry status
            $table->enum('status', ['pending', 'scheduled', 'processing', 'succeeded', 'failed', 'cancelled', 'card_updated'])->default('pending');
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(4);

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();

            // Failure tracking
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->string('decline_code')->nullable();

            // Success tracking
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->timestamp('succeeded_at')->nullable();

            // Card update link
            $table->string('card_update_token')->nullable();
            $table->timestamp('card_update_token_expires_at')->nullable();
            $table->boolean('card_was_updated')->default(false);

            // Notifications sent
            $table->json('notifications_sent')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['customer_id', 'status']);
        });

        // Payment failure analytics
        Schema::create('payment_failure_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('retry_attempt_id')->constrained('payment_retry_attempts')->onDelete('cascade');

            // Failure details
            $table->string('failure_reason'); // 'insufficient_funds', 'card_declined', 'expired_card', etc.
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();

            // Timing info
            $table->integer('day_of_week');
            $table->integer('hour_of_day');
            $table->date('date');
            $table->boolean('is_first_of_month');
            $table->boolean('is_end_of_month');

            // Customer context
            $table->integer('customer_tenure_days')->nullable(); // How long they've been a customer
            $table->integer('previous_successful_payments')->nullable();
            $table->integer('previous_failed_payments')->nullable();

            // Recovery outcome
            $table->boolean('eventually_recovered')->default(false);
            $table->integer('recovery_attempt_number')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'failure_reason']);
            $table->index(['day_of_week', 'hour_of_day']);
        });

        // Best retry times (learned from analytics)
        Schema::create('payment_optimal_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->integer('day_of_week');
            $table->integer('hour');
            $table->decimal('success_rate', 5, 2);
            $table->integer('sample_size');

            $table->timestamps();

            $table->unique(['tenant_id', 'day_of_week', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_optimal_times');
        Schema::dropIfExists('payment_failure_analytics');
        Schema::dropIfExists('payment_retry_attempts');
        Schema::dropIfExists('payment_retry_configs');
    }
};
