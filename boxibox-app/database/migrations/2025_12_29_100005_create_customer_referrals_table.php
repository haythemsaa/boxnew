<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add referral code to customers
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->unique()->after('status');
            }
            if (!Schema::hasColumn('customers', 'referred_by_customer_id')) {
                $table->foreignId('referred_by_customer_id')->nullable()->after('referral_code')->constrained('customers')->onDelete('set null');
            }
            if (!Schema::hasColumn('customers', 'referral_credits')) {
                $table->decimal('referral_credits', 10, 2)->default(0)->after('referred_by_customer_id');
            }
        });

        // Create referrals table
        Schema::create('customer_referrals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Referrer (parrain)
            $table->foreignId('referrer_customer_id')->constrained('customers')->onDelete('cascade');

            // Referred (filleul)
            $table->foreignId('referred_customer_id')->nullable()->constrained('customers')->onDelete('set null');

            // Invite details (before registration)
            $table->string('referral_code'); // Code used
            $table->string('invited_email')->nullable();
            $table->string('invited_phone')->nullable();
            $table->string('invited_name')->nullable();

            // Status
            $table->enum('status', [
                'pending',      // Invitation envoyée
                'registered',   // Filleul inscrit
                'converted',    // Filleul a souscrit un contrat
                'rewarded',     // Récompenses attribuées
                'expired',      // Invitation expirée
                'cancelled'     // Annulé
            ])->default('pending');

            // Contract info
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

            // Rewards
            $table->decimal('referrer_reward', 10, 2)->default(0); // Reward for parrain
            $table->decimal('referred_reward', 10, 2)->default(0); // Discount for filleul
            $table->enum('reward_type', ['credit', 'discount_percent', 'free_month', 'cash'])->default('credit');
            $table->boolean('referrer_reward_paid')->default(false);
            $table->boolean('referred_reward_applied')->default(false);
            $table->timestamp('reward_paid_at')->nullable();

            // Tracking
            $table->string('source')->nullable(); // email, sms, social, link
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('ip_address')->nullable();

            // Validity
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'referrer_customer_id']);
            $table->index(['referral_code']);
            $table->index(['status']);
            $table->index(['invited_email']);
        });

        // Create referral settings table for tenant customization
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');

            // Program settings
            $table->boolean('is_active')->default(true);
            $table->string('program_name')->default('Programme Parrainage');

            // Rewards configuration
            $table->decimal('referrer_reward_amount', 10, 2)->default(25.00);
            $table->decimal('referred_reward_amount', 10, 2)->default(25.00);
            $table->enum('reward_type', ['credit', 'discount_percent', 'free_month', 'cash'])->default('credit');

            // Conditions
            $table->unsignedInteger('min_contract_months')->default(1); // Minimum contract duration to qualify
            $table->unsignedInteger('reward_delay_days')->default(30); // Days after contract start to pay reward
            $table->unsignedInteger('referral_expiry_days')->default(90); // Days until invitation expires
            $table->unsignedInteger('max_referrals_per_customer')->nullable(); // null = unlimited

            // Messages
            $table->text('email_template')->nullable();
            $table->text('sms_template')->nullable();
            $table->text('terms_conditions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
        Schema::dropIfExists('customer_referrals');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'referral_credits')) {
                $table->dropColumn('referral_credits');
            }
            if (Schema::hasColumn('customers', 'referred_by_customer_id')) {
                $table->dropForeign(['referred_by_customer_id']);
                $table->dropColumn('referred_by_customer_id');
            }
            if (Schema::hasColumn('customers', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }
};
