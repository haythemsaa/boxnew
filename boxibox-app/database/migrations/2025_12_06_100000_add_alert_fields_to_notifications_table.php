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
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->string('priority')->default('medium')->after('message');
            }
            if (!Schema::hasColumn('notifications', 'related_type')) {
                $table->string('related_type')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('notifications', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
            }
            if (!Schema::hasColumn('notifications', 'alert_key')) {
                $table->string('alert_key')->nullable()->unique()->after('related_id');
            }
        });

        // Add index for faster queries
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'read_at'], 'notifications_user_unread_idx');
            $table->index(['tenant_id', 'type'], 'notifications_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_unread_idx');
            $table->dropIndex('notifications_type_idx');
            $table->dropColumn(['priority', 'related_type', 'related_id', 'alert_key']);
        });
    }
};
