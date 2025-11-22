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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])->default('free');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

            $table->enum('status', [
                'active',
                'trialing',
                'past_due',
                'cancelled',
                'expired'
            ])->default('trialing');

            $table->date('trial_ends_at')->nullable();
            $table->date('started_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->date('cancelled_at')->nullable();

            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();

            $table->integer('quantity_sites')->default(1);
            $table->integer('quantity_boxes')->default(50);
            $table->integer('quantity_users')->default(3);

            $table->json('features')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
