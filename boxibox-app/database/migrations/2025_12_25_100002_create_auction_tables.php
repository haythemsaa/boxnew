<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lien Sales / Auction System - Enchères pour unités impayées
     */
    public function up(): void
    {
        // Auction items (units going to auction)
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

            // Auction reference
            $table->string('auction_number')->unique();

            // Debt info
            $table->decimal('total_debt', 10, 2);
            $table->decimal('storage_fees', 10, 2)->default(0);
            $table->decimal('late_fees', 10, 2)->default(0);
            $table->decimal('legal_fees', 10, 2)->default(0);
            $table->integer('days_overdue')->default(0);

            // Auction details
            $table->text('contents_description')->nullable();
            $table->json('contents_photos')->nullable();
            $table->decimal('starting_bid', 10, 2)->default(0);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('current_bid', 10, 2)->default(0);
            $table->decimal('winning_bid', 10, 2)->nullable();
            $table->foreignId('winning_bidder_id')->nullable();

            // Status & Dates
            $table->enum('status', [
                'pending',          // Awaiting legal process
                'notice_sent',      // Legal notice sent to customer
                'scheduled',        // Auction scheduled
                'active',           // Auction is live
                'ended',            // Auction ended
                'sold',             // Item sold
                'unsold',           // No bids, item unsold
                'redeemed',         // Customer paid debt before auction
                'cancelled'         // Auction cancelled
            ])->default('pending');

            $table->date('first_notice_date')->nullable();
            $table->date('second_notice_date')->nullable();
            $table->date('final_notice_date')->nullable();
            $table->datetime('auction_start_date')->nullable();
            $table->datetime('auction_end_date')->nullable();
            $table->datetime('sold_at')->nullable();

            // Legal compliance
            $table->string('legal_jurisdiction')->default('FR');
            $table->json('legal_documents')->nullable();
            $table->boolean('legal_requirements_met')->default(false);
            $table->text('legal_notes')->nullable();

            // Platform integration
            $table->string('external_platform')->nullable();
            $table->string('external_listing_id')->nullable();
            $table->string('external_listing_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['site_id', 'status']);
            $table->index('auction_end_date');
        });

        // Auction bids
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('bidder_id')->nullable();

            // Bidder info (for external/anonymous bidders)
            $table->string('bidder_name');
            $table->string('bidder_email');
            $table->string('bidder_phone')->nullable();

            $table->decimal('amount', 10, 2);
            $table->boolean('is_winning')->default(false);
            $table->boolean('is_auto_bid')->default(false);
            $table->decimal('max_auto_bid', 10, 2)->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();

            $table->index(['auction_id', 'amount']);
        });

        // Auction notices (legal notifications)
        Schema::create('auction_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->enum('notice_type', [
                'first_warning',
                'second_warning',
                'final_notice',
                'auction_scheduled',
                'auction_reminder',
                'auction_result'
            ]);
            $table->enum('channel', ['email', 'sms', 'mail', 'registered_mail'])->default('email');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'returned'])->default('pending');
            $table->text('content')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Auction settings per tenant
        Schema::create('auction_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(true);

            // Trigger rules
            $table->integer('days_before_first_notice')->default(30);
            $table->integer('days_before_second_notice')->default(45);
            $table->integer('days_before_final_notice')->default(60);
            $table->integer('days_before_auction')->default(75);
            $table->decimal('minimum_debt_amount', 10, 2)->default(100);

            // Auction settings
            $table->integer('auction_duration_days')->default(7);
            $table->decimal('starting_bid_percentage', 5, 2)->default(10);
            $table->boolean('require_reserve_price')->default(false);
            $table->boolean('allow_proxy_bidding')->default(true);

            // Platform integration
            $table->string('preferred_platform')->nullable();
            $table->json('platform_credentials')->nullable();
            $table->boolean('auto_list_on_platform')->default(false);

            // Legal
            $table->string('legal_jurisdiction')->default('FR');
            $table->text('first_notice_template')->nullable();
            $table->text('second_notice_template')->nullable();
            $table->text('final_notice_template')->nullable();

            // Fees
            $table->decimal('platform_fee_percentage', 5, 2)->default(10);
            $table->decimal('admin_fee', 10, 2)->default(50);

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_settings');
        Schema::dropIfExists('auction_notices');
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('auctions');
    }
};
