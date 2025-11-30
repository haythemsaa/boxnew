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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Company name
            $table->string('slug')->unique(); // Unique identifier for subdomain/routing
            $table->string('domain')->nullable()->unique(); // Custom domain
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('FR');

            // Subscription & Plan
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])->default('free');
            $table->date('trial_ends_at')->nullable();
            $table->date('subscription_ends_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Limits based on plan
            $table->integer('max_sites')->default(1);
            $table->integer('max_boxes')->default(50);
            $table->integer('max_users')->default(3);

            // Company details
            $table->string('company_number')->nullable(); // SIRET/SIREN
            $table->string('vat_number')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website')->nullable();

            // Settings (JSON)
            $table->json('settings')->nullable();
            $table->json('features')->nullable(); // Enabled features

            // Billing
            $table->decimal('monthly_revenue', 12, 2)->default(0);
            $table->integer('total_customers')->default(0);
            $table->decimal('occupation_rate', 5, 2)->default(0);

            // Stripe/Payment
            $table->string('stripe_customer_id')->nullable();
            $table->string('payment_gateway')->default('stripe');

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
