<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to support_tickets
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('support_tickets', 'type')) {
                    $table->enum('type', ['tenant_customer', 'admin_tenant'])->default('tenant_customer')->after('ticket_number');
                }
                if (!Schema::hasColumn('support_tickets', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('support_tickets', 'last_message_at')) {
                    $table->timestamp('last_message_at')->nullable()->after('status');
                }
            });
        }

        // Chat Messages
        if (!Schema::hasTable('support_messages')) {
            Schema::create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();

                // Sender can be: user (staff/admin), customer, or system
                $table->enum('sender_type', ['user', 'customer', 'system'])->default('user');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

                $table->text('message');
                $table->json('attachments')->nullable();

                $table->boolean('is_internal')->default(false);
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();

                $table->timestamps();

                $table->index(['ticket_id', 'created_at']);
            });
        }

        // Canned Responses
        if (!Schema::hasTable('canned_responses')) {
            Schema::create('canned_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('is_global')->default(false);

                $table->string('title');
                $table->string('shortcut')->nullable();
                $table->text('content');
                $table->enum('category', ['greeting', 'billing', 'technical', 'closing', 'other'])->default('other');

                $table->integer('usage_count')->default(0);
                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('support_messages');

        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn(['type', 'customer_id', 'last_message_at']);
            });
        }
    }
};
