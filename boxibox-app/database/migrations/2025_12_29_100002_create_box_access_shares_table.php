<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_access_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Owner who shares

            // Share code
            $table->string('share_code', 20)->unique(); // Unique code for access
            $table->string('qr_code_path')->nullable(); // Path to generated QR code

            // Guest info
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->text('guest_note')->nullable(); // Note from owner

            // Validity
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);

            // Restrictions
            $table->json('allowed_hours')->nullable(); // e.g., {"start": "08:00", "end": "18:00"}
            $table->json('allowed_days')->nullable(); // e.g., ["monday", "tuesday", "wednesday"]

            // Status
            $table->enum('status', ['active', 'expired', 'revoked', 'used_up'])->default('active');
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();

            // Notifications
            $table->boolean('notify_on_use')->default(true);
            $table->boolean('sms_sent')->default(false);
            $table->boolean('email_sent')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['box_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['share_code']);
            $table->index(['valid_until']);
        });

        // Add foreign key to box_access_logs
        Schema::table('box_access_logs', function (Blueprint $table) {
            $table->foreign('box_access_share_id')
                ->references('id')
                ->on('box_access_shares')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('box_access_logs', function (Blueprint $table) {
            $table->dropForeign(['box_access_share_id']);
        });

        Schema::dropIfExists('box_access_shares');
    }
};
