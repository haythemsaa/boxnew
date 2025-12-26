<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Price history for ML training
        if (!Schema::hasTable('price_histories')) {
            Schema::create('price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('box_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');

                $table->decimal('base_price', 10, 2);
                $table->decimal('calculated_price', 10, 2);
                $table->decimal('final_price', 10, 2);

                // Context at time of pricing
                $table->decimal('occupancy_rate', 5, 2);
                $table->string('season')->nullable();
                $table->integer('day_of_week');
                $table->boolean('is_holiday')->default(false);

                // Outcome tracking
                $table->boolean('was_rented')->default(false);
                $table->integer('days_to_rent')->nullable();
                $table->decimal('actual_rent_price', 10, 2)->nullable();

                // ML features
                $table->json('features')->nullable();
                $table->decimal('predicted_conversion', 5, 4)->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'box_id', 'created_at']);
                $table->index(['site_id', 'was_rented']);
            });
        }

        // A/B Testing experiments
        if (!Schema::hasTable('pricing_experiments')) {
            Schema::create('pricing_experiments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'running', 'paused', 'completed', 'cancelled'])->default('draft');

                // Variants configuration
                $table->json('variants'); // [{name, weight, price_modifier, type}]

                // Traffic allocation
                $table->decimal('traffic_percentage', 5, 2)->default(100);

                // Statistical settings
                $table->integer('min_sample_size')->default(100);
                $table->decimal('confidence_level', 5, 2)->default(95);

                // Duration
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->integer('duration_days')->default(14);

                // Results
                $table->json('results')->nullable();
                $table->string('winning_variant')->nullable();
                $table->decimal('revenue_lift', 8, 2)->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // Experiment exposures (who saw what)
        if (!Schema::hasTable('experiment_exposures')) {
            Schema::create('experiment_exposures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('experiment_id')->constrained('pricing_experiments')->onDelete('cascade');
                $table->foreignId('box_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('visitor_id'); // Session/customer identifier
                $table->string('variant_name');

                // Price shown
                $table->decimal('price_shown', 10, 2);

                // Conversion tracking
                $table->boolean('converted')->default(false);
                $table->decimal('revenue', 10, 2)->nullable();
                $table->timestamp('converted_at')->nullable();

                $table->timestamps();

                $table->index(['experiment_id', 'variant_name']);
                $table->index(['visitor_id']);
            });
        }

        // Competitor price tracking
        if (!Schema::hasTable('competitor_prices')) {
            Schema::create('competitor_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');

                $table->string('competitor_name');
                $table->string('competitor_location')->nullable();
                $table->decimal('distance_km', 8, 2)->nullable();

                // Box category (small, medium, large, xl)
                $table->string('box_category');
                $table->decimal('box_size_m2', 8, 2)->nullable();

                $table->decimal('monthly_price', 10, 2);
                $table->decimal('weekly_price', 10, 2)->nullable();
                $table->boolean('has_promotion')->default(false);
                $table->string('promotion_details')->nullable();

                $table->string('source')->nullable(); // 'manual', 'scraping', 'api'
                $table->timestamp('collected_at');
                $table->timestamps();

                $table->index(['tenant_id', 'site_id', 'box_category']);
            });
        }

        // ML Model training logs
        if (!Schema::hasTable('pricing_model_logs')) {
            Schema::create('pricing_model_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

                $table->string('model_version');
                $table->string('model_type'); // 'demand_prediction', 'price_optimization', 'churn_risk'

                // Training metrics
                $table->decimal('training_accuracy', 5, 4)->nullable();
                $table->decimal('validation_accuracy', 5, 4)->nullable();
                $table->decimal('mae', 10, 4)->nullable(); // Mean Absolute Error
                $table->decimal('rmse', 10, 4)->nullable(); // Root Mean Square Error

                // Model configuration
                $table->json('hyperparameters')->nullable();
                $table->json('feature_importance')->nullable();
                $table->integer('training_samples');

                $table->boolean('is_active')->default(false);
                $table->timestamp('trained_at');
                $table->timestamps();

                $table->index(['tenant_id', 'model_type', 'is_active']);
            });
        }

        // Demand forecasts
        if (!Schema::hasTable('demand_forecasts')) {
            Schema::create('demand_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');

                $table->date('forecast_date');
                $table->string('box_category')->nullable();

                // Predictions
                $table->decimal('predicted_demand', 8, 2); // Expected number of inquiries
                $table->decimal('predicted_conversion', 5, 4); // Expected conversion rate
                $table->decimal('confidence_lower', 8, 2);
                $table->decimal('confidence_upper', 8, 2);

                // Recommended actions
                $table->decimal('recommended_price_modifier', 5, 4)->default(1.0);
                $table->json('factors')->nullable(); // What drove this prediction

                $table->timestamps();

                $table->unique(['tenant_id', 'site_id', 'forecast_date', 'box_category'], 'demand_forecasts_unique');
            });
        }

        // Real-time price adjustments log
        if (!Schema::hasTable('price_adjustments')) {
            Schema::create('price_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('box_id')->constrained()->onDelete('cascade');

                $table->decimal('old_price', 10, 2);
                $table->decimal('new_price', 10, 2);
                $table->decimal('adjustment_percentage', 5, 2);

                $table->string('trigger'); // 'occupancy', 'demand', 'competitor', 'seasonal', 'manual', 'ml'
                $table->json('trigger_details')->nullable();

                $table->boolean('auto_applied')->default(true);
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
                $table->index(['box_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_adjustments');
        Schema::dropIfExists('demand_forecasts');
        Schema::dropIfExists('pricing_model_logs');
        Schema::dropIfExists('competitor_prices');
        Schema::dropIfExists('experiment_exposures');
        Schema::dropIfExists('pricing_experiments');
        Schema::dropIfExists('price_histories');
    }
};
