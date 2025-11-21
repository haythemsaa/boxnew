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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');

            $table->enum('type', [
                'payment_reminder',
                'payment_received',
                'contract_expiring',
                'invoice_generated',
                'message_received'
            ]);
            $table->string('title');
            $table->text('message');

            $table->json('channels'); // ['email', 'sms', 'in_app']
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->timestamp('scheduled_for')->nullable();

            $table->json('data')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
