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
        Schema::table('contracts', function (Blueprint $table) {
            // Add signature image storage paths
            $table->string('customer_signature')->nullable()->after('customer_signed_at');
            $table->string('staff_signature')->nullable()->after('staff_user_id');
            $table->timestamp('staff_signed_at')->nullable()->after('staff_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['customer_signature', 'staff_signature', 'staff_signed_at']);
        });
    }
};
