<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sensor Types (predefined)
        Schema::create('iot_sensor_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('icon', 50)->default('cpu-chip');
            $table->string('color', 20)->default('blue');
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('default_min_threshold', 10, 2)->nullable();
            $table->decimal('default_max_threshold', 10, 2)->nullable();
            $table->timestamps();
        });

        // IoT Sensors
        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sensor_type_id')->constrained('iot_sensor_types')->onDelete('cascade');
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->enum('status', ['online', 'offline', 'alert', 'maintenance'])->default('offline');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->unsignedTinyInteger('signal_strength')->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->decimal('last_value', 10, 2)->nullable();
            $table->decimal('min_threshold', 10, 2)->nullable();
            $table->decimal('max_threshold', 10, 2)->nullable();
            $table->boolean('alert_enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'site_id']);
            $table->index(['status']);
        });

        // Sensor Readings (time series data)
        Schema::create('iot_sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('iot_sensors')->onDelete('cascade');
            $table->decimal('value', 10, 2);
            $table->timestamp('recorded_at');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->unsignedTinyInteger('signal_strength')->nullable();
            $table->timestamps();

            $table->index(['sensor_id', 'recorded_at']);
        });

        // IoT Alerts
        Schema::create('iot_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('sensor_id')->constrained('iot_sensors')->onDelete('cascade');
            $table->string('type'); // temperature_high, humidity_low, battery_low, etc.
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->text('message');
            $table->decimal('value', 10, 2)->nullable();
            $table->decimal('threshold', 10, 2)->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'resolved_at']);
            $table->index(['severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_alerts');
        Schema::dropIfExists('iot_sensor_readings');
        Schema::dropIfExists('iot_sensors');
        Schema::dropIfExists('iot_sensor_types');
    }
};
