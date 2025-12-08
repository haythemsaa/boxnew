<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rapports personnalisés
        if (!Schema::hasTable('custom_reports')) {
            Schema::create('custom_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['rent_roll', 'revenue', 'occupancy', 'aging', 'activity', 'custom', 'cash_flow', 'financial']);
                $table->json('columns')->nullable();
                $table->json('filters')->nullable();
                $table->json('grouping')->nullable();
                $table->json('sorting')->nullable();
                $table->boolean('is_public')->default(false);
                $table->boolean('is_favorite')->default(false);
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
            });
        }

        // Rapports planifiés
        if (!Schema::hasTable('scheduled_reports')) {
            Schema::create('scheduled_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('report_id')->nullable();
                $table->string('name');
                $table->enum('report_type', ['rent_roll', 'revenue', 'occupancy', 'aging', 'activity', 'financial', 'custom']);
                $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annually']);
                $table->integer('day_of_week')->nullable();
                $table->integer('day_of_month')->nullable();
                $table->time('send_time')->default('08:00');
                $table->json('recipients');
                $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
                $table->json('filters')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamp('next_send_at')->nullable();
                $table->timestamps();
            });
        }

        // Historique des rapports générés
        if (!Schema::hasTable('report_history')) {
            Schema::create('report_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('scheduled_report_id')->nullable();
                $table->unsignedBigInteger('custom_report_id')->nullable();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('report_type');
                $table->string('file_path')->nullable();
                $table->string('file_name');
                $table->string('format');
                $table->integer('file_size')->nullable();
                $table->json('parameters')->nullable();
                $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }

        // Snapshots mensuels
        if (!Schema::hasTable('monthly_snapshots')) {
            Schema::create('monthly_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->date('snapshot_date');
                $table->integer('total_boxes')->default(0);
                $table->integer('occupied_boxes')->default(0);
                $table->integer('available_boxes')->default(0);
                $table->integer('reserved_boxes')->default(0);
                $table->integer('maintenance_boxes')->default(0);
                $table->decimal('occupancy_rate', 5, 2)->default(0);
                $table->decimal('total_sqm', 10, 2)->default(0);
                $table->decimal('occupied_sqm', 10, 2)->default(0);
                $table->integer('active_contracts')->default(0);
                $table->integer('new_contracts')->default(0);
                $table->integer('ended_contracts')->default(0);
                $table->integer('active_customers')->default(0);
                $table->decimal('total_revenue', 12, 2)->default(0);
                $table->decimal('rent_revenue', 12, 2)->default(0);
                $table->decimal('fees_revenue', 12, 2)->default(0);
                $table->decimal('other_revenue', 12, 2)->default(0);
                $table->decimal('outstanding_balance', 12, 2)->default(0);
                $table->integer('overdue_invoices')->default(0);
                $table->decimal('average_rent_per_sqm', 10, 2)->nullable();
                $table->decimal('revpau', 10, 2)->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'site_id', 'snapshot_date']);
                $table->index(['tenant_id', 'snapshot_date']);
            });
        }

        // Prévisions de trésorerie
        if (!Schema::hasTable('cash_flow_forecasts')) {
            Schema::create('cash_flow_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->date('forecast_date');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('expected_rent_income', 12, 2)->default(0);
                $table->decimal('expected_fees_income', 12, 2)->default(0);
                $table->decimal('expected_other_income', 12, 2)->default(0);
                $table->decimal('expected_collections', 12, 2)->default(0);
                $table->decimal('expected_expenses', 12, 2)->default(0);
                $table->decimal('net_cash_flow', 12, 2)->default(0);
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->default(0);
                $table->json('assumptions')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'period_start']);
            });
        }

        // KPIs configurables
        if (!Schema::hasTable('kpi_definitions')) {
            Schema::create('kpi_definitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->text('description')->nullable();
                $table->string('category');
                $table->string('unit');
                $table->string('calculation_method')->nullable();
                $table->decimal('target_value', 12, 2)->nullable();
                $table->enum('target_direction', ['higher', 'lower', 'equal'])->default('higher');
                $table->decimal('warning_threshold', 12, 2)->nullable();
                $table->decimal('critical_threshold', 12, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
            });
        }

        // Valeurs des KPIs
        if (!Schema::hasTable('kpi_values')) {
            Schema::create('kpi_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('kpi_id')->constrained('kpi_definitions')->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
                $table->date('period_date');
                $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
                $table->decimal('value', 12, 4);
                $table->decimal('previous_value', 12, 4)->nullable();
                $table->decimal('change_percentage', 8, 2)->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'period_date']);
            });
        }

        // Tableaux de bord personnalisés
        if (!Schema::hasTable('dashboards')) {
            Schema::create('dashboards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_default')->default(false);
                $table->json('layout')->nullable();
                $table->timestamps();
            });
        }

        // Widgets des tableaux de bord
        if (!Schema::hasTable('dashboard_widgets')) {
            Schema::create('dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
                $table->string('widget_type');
                $table->string('title');
                $table->json('config');
                $table->integer('position_x')->default(0);
                $table->integer('position_y')->default(0);
                $table->integer('width')->default(4);
                $table->integer('height')->default(2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('cash_flow_forecasts');
        Schema::dropIfExists('monthly_snapshots');
        Schema::dropIfExists('report_history');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('custom_reports');
    }
};
