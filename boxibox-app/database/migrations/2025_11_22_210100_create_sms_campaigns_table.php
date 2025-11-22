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
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Campaign details
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('message');

            // Targeting
            $table->enum('segment', [
                'all',
                'vip',
                'at_risk',
                'new_customers',
                'inactive'
            ])->default('all');
            $table->json('filters')->nullable(); // Additional filter criteria

            // Scheduling
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Statistics
            $table->integer('recipients_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('estimated_cost', 10, 4)->default(0);
            $table->decimal('actual_cost', 10, 4)->default(0);

            // Metadata
            $table->string('provider')->nullable(); // twilio, vonage, aws_sns
            $table->json('provider_response')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('status');
            $table->index('segment');
            $table->index('scheduled_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_campaigns');
    }
};
