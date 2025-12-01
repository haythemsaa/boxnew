<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fournisseurs de serrures connectées
        if (!Schema::hasTable('smart_lock_providers')) {
            Schema::create('smart_lock_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('api_base_url')->nullable();
                $table->text('description')->nullable();
                $table->string('logo_url')->nullable();
                $table->json('supported_features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Configuration des serrures par tenant/site
        if (!Schema::hasTable('smart_lock_configurations')) {
            Schema::create('smart_lock_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->foreignId('provider_id')->constrained('smart_lock_providers')->cascadeOnDelete();
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->string('account_id')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('auto_lock_on_overdue')->default(true);
                $table->integer('overdue_days_before_lock')->default(7);
                $table->boolean('send_lock_notification')->default(true);
                $table->timestamp('last_sync_at')->nullable();
                $table->string('sync_status')->nullable();
                $table->timestamps();
                $table->unique(['site_id', 'provider_id']);
            });
        }

        // Serrures individuelles
        if (!Schema::hasTable('smart_locks')) {
            Schema::create('smart_locks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('configuration_id')->constrained('smart_lock_configurations')->cascadeOnDelete();
                $table->foreignId('box_id')->constrained()->cascadeOnDelete();
                $table->string('external_lock_id');
                $table->string('serial_number')->nullable();
                $table->string('model')->nullable();
                $table->string('firmware_version')->nullable();
                $table->enum('status', ['online', 'offline', 'locked', 'unlocked', 'error'])->default('online');
                $table->integer('battery_level')->nullable();
                $table->timestamp('last_battery_check')->nullable();
                $table->boolean('is_locked')->default(true);
                $table->timestamp('last_locked_at')->nullable();
                $table->timestamp('last_unlocked_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['configuration_id', 'external_lock_id']);
                $table->index('box_id');
            });
        }

        // Codes d'accès temporaires
        if (!Schema::hasTable('access_codes')) {
            Schema::create('access_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('smart_lock_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('code');
                $table->enum('code_type', ['permanent', 'temporary', 'one_time', 'recurring'])->default('permanent');
                $table->string('name')->nullable();
                $table->timestamp('valid_from');
                $table->timestamp('valid_until')->nullable();
                $table->json('recurring_schedule')->nullable();
                $table->integer('max_uses')->nullable();
                $table->integer('use_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_revoked')->default(false);
                $table->string('revoke_reason')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users');
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->index(['smart_lock_id', 'is_active']);
                $table->index(['customer_id', 'is_active']);
            });
        }

        // Logs d'accès
        if (!Schema::hasTable('access_logs')) {
            Schema::create('access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('smart_lock_id')->constrained()->cascadeOnDelete();
                $table->foreignId('access_code_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('box_id')->constrained()->cascadeOnDelete();
                $table->enum('event_type', ['unlock_success', 'unlock_failed', 'lock_success', 'lock_failed', 'code_invalid', 'code_expired', 'access_denied', 'forced_entry', 'battery_low', 'offline', 'online']);
                $table->string('access_method')->nullable();
                $table->string('code_used')->nullable();
                $table->string('ip_address')->nullable();
                $table->json('device_info')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->text('notes')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamp('event_at');
                $table->timestamps();
                $table->index(['smart_lock_id', 'event_at']);
                $table->index(['customer_id', 'event_at']);
                $table->index(['box_id', 'event_at']);
            });
        }

        // Commandes de verrouillage/déverrouillage
        if (!Schema::hasTable('lock_commands')) {
            Schema::create('lock_commands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('smart_lock_id')->constrained()->cascadeOnDelete();
                $table->foreignId('issued_by')->constrained('users');
                $table->enum('command', ['lock', 'unlock', 'generate_code', 'revoke_code', 'sync']);
                $table->json('parameters')->nullable();
                $table->enum('status', ['pending', 'sent', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->json('response')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['smart_lock_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lock_commands');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('access_codes');
        Schema::dropIfExists('smart_locks');
        Schema::dropIfExists('smart_lock_configurations');
        Schema::dropIfExists('smart_lock_providers');
    }
};
