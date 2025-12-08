<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catégories d'items pour le calculateur
        if (!Schema::hasTable('calculator_categories')) {
            Schema::create('calculator_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_global')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Items prédéfinis avec volumes
        if (!Schema::hasTable('calculator_items')) {
            Schema::create('calculator_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('calculator_categories')->cascadeOnDelete();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->decimal('volume_m3', 6, 2);
                $table->decimal('width_m', 4, 2)->nullable();
                $table->decimal('height_m', 4, 2)->nullable();
                $table->decimal('depth_m', 4, 2)->nullable();
                $table->boolean('is_stackable')->default(true);
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Calculs effectués par les visiteurs
        if (!Schema::hasTable('calculator_sessions')) {
            Schema::create('calculator_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('session_id')->nullable();
                $table->string('visitor_email')->nullable();
                $table->string('visitor_phone')->nullable();
                $table->string('visitor_name')->nullable();
                $table->json('items_selected')->nullable();
                $table->decimal('total_volume', 8, 2);
                $table->decimal('recommended_size', 8, 2)->nullable();
                $table->unsignedBigInteger('recommended_box_id')->nullable();
                $table->boolean('converted_to_lead')->default(false);
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->boolean('converted_to_booking')->default(false);
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });
        }

        // Configuration du widget calculateur
        if (!Schema::hasTable('calculator_widgets')) {
            Schema::create('calculator_widgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('embed_code')->unique();
                $table->json('style_config')->nullable();
                $table->json('categories_enabled')->nullable();
                $table->boolean('show_prices')->default(true);
                $table->boolean('show_availability')->default(true);
                $table->boolean('require_contact')->default(false);
                $table->boolean('enable_booking')->default(true);
                $table->string('redirect_url')->nullable();
                $table->text('custom_css')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('views_count')->default(0);
                $table->integer('calculations_count')->default(0);
                $table->integer('leads_count')->default(0);
                $table->timestamps();
            });
        }

        // Amélioration portail client
        if (!Schema::hasTable('customer_portal_settings')) {
            Schema::create('customer_portal_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->boolean('enable_online_payment')->default(true);
                $table->boolean('enable_auto_pay')->default(true);
                $table->boolean('enable_invoice_download')->default(true);
                $table->boolean('enable_contract_view')->default(true);
                $table->boolean('enable_maintenance_requests')->default(true);
                $table->boolean('enable_box_change_request')->default(true);
                $table->boolean('enable_termination_request')->default(true);
                $table->boolean('enable_document_upload')->default(true);
                $table->boolean('enable_notifications_settings')->default(true);
                $table->boolean('enable_payment_methods_management')->default(true);
                $table->json('custom_links')->nullable();
                $table->text('welcome_message')->nullable();
                $table->timestamps();
            });
        }

        // Demandes clients depuis le portail
        if (!Schema::hasTable('customer_requests')) {
            Schema::create('customer_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('type', [
                    'termination',
                    'box_upgrade',
                    'box_downgrade',
                    'payment_plan',
                    'maintenance',
                    'access_issue',
                    'billing_question',
                    'general_inquiry',
                    'document_request',
                    'other'
                ]);
                $table->string('subject');
                $table->text('description');
                $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('staff_notes')->nullable();
                $table->text('response')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->json('attachments')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['customer_id', 'status']);
            });
        }

        // Méthodes de paiement enregistrées
        if (!Schema::hasTable('saved_payment_methods')) {
            Schema::create('saved_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['card', 'sepa', 'bank_account']);
                $table->string('provider')->default('stripe');
                $table->string('provider_id')->nullable();
                $table->string('last_four')->nullable();
                $table->string('brand')->nullable();
                $table->string('exp_month')->nullable();
                $table->string('exp_year')->nullable();
                $table->string('holder_name')->nullable();
                $table->string('iban_last_four')->nullable();
                $table->string('bank_name')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['customer_id', 'is_active']);
            });
        }

        // Paiements automatiques
        if (!Schema::hasTable('auto_pay_settings')) {
            Schema::create('auto_pay_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_method_id')->constrained('saved_payment_methods')->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->integer('days_before_due')->default(3);
                $table->decimal('max_amount', 10, 2)->nullable();
                $table->timestamp('last_charged_at')->nullable();
                $table->timestamp('next_charge_at')->nullable();
                $table->integer('failed_attempts')->default(0);
                $table->timestamps();

                $table->unique(['contract_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_pay_settings');
        Schema::dropIfExists('saved_payment_methods');
        Schema::dropIfExists('customer_requests');
        Schema::dropIfExists('customer_portal_settings');
        Schema::dropIfExists('calculator_widgets');
        Schema::dropIfExists('calculator_sessions');
        Schema::dropIfExists('calculator_items');
        Schema::dropIfExists('calculator_categories');
    }
};
