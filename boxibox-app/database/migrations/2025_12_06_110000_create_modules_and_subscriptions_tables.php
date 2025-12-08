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
        // Table des modules disponibles
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description');
                $table->string('icon')->nullable();
                $table->string('color')->default('blue');
                $table->string('category');
                $table->decimal('monthly_price', 10, 2)->default(0);
                $table->decimal('yearly_price', 10, 2)->default(0);
                $table->json('features')->nullable();
                $table->json('routes')->nullable();
                $table->json('dependencies')->nullable();
                $table->boolean('is_core')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Table des packs/plans
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description');
                $table->string('badge_color')->default('blue');
                $table->decimal('monthly_price', 10, 2);
                $table->decimal('yearly_price', 10, 2);
                $table->decimal('yearly_discount', 5, 2)->default(20);
                $table->integer('max_sites')->nullable();
                $table->integer('max_boxes')->nullable();
                $table->integer('max_users')->nullable();
                $table->integer('max_customers')->nullable();
                $table->boolean('includes_support')->default(true);
                $table->string('support_level')->default('email');
                $table->json('included_modules')->nullable();
                $table->json('features')->nullable();
                $table->boolean('is_popular')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Abonnements des tenants
        if (!Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans');
                $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
                $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('trial');
                $table->date('trial_ends_at')->nullable();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->date('cancelled_at')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('payment_method')->nullable();
                $table->string('stripe_subscription_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        // Modules additionnels par tenant (hors pack)
        if (!Schema::hasTable('tenant_modules')) {
            Schema::create('tenant_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('module_id')->constrained()->cascadeOnDelete();
                $table->enum('status', ['active', 'trial', 'expired', 'disabled'])->default('active');
                $table->date('trial_ends_at')->nullable();
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
                $table->boolean('is_demo')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'module_id']);
                $table->index(['tenant_id', 'status']);
            });
        }

        // Historique des démos
        if (!Schema::hasTable('demo_history')) {
            Schema::create('demo_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
                $table->enum('demo_type', ['module', 'plan', 'full_app'])->default('module');
                $table->date('started_at');
                $table->date('ends_at');
                $table->date('converted_at')->nullable();
                $table->enum('status', ['active', 'expired', 'converted', 'cancelled'])->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Ajouter colonnes au tenant
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'subscription_status')) {
                $table->enum('subscription_status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('trial')->after('is_active');
            }
            if (!Schema::hasColumn('tenants', 'current_plan_id')) {
                $table->foreignId('current_plan_id')->nullable()->after('subscription_ends_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'subscription_status')) {
                $table->dropColumn('subscription_status');
            }
            if (Schema::hasColumn('tenants', 'current_plan_id')) {
                $table->dropColumn('current_plan_id');
            }
        });

        Schema::dropIfExists('demo_history');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('modules');
    }
};
