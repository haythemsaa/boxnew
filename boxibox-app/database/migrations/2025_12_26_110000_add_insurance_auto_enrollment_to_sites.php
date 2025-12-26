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
        Schema::table('sites', function (Blueprint $table) {
            // Auto-enrollment insurance settings
            $table->boolean('insurance_auto_enroll')->default(false)->after('access_hours');
            $table->boolean('insurance_required')->default(false)->after('insurance_auto_enroll');
            $table->foreignId('default_insurance_plan_id')
                  ->nullable()
                  ->after('insurance_required')
                  ->constrained('insurance_plans')
                  ->nullOnDelete();
            $table->decimal('insurance_min_coverage', 10, 2)->nullable()->after('default_insurance_plan_id');
            $table->text('insurance_auto_enroll_message')->nullable()->after('insurance_min_coverage');
        });

        // Also add tenant-level settings
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'insurance_auto_enroll')) {
                $table->boolean('insurance_auto_enroll')->default(false)->after('settings');
                $table->foreignId('default_insurance_plan_id')
                      ->nullable()
                      ->after('insurance_auto_enroll')
                      ->constrained('insurance_plans')
                      ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['default_insurance_plan_id']);
            $table->dropColumn([
                'insurance_auto_enroll',
                'insurance_required',
                'default_insurance_plan_id',
                'insurance_min_coverage',
                'insurance_auto_enroll_message',
            ]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'insurance_auto_enroll')) {
                $table->dropForeign(['default_insurance_plan_id']);
                $table->dropColumn([
                    'insurance_auto_enroll',
                    'default_insurance_plan_id',
                ]);
            }
        });
    }
};
