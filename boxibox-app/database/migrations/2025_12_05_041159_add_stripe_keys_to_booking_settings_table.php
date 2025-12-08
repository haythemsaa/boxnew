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
        Schema::table('booking_settings', function (Blueprint $table) {
            $table->string('stripe_publishable_key')->nullable()->after('available_payment_methods');
            $table->string('stripe_secret_key')->nullable()->after('stripe_publishable_key');
            $table->boolean('online_payment_enabled')->default(false)->after('stripe_secret_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_settings', function (Blueprint $table) {
            $table->dropColumn(['stripe_publishable_key', 'stripe_secret_key', 'online_payment_enabled']);
        });
    }
};
