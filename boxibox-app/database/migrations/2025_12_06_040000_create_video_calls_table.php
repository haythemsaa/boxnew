<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Video call sessions
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->onDelete('set null');
            $table->string('room_id')->unique();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->enum('status', ['pending', 'waiting', 'in_progress', 'completed', 'cancelled', 'missed'])->default('pending');
            $table->enum('type', ['tour', 'consultation', 'support', 'onboarding'])->default('tour');
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->text('summary')->nullable();
            $table->json('recording_urls')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_at']);
            $table->index(['agent_id', 'status']);
        });

        // Agent availability schedules
        Schema::create('video_agent_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0-6 (Sunday-Saturday)
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week', 'start_time']);
        });

        // Agent current status
        Schema::create('video_agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['online', 'busy', 'away', 'offline'])->default('offline');
            $table->foreignId('current_call_id')->nullable()->constrained('video_calls')->onDelete('set null');
            $table->datetime('status_changed_at')->nullable();
            $table->datetime('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Video call messages (chat during call)
        Schema::create('video_call_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_call_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sender_type'); // agent, guest
            $table->string('sender_name');
            $table->text('message');
            $table->enum('type', ['text', 'file', 'image', 'system'])->default('text');
            $table->string('file_url')->nullable();
            $table->timestamps();

            $table->index('video_call_id');
        });

        // Video call invitations
        Schema::create('video_call_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_call_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['pending', 'sent', 'opened', 'joined', 'expired'])->default('pending');
            $table->datetime('sent_at')->nullable();
            $table->datetime('opened_at')->nullable();
            $table->datetime('joined_at')->nullable();
            $table->datetime('expires_at');
            $table->timestamps();
        });

        // Video settings per tenant
        Schema::create('video_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('video_enabled')->default(true);
            $table->boolean('recording_enabled')->default(false);
            $table->boolean('chat_enabled')->default(true);
            $table->boolean('screen_sharing_enabled')->default(true);
            $table->boolean('waiting_room_enabled')->default(true);
            $table->integer('max_call_duration_minutes')->default(60);
            $table->integer('max_concurrent_calls')->default(5);
            $table->string('welcome_message')->nullable();
            $table->string('waiting_room_message')->nullable();
            $table->json('notification_emails')->nullable();
            $table->json('working_hours')->nullable();
            $table->string('ice_servers')->nullable(); // TURN/STUN server config
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_settings');
        Schema::dropIfExists('video_call_invitations');
        Schema::dropIfExists('video_call_messages');
        Schema::dropIfExists('video_agent_statuses');
        Schema::dropIfExists('video_agent_schedules');
        Schema::dropIfExists('video_calls');
    }
};
