<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Types de capteurs IoT
        if (!Schema::hasTable('iot_sensor_types')) {
        Schema::create('iot_sensor_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit'); // °C, %, lux, etc.
            $table->string('icon')->nullable();

            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('default_alert_min', 10, 2)->nullable();
            $table->decimal('default_alert_max', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        // Hubs/Gateways IoT par site
        if (!Schema::hasTable('iot_hubs')) {
        Schema::create('iot_hubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();

            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('firmware_version')->nullable();

            $table->enum('connection_type', ['wifi', 'ethernet', 'lora', 'cellular'])->default('wifi');
            $table->enum('status', ['online', 'offline', 'error'])->default('online');
            $table->timestamp('last_seen_at')->nullable();

            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });
        }

        // Capteurs individuels
        if (!Schema::hasTable('iot_sensors')) {
        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('iot_hubs')->cascadeOnDelete();
            $table->foreignId('sensor_type_id')->constrained('iot_sensor_types');
            $table->foreignId('box_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('external_id')->nullable(); // ID chez le fabricant

            $table->string('location_description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->enum('status', ['active', 'inactive', 'error', 'low_battery', 'offline'])->default('active');
            $table->integer('battery_level')->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->decimal('last_value', 10, 2)->nullable();

            // Seuils d'alerte personnalisés
            $table->decimal('alert_min', 10, 2)->nullable();
            $table->decimal('alert_max', 10, 2)->nullable();
            $table->boolean('alerts_enabled')->default(true);

            $table->integer('reading_interval_seconds')->default(300); // 5 min par défaut

            $table->json('calibration')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['box_id']);
        });
        }

        // Lectures des capteurs
        if (!Schema::hasTable('iot_readings')) {
        Schema::create('iot_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('iot_sensors')->cascadeOnDelete();

            $table->decimal('value', 10, 2);
            $table->timestamp('recorded_at');

            $table->boolean('is_anomaly')->default(false);
            $table->boolean('triggered_alert')->default(false);

            $table->timestamps();

            // Partitionnement par date pour performance
            $table->index(['sensor_id', 'recorded_at']);
        });
        }

        // Agrégations des lectures (pour historique long terme)
        if (!Schema::hasTable('iot_reading_aggregates')) {
        Schema::create('iot_reading_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('iot_sensors')->cascadeOnDelete();

            $table->enum('period', ['hourly', 'daily', 'weekly', 'monthly']);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            $table->decimal('min_value', 10, 2);
            $table->decimal('max_value', 10, 2);
            $table->decimal('avg_value', 10, 2);
            $table->integer('reading_count');

            $table->integer('anomaly_count')->default(0);
            $table->integer('alert_count')->default(0);

            $table->timestamps();

            $table->unique(['sensor_id', 'period', 'period_start']);
            $table->index(['sensor_id', 'period', 'period_start']);
        });
        }

        // Alertes IoT
        if (!Schema::hasTable('iot_alerts')) {
        Schema::create('iot_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_id')->constrained('iot_sensors')->cascadeOnDelete();
            $table->foreignId('reading_id')->nullable()->constrained('iot_readings');
            $table->foreignId('box_id')->nullable()->constrained();
            $table->foreignId('site_id')->constrained();

            $table->enum('alert_type', [
                'threshold_exceeded',
                'threshold_below',
                'sensor_offline',
                'battery_low',
                'anomaly_detected',
                'rapid_change',
                'device_error'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');

            $table->string('title');
            $table->text('message');
            $table->decimal('trigger_value', 10, 2)->nullable();
            $table->decimal('threshold_value', 10, 2)->nullable();

            $table->enum('status', ['active', 'acknowledged', 'resolved', 'ignored'])->default('active');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Notifications envoyées
            $table->boolean('notification_sent')->default(false);
            $table->json('notification_channels')->nullable(); // ["email", "sms", "push"]
            $table->timestamp('notification_sent_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['sensor_id', 'status']);
        });
        }

        // Règles d'alerte personnalisées
        if (!Schema::hasTable('iot_alert_rules')) {
        Schema::create('iot_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_type_id')->nullable()->constrained('iot_sensor_types');

            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('condition', ['above', 'below', 'equals', 'between', 'outside', 'change_rate']);
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->decimal('threshold_value_2', 10, 2)->nullable(); // Pour "between" et "outside"
            $table->integer('duration_seconds')->nullable(); // Durée avant déclenchement

            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->json('notification_channels')->default('["email"]');
            $table->json('notify_users')->nullable(); // IDs des utilisateurs à notifier

            $table->boolean('is_active')->default(true);
            $table->integer('cooldown_minutes')->default(60); // Délai entre alertes

            $table->timestamps();
        });
        }

        // Rapports IoT pour assurance
        if (!Schema::hasTable('iot_insurance_reports')) {
        Schema::create('iot_insurance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->json('temperature_summary')->nullable();
            $table->json('humidity_summary')->nullable();
            $table->json('incident_summary')->nullable();

            $table->integer('total_alerts')->default(0);
            $table->integer('critical_alerts')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(100);

            $table->string('file_path')->nullable();
            $table->enum('status', ['generating', 'ready', 'sent', 'error'])->default('generating');

            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_insurance_reports');
        Schema::dropIfExists('iot_alert_rules');
        Schema::dropIfExists('iot_alerts');
        Schema::dropIfExists('iot_reading_aggregates');
        Schema::dropIfExists('iot_readings');
        Schema::dropIfExists('iot_sensors');
        Schema::dropIfExists('iot_hubs');
        Schema::dropIfExists('iot_sensor_types');
    }
};
