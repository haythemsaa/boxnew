<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiosk Devices
        if (!Schema::hasTable('kiosk_devices')) {
            Schema::create('kiosk_devices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');

                $table->string('name');
                $table->string('device_code')->unique(); // For pairing
                $table->string('location_description')->nullable();

                // Device info
                $table->string('device_type')->default('tablet'); // tablet, touchscreen, terminal
                $table->string('os')->nullable();
                $table->string('browser')->nullable();
                $table->string('screen_resolution')->nullable();

                // Status
                $table->boolean('is_active')->default(true);
                $table->boolean('is_online')->default(false);
                $table->datetime('last_heartbeat_at')->nullable();
                $table->string('current_ip')->nullable();

                // Configuration
                $table->string('language')->default('fr');
                $table->boolean('allow_new_rentals')->default(true);
                $table->boolean('allow_payments')->default(true);
                $table->boolean('allow_access_code_generation')->default(true);
                $table->boolean('show_prices')->default(true);
                $table->boolean('require_id_verification')->default(false);

                // Branding
                $table->string('logo_path')->nullable();
                $table->string('background_image_path')->nullable();
                $table->string('primary_color')->default('#3B82F6');
                $table->string('secondary_color')->default('#10B981');
                $table->text('welcome_message')->nullable();

                // Idle/screensaver
                $table->integer('idle_timeout_seconds')->default(120);
                $table->boolean('enable_screensaver')->default(true);
                $table->json('screensaver_images')->nullable();

                // Security
                $table->string('admin_pin')->nullable();
                $table->boolean('require_pin_for_settings')->default(true);

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
            });
        }

        // Kiosk Sessions
        if (!Schema::hasTable('kiosk_sessions')) {
            Schema::create('kiosk_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('kiosk_id')->constrained('kiosk_devices')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

                $table->string('session_token', 64)->unique();
                $table->datetime('started_at');
                $table->datetime('ended_at')->nullable();
                $table->integer('duration_seconds')->nullable();

                // Session type
                $table->enum('purpose', [
                    'browse',           // Just browsing
                    'new_rental',       // New customer rental
                    'payment',          // Existing customer payment
                    'access_code',      // Get access code
                    'support',          // Support request
                    'account'           // Account management
                ])->default('browse');

                // Outcome
                $table->enum('outcome', [
                    'completed',        // Successfully completed
                    'abandoned',        // Left without completing
                    'timeout',          // Session timed out
                    'error'             // Error occurred
                ])->nullable();

                // What was accomplished
                $table->boolean('viewed_boxes')->default(false);
                $table->boolean('started_rental')->default(false);
                $table->boolean('completed_rental')->default(false);
                $table->boolean('made_payment')->default(false);
                $table->boolean('generated_access_code')->default(false);

                // Conversion
                $table->foreignId('created_booking_id')->nullable();
                $table->foreignId('created_contract_id')->nullable();
                $table->foreignId('created_payment_id')->nullable();
                $table->decimal('transaction_amount', 10, 2)->nullable();

                // Device info
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();

                $table->timestamps();

                $table->index(['kiosk_id', 'started_at']);
                $table->index(['tenant_id', 'purpose', 'started_at']);
            });
        }

        // Kiosk Actions Log
        if (!Schema::hasTable('kiosk_action_logs')) {
            Schema::create('kiosk_action_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('kiosk_sessions')->onDelete('cascade');
                $table->foreignId('kiosk_id')->constrained('kiosk_devices')->onDelete('cascade');

                $table->string('action'); // page_view, box_selected, form_started, payment_initiated, etc.
                $table->string('page')->nullable();
                $table->json('data')->nullable();
                $table->integer('time_on_previous_page_seconds')->nullable();

                $table->timestamps();

                $table->index(['session_id', 'created_at']);
            });
        }

        // Kiosk Analytics (daily)
        if (!Schema::hasTable('kiosk_analytics')) {
            Schema::create('kiosk_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('kiosk_id')->constrained('kiosk_devices')->onDelete('cascade');
                $table->date('date');

                // Sessions
                $table->integer('total_sessions')->default(0);
                $table->integer('unique_visitors')->default(0);
                $table->integer('avg_session_duration_seconds')->nullable();

                // By purpose
                $table->integer('browse_sessions')->default(0);
                $table->integer('rental_sessions')->default(0);
                $table->integer('payment_sessions')->default(0);
                $table->integer('access_code_sessions')->default(0);

                // Outcomes
                $table->integer('completed_sessions')->default(0);
                $table->integer('abandoned_sessions')->default(0);
                $table->integer('timeout_sessions')->default(0);

                // Conversions
                $table->integer('rentals_started')->default(0);
                $table->integer('rentals_completed')->default(0);
                $table->decimal('rental_conversion_rate', 5, 2)->nullable();
                $table->integer('payments_completed')->default(0);
                $table->integer('access_codes_generated')->default(0);

                // Revenue
                $table->decimal('total_revenue', 12, 2)->default(0);
                $table->decimal('rental_revenue', 12, 2)->default(0);
                $table->decimal('payment_revenue', 12, 2)->default(0);

                // Peak hours (JSON array of hourly counts)
                $table->json('hourly_sessions')->nullable();

                $table->timestamps();

                $table->unique(['kiosk_id', 'date']);
                $table->index(['tenant_id', 'date']);
            });
        }

        // Kiosk Maintenance/Issues Log
        if (!Schema::hasTable('kiosk_issues')) {
            Schema::create('kiosk_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kiosk_id')->constrained('kiosk_devices')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');

                $table->enum('type', [
                    'offline',
                    'hardware',
                    'software',
                    'payment_issue',
                    'printer_issue',
                    'connectivity',
                    'other'
                ]);

                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');

                $table->datetime('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('resolution_notes')->nullable();

                $table->timestamps();

                $table->index(['kiosk_id', 'status']);
                $table->index(['tenant_id', 'status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_issues');
        Schema::dropIfExists('kiosk_analytics');
        Schema::dropIfExists('kiosk_action_logs');
        Schema::dropIfExists('kiosk_sessions');
        Schema::dropIfExists('kiosk_devices');
    }
};
