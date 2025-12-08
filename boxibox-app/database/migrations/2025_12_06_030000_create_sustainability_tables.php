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
        // Energy consumption tracking
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('reading_date');
            $table->decimal('electricity_kwh', 10, 2)->default(0);
            $table->decimal('gas_m3', 10, 2)->default(0);
            $table->decimal('water_m3', 10, 2)->default(0);
            $table->decimal('solar_generated_kwh', 10, 2)->default(0);
            $table->decimal('electricity_cost', 10, 2)->nullable();
            $table->decimal('gas_cost', 10, 2)->nullable();
            $table->decimal('water_cost', 10, 2)->nullable();
            $table->string('source')->default('manual'); // manual, smart_meter, api
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'reading_date']);
            $table->index(['tenant_id', 'reading_date']);
        });

        // Carbon footprint calculations
        Schema::create('carbon_footprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->year('year');
            $table->tinyInteger('month');
            $table->decimal('electricity_co2_kg', 12, 2)->default(0);
            $table->decimal('gas_co2_kg', 12, 2)->default(0);
            $table->decimal('transport_co2_kg', 12, 2)->default(0);
            $table->decimal('waste_co2_kg', 12, 2)->default(0);
            $table->decimal('total_co2_kg', 12, 2)->default(0);
            $table->decimal('offset_co2_kg', 12, 2)->default(0); // Carbon offsets
            $table->decimal('net_co2_kg', 12, 2)->default(0);
            $table->decimal('co2_per_sqm', 8, 4)->nullable();
            $table->decimal('co2_per_box', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'site_id', 'year', 'month']);
        });

        // Sustainability initiatives/actions
        Schema::create('sustainability_initiatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('category'); // energy, waste, transport, water, materials
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('investment_cost', 12, 2)->nullable();
            $table->decimal('annual_savings', 12, 2)->nullable();
            $table->decimal('co2_reduction_kg', 12, 2)->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, completed
            $table->integer('progress_percent')->default(0);
            $table->json('milestones')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Waste tracking
        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->decimal('general_waste_kg', 10, 2)->default(0);
            $table->decimal('recycling_kg', 10, 2)->default(0);
            $table->decimal('cardboard_kg', 10, 2)->default(0);
            $table->decimal('hazardous_kg', 10, 2)->default(0);
            $table->decimal('organic_kg', 10, 2)->default(0);
            $table->decimal('total_kg', 10, 2)->default(0);
            $table->decimal('recycling_rate', 5, 2)->default(0); // percentage
            $table->decimal('disposal_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'record_date']);
        });

        // Sustainability goals/targets
        Schema::create('sustainability_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('metric'); // co2_reduction, energy_reduction, recycling_rate, etc.
            $table->decimal('baseline_value', 12, 2);
            $table->decimal('target_value', 12, 2);
            $table->decimal('current_value', 12, 2)->default(0);
            $table->string('unit'); // kg, %, kWh, etc.
            $table->year('target_year');
            $table->text('description')->nullable();
            $table->boolean('is_achieved')->default(false);
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'target_year']);
        });

        // Certifications and badges
        Schema::create('sustainability_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // ISO 14001, BREEAM, LEED, etc.
            $table->string('level')->nullable(); // Gold, Silver, Bronze, etc.
            $table->date('obtained_date');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('certificate_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability_certifications');
        Schema::dropIfExists('sustainability_goals');
        Schema::dropIfExists('waste_records');
        Schema::dropIfExists('sustainability_initiatives');
        Schema::dropIfExists('carbon_footprints');
        Schema::dropIfExists('energy_readings');
    }
};
