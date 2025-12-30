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
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('payment_method', [
                'card_now',        // Card payment now via Stripe
                'at_signing',      // Pay at contract signing (on site)
                'bank_transfer',   // Bank transfer
                'sepa_debit',      // SEPA direct debit
                'cash',            // Cash payment
                'check',           // Check payment
                'other'            // Other payment method
            ])->default('at_signing')->after('discount_amount');

            $table->text('payment_notes')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_notes']);
        });
    }
};
