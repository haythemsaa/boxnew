<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Waitlist Management System - Liste d'attente pour boxes populaires
     */
    public function up(): void
    {
        // Main waitlist entries
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

            // Customer info (for non-registered)
            $table->string('customer_email');
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_phone')->nullable();

            // Preferences
            $table->decimal('min_size', 8, 2)->nullable();
            $table->decimal('max_size', 8, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->boolean('needs_climate_control')->default(false);
            $table->boolean('needs_ground_floor')->default(false);
            $table->boolean('needs_drive_up')->default(false);
            $table->date('desired_start_date')->nullable();
            $table->text('notes')->nullable();

            // Status & Priority
            $table->enum('status', [
                'active',      // Actively waiting
                'notified',    // Box available, notified
                'converted',   // Converted to booking
                'expired',     // Expired (didn't respond)
                'cancelled'    // Cancelled by customer
            ])->default('active');
            $table->integer('priority')->default(0);
            $table->integer('position')->nullable();

            // Tracking
            $table->string('source')->default('website');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('converted_booking_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'site_id', 'status']);
            $table->index(['box_id', 'status']);
            $table->index('customer_email');
        });

        // Waitlist notifications log
        Schema::create('waitlist_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waitlist_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->enum('channel', ['email', 'sms', 'push'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed', 'clicked', 'converted'])->default('pending');
            $table->string('message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Waitlist settings per site
        Schema::create('waitlist_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(true);
            $table->integer('max_entries_per_box')->default(10);
            $table->integer('notification_expiry_hours')->default(48);
            $table->integer('max_notifications_per_entry')->default(3);
            $table->boolean('auto_notify')->default(true);
            $table->boolean('priority_by_date')->default(true);
            $table->text('notification_email_template')->nullable();
            $table->text('notification_sms_template')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_settings');
        Schema::dropIfExists('waitlist_notifications');
        Schema::dropIfExists('waitlist_entries');
    }
};
