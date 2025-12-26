<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('review_requests', 'uuid')) {
                $table->uuid('uuid')->after('id')->unique()->nullable();
            }
            if (!Schema::hasColumn('review_requests', 'site_id')) {
                $table->foreignId('site_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('review_requests', 'booking_id')) {
                $table->unsignedBigInteger('booking_id')->nullable()->after('contract_id');
            }
            if (!Schema::hasColumn('review_requests', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('booking_id');
            }
            if (!Schema::hasColumn('review_requests', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_email');
            }
            if (!Schema::hasColumn('review_requests', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('review_requests', 'trigger')) {
                $table->string('trigger')->default('move_in')->after('customer_phone');
            }
            if (!Schema::hasColumn('review_requests', 'delay_days')) {
                $table->integer('delay_days')->default(7)->after('trigger');
            }
            if (!Schema::hasColumn('review_requests', 'scheduled_at')) {
                $table->datetime('scheduled_at')->nullable()->after('delay_days');
            }
            if (!Schema::hasColumn('review_requests', 'channel')) {
                $table->string('channel')->default('email')->after('status');
            }
            if (!Schema::hasColumn('review_requests', 'email_message_id')) {
                $table->string('email_message_id')->nullable()->after('channel');
            }
            if (!Schema::hasColumn('review_requests', 'sms_message_id')) {
                $table->string('sms_message_id')->nullable()->after('email_message_id');
            }
            if (!Schema::hasColumn('review_requests', 'tracking_token')) {
                $table->string('tracking_token')->nullable()->after('sms_message_id');
            }
            if (!Schema::hasColumn('review_requests', 'clicked_at')) {
                $table->datetime('clicked_at')->nullable()->after('tracking_token');
            }
            if (!Schema::hasColumn('review_requests', 'reviewed_at')) {
                $table->datetime('reviewed_at')->nullable()->after('clicked_at');
            }
            if (!Schema::hasColumn('review_requests', 'review_platform')) {
                $table->string('review_platform')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('review_requests', 'external_review_id')) {
                $table->string('external_review_id')->nullable()->after('review_platform');
            }
            if (!Schema::hasColumn('review_requests', 'rating')) {
                $table->integer('rating')->nullable()->after('external_review_id');
            }
            if (!Schema::hasColumn('review_requests', 'review_text')) {
                $table->text('review_text')->nullable()->after('rating');
            }
            if (!Schema::hasColumn('review_requests', 'send_attempts')) {
                $table->integer('send_attempts')->default(0)->after('review_text');
            }
            if (!Schema::hasColumn('review_requests', 'max_attempts')) {
                $table->integer('max_attempts')->default(3)->after('send_attempts');
            }
            if (!Schema::hasColumn('review_requests', 'last_attempt_at')) {
                $table->datetime('last_attempt_at')->nullable()->after('max_attempts');
            }
            if (!Schema::hasColumn('review_requests', 'last_error')) {
                $table->text('last_error')->nullable()->after('last_attempt_at');
            }
            if (!Schema::hasColumn('review_requests', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add indexes
        Schema::table('review_requests', function (Blueprint $table) {
            $indexes = collect(\DB::select("SHOW INDEX FROM review_requests"))->pluck('Key_name')->unique();

            if (!$indexes->contains('review_requests_tracking_token_index') && Schema::hasColumn('review_requests', 'tracking_token')) {
                $table->index('tracking_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            $columns = [
                'uuid', 'site_id', 'booking_id', 'customer_email', 'customer_name',
                'customer_phone', 'trigger', 'delay_days', 'scheduled_at', 'channel',
                'email_message_id', 'sms_message_id', 'tracking_token', 'clicked_at',
                'reviewed_at', 'review_platform', 'external_review_id', 'rating',
                'review_text', 'send_attempts', 'max_attempts', 'last_attempt_at',
                'last_error', 'deleted_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('review_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
