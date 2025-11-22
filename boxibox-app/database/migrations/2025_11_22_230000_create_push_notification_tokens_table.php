<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'is_active']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_tokens');
    }
};
