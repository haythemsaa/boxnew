<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour le système d'abonnement hybride
 *
 * - Plans avec quotas email/SMS inclus
 * - Suivi de consommation par tenant
 * - Packs de crédits additionnels
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table des plans d'abonnement (skip if already exists from another migration)
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Starter, Pro, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Tarification
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Quotas Email
            $table->integer('emails_per_month')->default(500); // 0 = illimité
            $table->boolean('email_tracking_enabled')->default(true);
            $table->boolean('custom_email_provider_allowed')->default(false);

            // Quotas SMS
            $table->integer('sms_per_month')->default(0);
            $table->boolean('custom_sms_provider_allowed')->default(false);

            // Limites générales
            $table->integer('max_sites')->default(1); // 0 = illimité
            $table->integer('max_boxes')->default(100); // 0 = illimité
            $table->integer('max_users')->default(3); // 0 = illimité
            $table->integer('max_customers')->default(500); // 0 = illimité

            // Fonctionnalités
            $table->json('features')->nullable(); // Liste des features incluses
            $table->boolean('api_access')->default(false);
            $table->boolean('whitelabel')->default(false);
            $table->boolean('priority_support')->default(false);

            // Ordre d'affichage et statut
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Mis en avant
            $table->boolean('is_default')->default(false); // Plan par défaut pour nouveaux tenants

            $table->timestamps();
            });
        }

        // Table des abonnements des tenants
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();

            // Période
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Statut
            $table->enum('status', ['active', 'trial', 'past_due', 'cancelled', 'expired'])->default('trial');

            // Paiement
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();

            // Surcharges optionnelles (quotas custom)
            $table->integer('extra_emails_per_month')->default(0);
            $table->integer('extra_sms_per_month')->default(0);
            $table->decimal('extra_monthly_cost', 10, 2)->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Table de suivi de consommation mensuelle
        Schema::create('tenant_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('period_start'); // Premier jour du mois
            $table->date('period_end'); // Dernier jour du mois

            // Consommation Email
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_quota')->default(0); // Quota au moment de l'enregistrement
            $table->integer('emails_from_credits')->default(0); // Utilisés depuis les crédits achetés

            // Consommation SMS
            $table->integer('sms_sent')->default(0);
            $table->integer('sms_quota')->default(0);
            $table->integer('sms_from_credits')->default(0);

            // Statistiques
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);
            $table->integer('sms_delivered')->default(0);
            $table->integer('sms_replied')->default(0);

            // Coûts (pour facturation des dépassements)
            $table->decimal('overage_cost', 10, 2)->default(0);
            $table->boolean('overage_billed')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'period_start']);
            $table->index(['tenant_id', 'period_start']);
        });

        // Table des crédits achetés (packs additionnels)
        Schema::create('tenant_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Type de crédit
            $table->enum('type', ['email', 'sms']);

            // Quantités
            $table->integer('credits_purchased');
            $table->integer('credits_remaining');

            // Achat
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_method')->nullable(); // stripe, manual, promo
            $table->string('stripe_payment_id')->nullable();

            // Validité
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable(); // null = jamais

            // Statut
            $table->enum('status', ['active', 'exhausted', 'expired'])->default('active');

            $table->timestamps();

            $table->index(['tenant_id', 'type', 'status']);
        });

        // Table des packs de crédits disponibles à l'achat
        Schema::create('credit_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Pack 100 SMS", "Pack 1000 Emails"
            $table->enum('type', ['email', 'sms']);
            $table->integer('credits'); // Nombre de crédits
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('price_per_unit', 10, 4)->nullable(); // Prix unitaire calculé
            $table->integer('validity_days')->nullable(); // Durée de validité en jours
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Ajouter les colonnes de quota au tenant
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('is_active');
            $table->integer('emails_sent_this_month')->default(0)->after('subscription_plan_id');
            $table->integer('sms_sent_this_month')->default(0)->after('emails_sent_this_month');
            $table->timestamp('usage_reset_at')->nullable()->after('sms_sent_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan_id',
                'emails_sent_this_month',
                'sms_sent_this_month',
                'usage_reset_at'
            ]);
        });

        Schema::dropIfExists('credit_packs');
        Schema::dropIfExists('tenant_credits');
        Schema::dropIfExists('tenant_usage');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
