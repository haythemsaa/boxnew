<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('sms_campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

            // SMS details
            $table->string('to_phone', 20);
            $table->string('from_phone', 20)->nullable();
            $table->text('message');

            // Type and purpose
            $table->enum('type', [
                'campaign',
                'payment_reminder',
                'contract_expiration',
                'welcome',
                'otp',
                'custom'
            ])->default('campaign');

            // Provider info
            $table->string('provider'); // twilio, vonage, aws_sns
            $table->string('provider_message_id')->nullable();

            // Status tracking
            $table->enum('status', [
                'queued',
                'sent',
                'delivered',
                'failed',
                'undelivered'
            ])->default('queued');

            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            // Cost
            $table->decimal('cost', 8, 4)->nullable();
            $table->string('currency', 3)->default('EUR');

            // Metadata
            $table->json('metadata')->nullable();
            $table->json('provider_response')->nullable();

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('sms_campaign_id');
            $table->index('customer_id');
            $table->index('to_phone');
            $table->index('status');
            $table->index('type');
            $table->index('provider');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
