<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

            // Access details
            $table->enum('access_type', ['entry', 'exit'])->default('entry');
            $table->enum('method', ['code', 'qr_code', 'nfc', 'smart_lock', 'manual', 'shared_access'])->default('code');
            $table->string('access_code_used')->nullable();

            // Shared access info
            $table->foreignId('shared_by_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('box_access_share_id')->nullable();

            // Device/location info
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Status
            $table->enum('status', ['success', 'failed', 'denied'])->default('success');
            $table->string('failure_reason')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'box_id', 'accessed_at']);
            $table->index(['customer_id', 'accessed_at']);
            $table->index(['contract_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_access_logs');
    }
};
