<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('sms_campaigns')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to'); // Phone number
            $table->text('message');
            $table->string('status')->default('sent'); // sent, failed, delivered, undelivered
            $table->string('provider')->nullable(); // twilio, vonage, aws-sns
            $table->string('provider_id')->nullable(); // SID from provider
            $table->string('type')->default('general'); // payment_reminder, promotion, welcome, otp, campaign
            $table->decimal('cost', 10, 4)->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('campaign_id');
            $table->index('customer_id');
            $table->index('type');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
