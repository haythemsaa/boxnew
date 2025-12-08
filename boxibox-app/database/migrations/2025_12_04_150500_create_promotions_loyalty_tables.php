<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Promotions avancées - étendre la table existante si elle existe
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->enum('type', [
                    'first_month_free',
                    'percentage_discount',
                    'fixed_discount',
                    'months_free',
                    'referral',
                    'loyalty',
                    'seasonal',
                    'corporate',
                    'student',
                    'military',
                    'senior'
                ]);
                $table->decimal('discount_value', 10, 2)->nullable();
                $table->integer('discount_months')->nullable();
                $table->decimal('minimum_rent', 10, 2)->nullable();
                $table->integer('minimum_contract_months')->nullable();
                $table->json('applicable_box_types')->nullable();
                $table->json('applicable_sites')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->integer('max_uses')->nullable();
                $table->integer('times_used')->default(0);
                $table->boolean('is_combinable')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('requires_code')->default(false);
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
                $table->index(['tenant_id', 'code']);
            });
        } else {
            // Ajouter les colonnes manquantes si elles n'existent pas
            Schema::table('promotions', function (Blueprint $table) {
                if (!Schema::hasColumn('promotions', 'discount_months')) {
                    $table->integer('discount_months')->nullable()->after('value');
                }
                if (!Schema::hasColumn('promotions', 'minimum_contract_months')) {
                    $table->integer('minimum_contract_months')->nullable()->after('min_rental_amount');
                }
                if (!Schema::hasColumn('promotions', 'applicable_box_types')) {
                    $table->json('applicable_box_types')->nullable();
                }
                if (!Schema::hasColumn('promotions', 'applicable_sites')) {
                    $table->json('applicable_sites')->nullable();
                }
                if (!Schema::hasColumn('promotions', 'is_combinable')) {
                    $table->boolean('is_combinable')->default(false);
                }
                if (!Schema::hasColumn('promotions', 'requires_code')) {
                    $table->boolean('requires_code')->default(false);
                }
            });
        }

        // Utilisation des promotions
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_applied', 10, 2);
            $table->date('applied_from');
            $table->date('applied_until')->nullable();
            $table->timestamps();
        });

        // Programme de parrainage
        Schema::create('referral_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('referrer_reward', 10, 2)->default(50);
            $table->enum('referrer_reward_type', ['cash', 'credit', 'discount', 'free_month']);
            $table->decimal('referee_discount', 10, 2)->default(10);
            $table->enum('referee_discount_type', ['percentage', 'fixed', 'free_month']);
            $table->integer('referee_discount_months')->default(1);
            $table->integer('minimum_contract_months')->default(1);
            $table->integer('days_until_reward')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Parrainages
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('referral_program')->cascadeOnDelete();
            $table->foreignId('referrer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('referee_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('referral_code')->unique();
            $table->enum('status', ['pending', 'signed_up', 'qualified', 'rewarded', 'expired'])->default('pending');
            $table->string('referee_email')->nullable();
            $table->string('referee_name')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_up_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Programme de fidélité
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('points_per_euro', 5, 2)->default(1);
            $table->decimal('points_value_in_euro', 7, 4)->default(0.01);
            $table->integer('minimum_redeem_points')->default(100);
            $table->json('tiers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Points de fidélité client
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->integer('points_balance')->default(0);
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_points_redeemed')->default(0);
            $table->string('tier')->default('bronze');
            $table->timestamps();

            $table->unique(['tenant_id', 'customer_id', 'program_id']);
        });

        // Transactions de points
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_points_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjust', 'bonus']);
            $table->integer('points');
            $table->text('description');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Récompenses disponibles
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['discount', 'free_month', 'upgrade', 'gift', 'service']);
            $table->integer('points_required');
            $table->decimal('value', 10, 2)->nullable();
            $table->string('tier_required')->nullable();
            $table->integer('quantity_available')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Récompenses réclamées
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_points_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->cascadeOnDelete();
            $table->integer('points_spent');
            $table->enum('status', ['pending', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_programs');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_program');
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('promotions');
    }
};
