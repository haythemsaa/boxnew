<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google Reserve Integration Settings
        if (!Schema::hasTable('google_reserve_settings')) {
            Schema::create('google_reserve_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

                $table->boolean('is_enabled')->default(false);
                $table->string('merchant_id')->nullable();
                $table->string('place_id')->nullable();
                $table->string('api_key')->nullable();
                $table->string('webhook_secret')->nullable();

                // Availability settings
                $table->json('available_days')->nullable(); // [0,1,2,3,4,5,6]
                $table->time('opening_time')->default('08:00');
                $table->time('closing_time')->default('19:00');
                $table->integer('slot_duration_minutes')->default(30);
                $table->integer('max_advance_days')->default(30);
                $table->integer('min_advance_hours')->default(2);

                // Auto-confirmation
                $table->boolean('auto_confirm')->default(true);
                $table->boolean('require_deposit')->default(false);
                $table->decimal('deposit_amount', 10, 2)->nullable();

                // Notifications
                $table->boolean('notify_on_booking')->default(true);
                $table->boolean('send_customer_confirmation')->default(true);
                $table->boolean('send_reminder')->default(true);
                $table->integer('reminder_hours_before')->default(24);

                $table->timestamps();

                $table->unique(['tenant_id', 'site_id']);
            });
        }

        // Google Reserve Bookings
        if (!Schema::hasTable('google_reserve_bookings')) {
            Schema::create('google_reserve_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('box_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('booking_id')->nullable(); // Link to internal booking

                // Google Reserve data
                $table->string('google_booking_id')->unique();
                $table->string('google_merchant_id')->nullable();

                // Customer info from Google
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone')->nullable();

                // Booking details
                $table->date('booking_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('service_type')->default('visit'); // visit, consultation, move_in
                $table->decimal('box_size_requested', 8, 2)->nullable();
                $table->text('customer_notes')->nullable();

                // Status
                $table->enum('status', [
                    'pending',
                    'confirmed',
                    'cancelled_by_customer',
                    'cancelled_by_merchant',
                    'no_show',
                    'completed',
                    'converted' // Became a contract
                ])->default('pending');

                $table->datetime('confirmed_at')->nullable();
                $table->datetime('cancelled_at')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->datetime('completed_at')->nullable();

                // Conversion tracking
                $table->foreignId('converted_contract_id')->nullable();
                $table->decimal('converted_value', 10, 2)->nullable();

                // Sync info
                $table->datetime('last_synced_at')->nullable();
                $table->json('google_raw_data')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status', 'booking_date']);
                $table->index('google_booking_id');
            });
        }

        // Google Reserve Availability Slots
        if (!Schema::hasTable('google_reserve_slots')) {
            Schema::create('google_reserve_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');

                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('max_bookings')->default(1);
                $table->integer('current_bookings')->default(0);
                $table->boolean('is_available')->default(true);
                $table->boolean('is_blocked')->default(false);
                $table->string('block_reason')->nullable();

                $table->timestamps();

                $table->unique(['site_id', 'date', 'start_time']);
                $table->index(['site_id', 'date', 'is_available']);
            });
        }

        // Sync logs
        if (!Schema::hasTable('google_reserve_sync_logs')) {
            Schema::create('google_reserve_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('sync_type'); // availability, bookings, merchants
                $table->enum('direction', ['push', 'pull'])->default('pull');
                $table->enum('status', ['started', 'success', 'partial', 'failed'])->default('started');
                $table->integer('records_processed')->default(0);
                $table->integer('records_created')->default(0);
                $table->integer('records_updated')->default(0);
                $table->integer('errors_count')->default(0);
                $table->json('error_details')->nullable();
                $table->datetime('started_at');
                $table->datetime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('google_reserve_sync_logs');
        Schema::dropIfExists('google_reserve_slots');
        Schema::dropIfExists('google_reserve_bookings');
        Schema::dropIfExists('google_reserve_settings');
    }
};
