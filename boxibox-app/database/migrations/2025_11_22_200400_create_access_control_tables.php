<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // noke, pti, opentech, salto
            $table->string('device_id')->unique();
            $table->string('device_name');
            $table->string('status')->default('active'); // active, inactive, offline, low_battery
            $table->integer('battery_level')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable(); // Provider-specific data
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('box_id');
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('smart_lock_id')->nullable()->constrained()->onDelete('set null');
            $table->string('access_method'); // code, bluetooth, nfc, biometric, manual
            $table->string('status'); // granted, denied, forced, timeout
            $table->string('user_identifier')->nullable(); // Code, card number, etc.
            $table->text('reason')->nullable(); // Reason for denial
            $table->timestamp('accessed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'accessed_at']);
            $table->index(['box_id', 'accessed_at']);
            $table->index(['customer_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('smart_locks');
    }
};
