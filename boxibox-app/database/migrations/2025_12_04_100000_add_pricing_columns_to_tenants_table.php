<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'widget_level')) {
                $table->enum('widget_level', ['none', 'basic', 'pro', 'whitelabel'])
                    ->default('none')
                    ->after('plan');
            }

            if (!Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->enum('billing_cycle', ['monthly', 'yearly'])
                    ->default('monthly')
                    ->after('widget_level');
            }

            if (!Schema::hasColumn('tenants', 'addons')) {
                $table->json('addons')->nullable()->after('plan_elements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['widget_level', 'billing_cycle', 'addons']);
        });
    }
};
