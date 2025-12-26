<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Move-in sessions - Tracks the entire move-in process
        if (!Schema::hasTable('movein_sessions')) {
            Schema::create('movein_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('box_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedBigInteger('reservation_id')->nullable();

                $table->string('session_token', 64)->unique();
                $table->string('status')->default('pending');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->json('completed_steps')->nullable();
                $table->string('current_step')->default('identity');
                $table->string('identity_method')->nullable();
                $table->json('identity_data')->nullable();
                $table->timestamp('identity_verified_at')->nullable();
                $table->timestamp('contract_sent_at')->nullable();
                $table->timestamp('contract_signed_at')->nullable();
                $table->string('signature_ip')->nullable();
                $table->string('signature_user_agent')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('payment_intent_id')->nullable();
                $table->decimal('amount_paid', 10, 2)->nullable();
                $table->timestamp('payment_completed_at')->nullable();
                $table->string('access_code')->nullable();
                $table->string('access_qr_code')->nullable();
                $table->timestamp('access_granted_at')->nullable();
                $table->timestamp('first_access_at')->nullable();
                $table->date('preferred_movein_date')->nullable();
                $table->string('preferred_time_slot')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('device_type')->nullable();
                $table->string('browser')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('language')->default('fr');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('reminder_sent_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['session_token']);
                $table->index(['email']);
            });
        }

        if (!Schema::hasTable('movein_step_logs')) {
            Schema::create('movein_step_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movein_session_id')->constrained('movein_sessions')->onDelete('cascade');
                $table->string('step');
                $table->string('action');
                $table->json('data')->nullable();
                $table->string('error_message')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                $table->index(['movein_session_id', 'step']);
            });
        }

        if (!Schema::hasTable('identity_documents')) {
            Schema::create('identity_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movein_session_id')->constrained('movein_sessions')->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('document_type');
                $table->string('document_country', 2)->nullable();
                $table->string('document_number_hash')->nullable();
                $table->date('document_expiry')->nullable();
                $table->string('front_image_path')->nullable();
                $table->string('back_image_path')->nullable();
                $table->string('selfie_image_path')->nullable();
                $table->json('verification_result')->nullable();
                $table->string('verification_status')->default('pending');
                $table->string('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('movein_access_codes')) {
            Schema::create('movein_access_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('movein_session_id')->constrained('movein_sessions')->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('code', 32)->unique();
                $table->string('qr_code_path')->nullable();
                $table->string('code_type')->default('temporary');
                $table->dateTime('valid_from');
                $table->dateTime('valid_until');
                $table->integer('max_uses')->default(3);
                $table->integer('use_count')->default(0);
                $table->json('allowed_areas')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->index(['code']);
                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('movein_access_logs')) {
            Schema::create('movein_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('access_code_id')->constrained('movein_access_codes')->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->string('door_id')->nullable();
                $table->string('access_point')->nullable();
                $table->string('status');
                $table->string('denial_reason')->nullable();
                $table->string('method')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('movein_configurations')) {
            Schema::create('movein_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->boolean('enabled')->default(true);
                $table->boolean('require_identity_verification')->default(true);
                $table->boolean('require_selfie')->default(false);
                $table->boolean('allow_video_verification')->default(false);
                $table->boolean('require_document_scan')->default(true);
                $table->boolean('enable_digital_signature')->default(true);
                $table->boolean('require_initials')->default(false);
                $table->string('contract_template_id')->nullable();
                $table->boolean('require_upfront_payment')->default(true);
                $table->boolean('allow_sepa_mandate')->default(true);
                $table->boolean('allow_card_payment')->default(true);
                $table->integer('deposit_months')->default(1);
                $table->integer('access_code_validity_hours')->default(48);
                $table->integer('access_code_max_uses')->default(3);
                $table->boolean('send_access_code_sms')->default(true);
                $table->boolean('send_access_code_email')->default(true);
                $table->boolean('enable_qr_code')->default(true);
                $table->boolean('notify_staff_on_completion')->default(true);
                $table->boolean('notify_customer_reminders')->default(true);
                $table->integer('reminder_hours_before_expiry')->default(24);
                $table->integer('session_expiry_hours')->default(72);
                $table->integer('max_retry_attempts')->default(3);
                $table->json('welcome_message')->nullable();
                $table->json('completion_message')->nullable();
                $table->json('custom_steps')->nullable();
                $table->timestamps();
                $table->unique('tenant_id');
            });
        }

        if (!Schema::hasTable('movein_analytics')) {
            Schema::create('movein_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->integer('sessions_started')->default(0);
                $table->integer('identity_verified')->default(0);
                $table->integer('contracts_signed')->default(0);
                $table->integer('payments_completed')->default(0);
                $table->integer('sessions_completed')->default(0);
                $table->integer('sessions_expired')->default(0);
                $table->integer('sessions_cancelled')->default(0);
                $table->integer('avg_completion_minutes')->nullable();
                $table->integer('avg_identity_minutes')->nullable();
                $table->integer('avg_contract_minutes')->nullable();
                $table->integer('avg_payment_minutes')->nullable();
                $table->integer('identity_failures')->default(0);
                $table->integer('payment_failures')->default(0);
                $table->integer('contract_rejections')->default(0);
                $table->decimal('total_revenue', 12, 2)->default(0);
                $table->integer('total_boxes_rented')->default(0);
                $table->timestamps();
                $table->unique(['tenant_id', 'date']);
                $table->index(['tenant_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movein_analytics');
        Schema::dropIfExists('movein_configurations');
        Schema::dropIfExists('movein_access_logs');
        Schema::dropIfExists('movein_access_codes');
        Schema::dropIfExists('identity_documents');
        Schema::dropIfExists('movein_step_logs');
        Schema::dropIfExists('movein_sessions');
    }
};
