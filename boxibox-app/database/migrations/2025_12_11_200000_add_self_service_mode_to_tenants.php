<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les champs self-service aux tenants (si pas déjà présent)
        if (!Schema::hasColumn('tenants', 'self_service_enabled')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('self_service_enabled')->default(false)->after('is_active');
                $table->json('self_service_settings')->nullable()->after('self_service_enabled');
            });
        }

        // Ajouter les champs self-service aux sites
        if (!Schema::hasColumn('sites', 'self_service_enabled')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->boolean('self_service_enabled')->default(false)->after('is_active');
                $table->string('gate_system_type')->nullable()->after('self_service_enabled'); // manual, keypad, qr_scanner, smart_lock, rfid
                $table->string('gate_api_endpoint')->nullable();
                $table->string('gate_api_key')->nullable();
                $table->json('access_hours')->nullable(); // Override tenant settings per site
            });
        }

        // Créer la table des codes d'accès client
        if (!Schema::hasTable('customer_access_codes')) {
            Schema::create('customer_access_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();

                $table->string('access_code')->unique(); // Code PIN (4-6 chiffres)
                $table->string('qr_code')->unique()->nullable(); // Code QR unique
                $table->string('rfid_tag')->nullable(); // Tag RFID si applicable

                $table->enum('status', ['active', 'suspended', 'expired', 'revoked'])->default('active');
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();

                $table->boolean('is_permanent')->default(false); // Code permanent vs temporaire
                $table->boolean('is_master')->default(false); // Code principal du client

                $table->integer('max_uses')->nullable(); // Nombre max d'utilisations (null = illimité)
                $table->integer('use_count')->default(0);

                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id', 'status']);
                $table->index(['access_code']);
                $table->index(['qr_code']);
            });
        }

        // Ajouter colonnes spécifiques à la table access_logs existante si nécessaire
        if (Schema::hasTable('access_logs')) {
            if (!Schema::hasColumn('access_logs', 'access_code_id')) {
                Schema::table('access_logs', function (Blueprint $table) {
                    $table->foreignId('access_code_id')->nullable()->after('customer_id')->constrained('customer_access_codes')->cascadeOnDelete();
                    if (!Schema::hasColumn('access_logs', 'access_method')) {
                        $table->string('access_method')->nullable()->after('event_type'); // pin, qr, rfid, app, manual, guest
                    }
                    if (!Schema::hasColumn('access_logs', 'gate_id')) {
                        $table->string('gate_id')->nullable();
                        $table->string('gate_name')->nullable();
                    }
                });
            }
        }

        // Créer la table des accès invités
        if (!Schema::hasTable('guest_access_codes')) {
            Schema::create('guest_access_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); // Client qui invite
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();

                $table->string('guest_name');
                $table->string('guest_phone')->nullable();
                $table->string('guest_email')->nullable();

                $table->string('access_code')->unique();
                $table->string('qr_code')->unique()->nullable();

                $table->enum('status', ['pending', 'active', 'used', 'expired', 'cancelled'])->default('pending');
                $table->timestamp('valid_from');
                $table->timestamp('valid_until');

                $table->integer('max_uses')->default(1);
                $table->integer('use_count')->default(0);

                $table->text('purpose')->nullable(); // Raison de la visite
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'customer_id', 'status']);
            });
        }

        // Ajouter les préférences d'accès aux clients
        if (!Schema::hasColumn('customers', 'self_service_enabled')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('self_service_enabled')->default(true)->after('status');
                $table->string('preferred_access_method')->default('pin')->after('self_service_enabled'); // pin, qr, app
                $table->boolean('entry_notifications')->default(true);
                $table->boolean('exit_notifications')->default(true);
                $table->boolean('allow_guest_access')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'self_service_enabled')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn([
                    'self_service_enabled',
                    'preferred_access_method',
                    'entry_notifications',
                    'exit_notifications',
                    'allow_guest_access'
                ]);
            });
        }

        Schema::dropIfExists('guest_access_codes');

        if (Schema::hasColumn('access_logs', 'access_code_id')) {
            Schema::table('access_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('access_code_id');
            });
        }

        Schema::dropIfExists('customer_access_codes');

        if (Schema::hasColumn('sites', 'self_service_enabled')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn([
                    'self_service_enabled',
                    'gate_system_type',
                    'gate_api_endpoint',
                    'gate_api_key',
                    'access_hours'
                ]);
            });
        }

        if (Schema::hasColumn('tenants', 'self_service_enabled')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn(['self_service_enabled', 'self_service_settings']);
            });
        }
    }
};
