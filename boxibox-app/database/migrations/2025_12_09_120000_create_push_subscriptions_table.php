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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('endpoint', 500)->unique();
            $table->string('p256dh_key', 100);
            $table->string('auth_token', 50);
            $table->string('content_encoding')->default('aesgcm');
            $table->string('device_type')->nullable(); // web, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Table pour l'historique des notifications envoyees
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // alert, reminder, payment, contract, system
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed, clicked
            $table->integer('recipients_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
        Schema::dropIfExists('push_subscriptions');
    }
};
