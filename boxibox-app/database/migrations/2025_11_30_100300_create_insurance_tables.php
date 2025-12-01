<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fournisseurs d'assurance partenaires
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // AXA, Allianz, Groupama, etc.
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();

            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('api_key')->nullable();

            $table->decimal('commission_rate', 5, 2)->default(15); // % de commission

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Plans d'assurance disponibles
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('insurance_providers')->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Couverture
            $table->decimal('coverage_amount', 12, 2); // Montant max couvert
            $table->json('covered_risks'); // ["fire", "flood", "theft", "damage", ...]
            $table->json('exclusions')->nullable();

            // Tarification
            $table->enum('pricing_type', ['fixed', 'percentage', 'per_sqm'])->default('fixed');
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->decimal('percentage_of_value', 5, 2)->nullable(); // Si pricing_type = percentage
            $table->decimal('price_per_sqm', 10, 2)->nullable(); // Si pricing_type = per_sqm

            // Franchise
            $table->decimal('deductible', 10, 2)->default(0);
            $table->decimal('deductible_percentage', 5, 2)->nullable();

            $table->integer('min_contract_months')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);

            $table->timestamps();
        });

        // Polices d'assurance des clients
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('insurance_plans');

            $table->string('policy_number')->unique();
            $table->string('external_policy_id')->nullable(); // ID chez l'assureur

            $table->decimal('coverage_amount', 12, 2);
            $table->decimal('premium_monthly', 10, 2);
            $table->decimal('premium_yearly', 10, 2)->nullable();
            $table->decimal('deductible', 10, 2)->default(0);

            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled', 'expired'])->default('pending');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Déclaration de valeur par le client
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->text('items_description')->nullable();
            $table->json('items_inventory')->nullable();

            // Paiement
            $table->enum('payment_frequency', ['monthly', 'yearly'])->default('monthly');
            $table->boolean('auto_renew')->default(true);
            $table->date('next_payment_date')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        // Sinistres
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('insurance_policies')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('claim_number')->unique();
            $table->string('external_claim_id')->nullable();

            $table->date('incident_date');
            $table->text('incident_description');
            $table->enum('incident_type', ['fire', 'flood', 'theft', 'damage', 'other']);

            $table->decimal('claimed_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'additional_info_required',
                'approved',
                'partially_approved',
                'rejected',
                'paid',
                'closed'
            ])->default('draft');

            $table->text('status_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Documents
            $table->json('documents')->nullable(); // [{name, url, type}, ...]
            $table->json('photos')->nullable();

            // Suivi
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['policy_id', 'status']);
        });

        // Historique des paiements d'assurance
        Schema::create('insurance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('insurance_policies')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained();

            $table->decimal('amount', 10, 2);
            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });

        // Certificats d'assurance générés
        Schema::create('insurance_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('insurance_policies')->cascadeOnDelete();

            $table->string('certificate_number')->unique();
            $table->string('file_path');
            $table->date('valid_from');
            $table->date('valid_until');

            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_certificates');
        Schema::dropIfExists('insurance_payments');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('insurance_providers');
    }
};
