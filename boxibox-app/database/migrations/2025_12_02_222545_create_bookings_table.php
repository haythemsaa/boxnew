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
        // Booking settings per tenant
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(true);
            $table->string('booking_url_slug')->unique()->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#1E40AF');
            $table->text('welcome_message')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->boolean('require_deposit')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('deposit_percentage', 5, 2)->default(0);
            $table->integer('min_rental_days')->default(30);
            $table->integer('max_advance_booking_days')->default(90);
            $table->boolean('auto_confirm')->default(false);
            $table->boolean('require_id_verification')->default(true);
            $table->boolean('allow_promo_codes')->default(true);
            $table->json('available_payment_methods')->nullable();
            $table->json('business_hours')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('custom_css')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'site_id']);
        });

        // Bookings/Reservations
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('booking_number')->unique();

            // Customer info (for non-registered customers)
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->string('customer_country')->default('FR');
            $table->string('customer_company')->nullable();
            $table->string('customer_vat_number')->nullable();

            // Booking details
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('rental_type', ['fixed', 'month_to_month'])->default('month_to_month');
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->nullable();

            // Status
            $table->enum('status', [
                'pending',           // Waiting for confirmation
                'confirmed',         // Confirmed by tenant
                'deposit_paid',      // Deposit received
                'active',            // Contract started
                'completed',         // Contract ended normally
                'cancelled',         // Cancelled by customer
                'rejected',          // Rejected by tenant
                'expired'            // Booking expired (not confirmed in time)
            ])->default('pending');

            // Source tracking
            $table->enum('source', [
                'website',           // From public booking page
                'widget',            // From embedded widget
                'api',               // From API integration
                'manual',            // Created manually by staff
                'import'             // Imported from external system
            ])->default('website');
            $table->string('source_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('promo_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('id_document_path')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Conversion to contract
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['site_id', 'status']);
            $table->index(['box_id', 'start_date']);
            $table->index('customer_email');
        });

        // Booking status history
        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // Promo codes for bookings
        Schema::create('booking_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed', 'free_months'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_rental_amount', 10, 2)->nullable();
            $table->integer('min_rental_months')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('first_time_only')->default(false);
            $table->json('applicable_box_types')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Booking payments
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'first_month', 'full_payment'])->default('deposit');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('payment_data')->nullable();
            $table->timestamps();
        });

        // Widget configurations
        Schema::create('booking_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->uuid('widget_key')->unique();
            $table->string('name');
            $table->string('allowed_domain')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->enum('widget_type', ['full', 'compact', 'button', 'inline'])->default('full');
            $table->json('style_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('views_count')->default(0);
            $table->integer('bookings_count')->default(0);
            $table->timestamps();
        });

        // API keys for external integrations
        Schema::create('booking_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('api_key')->unique();
            $table->string('api_secret');
            $table->json('permissions')->nullable();
            $table->json('ip_whitelist')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->integer('requests_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_api_keys');
        Schema::dropIfExists('booking_widgets');
        Schema::dropIfExists('booking_payments');
        Schema::dropIfExists('booking_promo_codes');
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_settings');
    }
};
