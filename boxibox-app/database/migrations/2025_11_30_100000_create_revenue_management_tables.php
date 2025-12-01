<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des stratégies de pricing IA
        Schema::create('pricing_ai_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('strategy_type', ['occupancy_based', 'demand_based', 'seasonal', 'competitor', 'ml_optimized'])->default('occupancy_based');

            // Paramètres de l'algorithme
            $table->decimal('min_price_factor', 5, 2)->default(0.80); // -20% minimum
            $table->decimal('max_price_factor', 5, 2)->default(1.50); // +50% maximum
            $table->integer('occupancy_threshold_low')->default(60); // Seuil bas occupation
            $table->integer('occupancy_threshold_high')->default(85); // Seuil haut occupation
            $table->decimal('price_increase_step', 5, 2)->default(0.05); // +5% par palier
            $table->decimal('price_decrease_step', 5, 2)->default(0.03); // -3% par palier

            // Saisonnalité
            $table->json('seasonal_factors')->nullable(); // {"jan": 0.9, "jul": 1.2, ...}
            $table->json('day_of_week_factors')->nullable(); // {"monday": 1.0, "saturday": 1.1, ...}

            // ML parameters
            $table->json('ml_model_params')->nullable();
            $table->timestamp('last_ml_training')->nullable();
            $table->decimal('ml_confidence_score', 5, 2)->nullable();

            $table->timestamps();
        });

        // Historique des prix recommandés par l'IA
        Schema::create('pricing_ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('box_type')->nullable(); // Pour recommandations par type

            $table->decimal('current_price', 10, 2);
            $table->decimal('recommended_price', 10, 2);
            $table->decimal('price_change_percent', 5, 2);
            $table->decimal('confidence_score', 5, 2)->default(0.85);

            // Facteurs de la recommandation
            $table->decimal('occupancy_rate', 5, 2);
            $table->decimal('demand_score', 5, 2)->nullable();
            $table->decimal('seasonal_factor', 5, 2)->nullable();
            $table->decimal('competitor_factor', 5, 2)->nullable();

            $table->enum('status', ['pending', 'applied', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        // Métriques RevPAU (Revenue per Available Unit)
        Schema::create('revenue_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');

            // Métriques d'occupation
            $table->integer('total_units');
            $table->integer('occupied_units');
            $table->integer('reserved_units')->default(0);
            $table->decimal('physical_occupancy_rate', 5, 2);
            $table->decimal('economic_occupancy_rate', 5, 2);

            // Métriques de revenus
            $table->decimal('gross_revenue', 12, 2);
            $table->decimal('net_revenue', 12, 2);
            $table->decimal('revpau', 10, 2); // Revenue per Available Unit
            $table->decimal('revpou', 10, 2); // Revenue per Occupied Unit
            $table->decimal('average_rent', 10, 2);

            // Métriques de performance
            $table->decimal('collection_rate', 5, 2)->default(100);
            $table->decimal('delinquency_rate', 5, 2)->default(0);
            $table->integer('new_contracts')->default(0);
            $table->integer('terminated_contracts')->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);

            // Comparaisons
            $table->decimal('revpau_change_vs_previous', 5, 2)->nullable();
            $table->decimal('revpau_change_vs_year', 5, 2)->nullable();

            $table->timestamps();
            $table->unique(['site_id', 'metric_date']);
            $table->index(['tenant_id', 'metric_date']);
        });

        // A/B Testing des prix
        Schema::create('pricing_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('box_type')->nullable();
            $table->decimal('control_price', 10, 2);
            $table->decimal('variant_price', 10, 2);
            $table->integer('control_sample_size')->default(0);
            $table->integer('variant_sample_size')->default(0);

            // Résultats
            $table->integer('control_conversions')->default(0);
            $table->integer('variant_conversions')->default(0);
            $table->decimal('control_revenue', 12, 2)->default(0);
            $table->decimal('variant_revenue', 12, 2)->default(0);

            $table->enum('status', ['draft', 'running', 'paused', 'completed'])->default('draft');
            $table->enum('winner', ['control', 'variant', 'inconclusive'])->nullable();
            $table->decimal('statistical_significance', 5, 2)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // Alertes de pricing
        Schema::create('pricing_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();

            $table->enum('alert_type', [
                'underpriced',
                'overpriced',
                'high_vacancy',
                'competitor_change',
                'revenue_drop',
                'demand_spike',
                'seasonal_opportunity'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();

            $table->decimal('potential_revenue_impact', 12, 2)->nullable();
            $table->string('recommended_action')->nullable();

            $table->boolean('is_read')->default(false);
            $table->boolean('is_actioned')->default(false);
            $table->foreignId('actioned_by')->nullable()->constrained('users');
            $table->timestamp('actioned_at')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_alerts');
        Schema::dropIfExists('pricing_ab_tests');
        Schema::dropIfExists('revenue_metrics');
        Schema::dropIfExists('pricing_ai_recommendations');
        Schema::dropIfExists('pricing_ai_strategies');
    }
};
