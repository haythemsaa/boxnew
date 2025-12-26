<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral Settings per tenant
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);

            // Referrer reward (the person who refers)
            $table->enum('referrer_reward_type', ['percentage', 'fixed', 'free_month'])->default('percentage');
            $table->decimal('referrer_reward_value', 10, 2)->default(10); // 10% or 10€ or 1 month
            $table->string('referrer_reward_description')->nullable();
            $table->integer('referrer_reward_max_uses')->nullable(); // null = unlimited

            // Referee reward (the new customer)
            $table->enum('referee_reward_type', ['percentage', 'fixed', 'free_month'])->default('percentage');
            $table->decimal('referee_reward_value', 10, 2)->default(10); // 10% or 10€ or 1 month
            $table->string('referee_reward_description')->nullable();

            // Rules
            $table->integer('min_rental_months')->default(1); // Min rental for referral to be valid
            $table->integer('max_referrals_per_customer')->nullable(); // null = unlimited
            $table->integer('reward_delay_days')->default(30); // Days after signup to get reward
            $table->boolean('require_active_contract')->default(true); // Referrer must have active contract

            $table->timestamps();

            $table->unique(['tenant_id', 'site_id']);
        });

        // Referral Codes - each customer can have a unique code
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade'); // null = global promo
            $table->string('code', 20)->unique();
            $table->string('name')->nullable(); // e.g., "John's referral code"
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('uses_count')->default(0);
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Custom tracking data
            $table->timestamps();

            $table->index(['tenant_id', 'code']);
            $table->index(['customer_id', 'is_active']);
        });

        // Referrals - tracks actual referrals
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('referral_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('referee_customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('status', ['pending', 'qualified', 'rewarded', 'expired', 'cancelled'])->default('pending');
            $table->dateTime('qualified_at')->nullable(); // When referral became eligible for reward
            $table->dateTime('rewarded_at')->nullable();

            // Snapshot of reward settings at time of referral
            $table->json('reward_snapshot')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['referrer_customer_id', 'status']);
        });

        // Referral Rewards - tracks individual rewards given
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('referral_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('recipient_type', ['referrer', 'referee']);

            $table->enum('reward_type', ['percentage', 'fixed', 'free_month', 'credit']);
            $table->decimal('reward_value', 10, 2);
            $table->decimal('reward_amount', 10, 2)->nullable(); // Actual € amount
            $table->string('description')->nullable();

            $table->enum('status', ['pending', 'applied', 'expired', 'cancelled'])->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->foreignId('applied_to_invoice_id')->nullable(); // If applied to invoice
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('referral_settings');
    }
};
