<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-calculated statistics tables for dashboard performance
 * These tables store aggregated data to avoid expensive real-time calculations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // TENANT DAILY STATS - Pre-calculated daily metrics
        // =====================================================
        Schema::create('tenant_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('stat_date');

            // Revenue metrics
            $table->decimal('daily_revenue', 12, 2)->default(0);
            $table->decimal('monthly_revenue', 12, 2)->default(0);
            $table->decimal('yearly_revenue', 12, 2)->default(0);

            // Payment metrics
            $table->integer('payments_count')->default(0);
            $table->decimal('payments_total', 12, 2)->default(0);

            // Customer metrics
            $table->integer('new_customers')->default(0);
            $table->integer('active_customers')->default(0);
            $table->integer('churned_customers')->default(0);

            // Contract metrics
            $table->integer('new_contracts')->default(0);
            $table->integer('active_contracts')->default(0);
            $table->integer('terminated_contracts')->default(0);
            $table->decimal('average_contract_value', 10, 2)->default(0);

            // Occupation metrics
            $table->integer('total_boxes')->default(0);
            $table->integer('occupied_boxes')->default(0);
            $table->integer('available_boxes')->default(0);
            $table->integer('reserved_boxes')->default(0);
            $table->integer('maintenance_boxes')->default(0);
            $table->decimal('occupation_rate', 5, 2)->default(0);

            // Invoicing metrics
            $table->integer('invoices_sent')->default(0);
            $table->integer('invoices_paid')->default(0);
            $table->integer('invoices_overdue')->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);

            // Lead/Booking metrics
            $table->integer('new_leads')->default(0);
            $table->integer('converted_leads')->default(0);
            $table->integer('new_bookings')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);

            $table->timestamps();

            // Unique constraint to prevent duplicate stats
            $table->unique(['tenant_id', 'stat_date'], 'idx_tenant_daily_unique');
            $table->index(['tenant_id', 'stat_date'], 'idx_tenant_daily_lookup');
        });

        // =====================================================
        // SITE DAILY STATS - Pre-calculated site-level metrics
        // =====================================================
        Schema::create('site_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->date('stat_date');

            // Occupation metrics
            $table->integer('total_boxes')->default(0);
            $table->integer('occupied_boxes')->default(0);
            $table->integer('available_boxes')->default(0);
            $table->decimal('occupation_rate', 5, 2)->default(0);

            // Revenue metrics
            $table->decimal('daily_revenue', 12, 2)->default(0);
            $table->decimal('potential_revenue', 12, 2)->default(0);
            $table->decimal('lost_revenue', 12, 2)->default(0);

            // Contract activity
            $table->integer('new_contracts')->default(0);
            $table->integer('terminated_contracts')->default(0);
            $table->integer('active_contracts')->default(0);

            // IoT metrics (if applicable)
            $table->integer('iot_alerts_count')->default(0);
            $table->decimal('avg_temperature', 5, 2)->nullable();
            $table->decimal('avg_humidity', 5, 2)->nullable();

            // Access metrics
            $table->integer('access_events')->default(0);
            $table->integer('unique_visitors')->default(0);

            $table->timestamps();

            // Unique constraint
            $table->unique(['site_id', 'stat_date'], 'idx_site_daily_unique');
            $table->index(['site_id', 'stat_date'], 'idx_site_daily_lookup');
        });

        // =====================================================
        // SITE HOURLY STATS - For real-time dashboards
        // =====================================================
        Schema::create('site_hourly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->timestamp('stat_hour');

            // IoT readings summary
            $table->integer('iot_readings_count')->default(0);
            $table->integer('iot_alerts_count')->default(0);
            $table->decimal('avg_temperature', 5, 2)->nullable();
            $table->decimal('avg_humidity', 5, 2)->nullable();
            $table->decimal('min_temperature', 5, 2)->nullable();
            $table->decimal('max_temperature', 5, 2)->nullable();

            // Access control
            $table->integer('access_events')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('failed_access_attempts')->default(0);

            // Chatbot activity
            $table->integer('chatbot_conversations')->default(0);
            $table->integer('chatbot_handoffs')->default(0);

            $table->timestamps();

            // Unique constraint
            $table->unique(['site_id', 'stat_hour'], 'idx_site_hourly_unique');
            $table->index(['site_id', 'stat_hour'], 'idx_site_hourly_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_hourly_stats');
        Schema::dropIfExists('site_daily_stats');
        Schema::dropIfExists('tenant_daily_stats');
    }
};
