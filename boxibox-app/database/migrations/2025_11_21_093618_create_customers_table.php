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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Personal Information
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->enum('civility', ['mr', 'mrs', 'ms', 'other'])->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->date('birth_date')->nullable();

            // Contact
            $table->string('email')->index();
            $table->string('phone');
            $table->string('mobile')->nullable();

            // Address
            $table->text('address');
            $table->string('city');
            $table->string('postal_code');
            $table->string('country')->default('FR');

            // Company Info
            $table->string('company_number')->nullable();
            $table->string('vat_number')->nullable();

            // Identity Documents
            $table->enum('id_type', ['id_card', 'passport', 'driving_license'])->nullable();
            $table->string('id_number')->nullable();
            $table->date('id_expiry')->nullable();

            // Billing
            $table->boolean('same_billing_address')->default(true);
            $table->text('billing_address')->nullable();

            // Status & Scoring
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->integer('credit_score')->default(0);

            // Statistics
            $table->integer('total_contracts')->default(0);
            $table->integer('active_contracts')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('outstanding_balance', 10, 2)->default(0);

            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            // RGPD
            $table->timestamp('gdpr_consent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
