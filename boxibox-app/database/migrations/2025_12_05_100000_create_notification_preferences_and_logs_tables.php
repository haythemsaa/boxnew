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
        // Notification Preferences table - stores tenant notification settings
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Global channel toggles
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(false);

            // Invoice notifications
            $table->boolean('invoice_created_email')->default(true);
            $table->boolean('invoice_created_sms')->default(false);
            $table->boolean('invoice_created_push')->default(false);

            // Payment notifications
            $table->boolean('payment_received_email')->default(true);
            $table->boolean('payment_received_sms')->default(false);
            $table->boolean('payment_received_push')->default(false);

            // Payment reminders
            $table->boolean('payment_reminder_email')->default(true);
            $table->boolean('payment_reminder_sms')->default(false);
            $table->boolean('payment_reminder_push')->default(false);

            // Contract notifications
            $table->boolean('contract_expiring_email')->default(true);
            $table->boolean('contract_expiring_sms')->default(false);
            $table->boolean('contract_expiring_push')->default(false);

            // Access alerts
            $table->boolean('access_alert_email')->default(true);
            $table->boolean('access_alert_sms')->default(true);
            $table->boolean('access_alert_push')->default(true);

            // IoT alerts
            $table->boolean('iot_alert_email')->default(true);
            $table->boolean('iot_alert_sms')->default(false);
            $table->boolean('iot_alert_push')->default(true);

            // Booking notifications
            $table->boolean('booking_confirmed_email')->default(true);
            $table->boolean('booking_confirmed_sms')->default(false);
            $table->boolean('booking_confirmed_push')->default(false);

            // Welcome notifications
            $table->boolean('welcome_email')->default(true);
            $table->boolean('welcome_sms')->default(false);
            $table->boolean('welcome_push')->default(false);

            $table->timestamps();

            $table->unique('tenant_id');
        });

        // Notification Logs table - tracks all sent notifications
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->string('type')->index(); // invoice_created, payment_received, etc.
            $table->string('channel')->index(); // email, sms, push
            $table->string('recipient'); // email address, phone number, device token
            $table->string('subject')->nullable();
            $table->text('body')->nullable();

            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();

            // Polymorphic relation to the entity that triggered the notification
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_preferences');
    }
};
