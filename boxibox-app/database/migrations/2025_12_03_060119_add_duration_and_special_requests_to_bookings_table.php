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
            // Duration fields
            $table->enum('duration_type', ['month_to_month', 'fixed_term'])->default('month_to_month')->after('rental_type');
            $table->integer('planned_duration_months')->nullable()->after('duration_type'); // Durée prévue en mois
            $table->date('planned_end_date')->nullable()->after('planned_duration_months');

            // Special requests and additional info
            $table->text('special_requests')->nullable()->after('notes'); // Demandes spéciales du client
            $table->boolean('needs_24h_access')->default(false)->after('special_requests');
            $table->boolean('needs_climate_control')->default(false)->after('needs_24h_access');
            $table->boolean('needs_electricity')->default(false)->after('needs_climate_control');
            $table->boolean('needs_insurance')->default(false)->after('needs_electricity');

            // Moving assistance
            $table->boolean('needs_moving_help')->default(false)->after('needs_insurance');
            $table->date('preferred_move_in_date')->nullable()->after('needs_moving_help');
            $table->enum('preferred_time_slot', ['morning', 'afternoon', 'evening', 'flexible'])->nullable()->after('preferred_move_in_date');

            // Additional contact
            $table->string('secondary_contact_name')->nullable()->after('customer_vat_number');
            $table->string('secondary_contact_phone')->nullable()->after('secondary_contact_name');

            // Storage contents info
            $table->text('storage_contents')->nullable()->after('preferred_time_slot'); // What will be stored
            $table->enum('estimated_value', ['under_1000', '1000_5000', '5000_10000', 'over_10000'])->nullable()->after('storage_contents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'duration_type',
                'planned_duration_months',
                'planned_end_date',
                'special_requests',
                'needs_24h_access',
                'needs_climate_control',
                'needs_electricity',
                'needs_insurance',
                'needs_moving_help',
                'preferred_move_in_date',
                'preferred_time_slot',
                'secondary_contact_name',
                'secondary_contact_phone',
                'storage_contents',
                'estimated_value',
            ]);
        });
    }
};
