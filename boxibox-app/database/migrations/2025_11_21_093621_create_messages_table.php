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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('type', ['message', 'notification', 'system'])->default('message');

            $table->foreignId('parent_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->string('thread_id')->index();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'sender_id']);
            $table->index(['tenant_id', 'recipient_id']);
            $table->index('thread_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
