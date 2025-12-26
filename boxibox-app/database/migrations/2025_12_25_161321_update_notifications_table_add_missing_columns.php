<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the type enum to include more notification types
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'payment_reminder',
            'payment_received',
            'contract_expiring',
            'invoice_generated',
            'message_received',
            'payment',
            'contract',
            'invoice',
            'booking',
            'system',
            'alert',
            'reminder',
            'promotion'
        ) NOT NULL DEFAULT 'system'");

        // Add sent_at column if it doesn't exist
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'sent_at')) {
                $table->dropColumn('sent_at');
            }
        });

        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'payment_reminder',
            'payment_received',
            'contract_expiring',
            'invoice_generated',
            'message_received'
        ) NOT NULL");
    }
};
