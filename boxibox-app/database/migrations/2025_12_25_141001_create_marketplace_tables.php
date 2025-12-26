<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marketplace Integrations (SpareFoot, SelfStorage.com, etc.)
        if (!Schema::hasTable('marketplace_integrations')) {
            Schema::create('marketplace_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

                $table->string('platform'); // sparefoot, selfstorage, storagecafe, google_business
                $table->string('platform_account_id')->nullable();
                $table->boolean('is_active')->default(true);

                // API Credentials
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->text('access_token')->nullable();
                $table->datetime('token_expires_at')->nullable();
                $table->string('webhook_url')->nullable();
                $table->string('webhook_secret')->nullable();

                // Settings
                $table->boolean('auto_sync_inventory')->default(true);
                $table->boolean('auto_sync_prices')->default(true);
                $table->boolean('auto_accept_leads')->default(false);
                $table->integer('sync_interval_minutes')->default(60);
                $table->decimal('price_markup_percent', 5, 2)->default(0);

                // Commission
                $table->decimal('commission_percent', 5, 2)->nullable();
                $table->decimal('lead_cost', 10, 2)->nullable();
                $table->enum('commission_type', ['percent', 'fixed', 'per_lead'])->default('percent');

                $table->datetime('last_sync_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'platform']);
            });
        }

        // Marketplace Listings
        if (!Schema::hasTable('marketplace_listings')) {
            Schema::create('marketplace_listings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('integration_id')->constrained('marketplace_integrations')->onDelete('cascade');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('box_id')->nullable()->constrained()->onDelete('cascade');

                $table->string('external_listing_id')->nullable();
                $table->string('listing_type'); // unit, facility, promotion

                // Listing details
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('unit_type')->nullable(); // small, medium, large, xl, parking
                $table->decimal('size_sqft', 8, 2)->nullable();
                $table->decimal('size_m2', 8, 2)->nullable();

                // Pricing
                $table->decimal('listed_price', 10, 2);
                $table->decimal('original_price', 10, 2)->nullable();
                $table->decimal('promo_price', 10, 2)->nullable();
                $table->string('promo_description')->nullable();
                $table->date('promo_start_date')->nullable();
                $table->date('promo_end_date')->nullable();

                // Features
                $table->json('features')->nullable(); // climate, security, drive_up, etc.
                $table->json('images')->nullable();

                // Availability
                $table->boolean('is_available')->default(true);
                $table->integer('quantity_available')->default(1);

                // Status
                $table->enum('status', ['draft', 'pending', 'active', 'paused', 'rejected', 'expired'])->default('draft');
                $table->string('rejection_reason')->nullable();
                $table->datetime('published_at')->nullable();
                $table->datetime('expires_at')->nullable();

                // Performance
                $table->integer('views_count')->default(0);
                $table->integer('clicks_count')->default(0);
                $table->integer('leads_count')->default(0);
                $table->integer('conversions_count')->default(0);

                // Sync
                $table->datetime('last_synced_at')->nullable();
                $table->json('sync_errors')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
                $table->index(['integration_id', 'external_listing_id']);
            });
        }

        // Marketplace Leads
        if (!Schema::hasTable('marketplace_leads')) {
            Schema::create('marketplace_leads', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('integration_id')->constrained('marketplace_integrations')->onDelete('cascade');
                $table->foreignId('listing_id')->nullable()->constrained('marketplace_listings')->onDelete('set null');
                $table->foreignId('site_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

                // External reference
                $table->string('external_lead_id')->nullable();
                $table->string('platform');

                // Lead info
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone')->nullable();
                $table->string('unit_size_requested')->nullable();
                $table->date('move_in_date')->nullable();
                $table->text('message')->nullable();

                // Source tracking
                $table->string('source_url')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();

                // Status
                $table->enum('status', [
                    'new',
                    'contacted',
                    'qualified',
                    'tour_scheduled',
                    'converted',
                    'lost',
                    'duplicate'
                ])->default('new');

                $table->datetime('first_contacted_at')->nullable();
                $table->datetime('converted_at')->nullable();
                $table->string('lost_reason')->nullable();

                // Conversion
                $table->foreignId('converted_contract_id')->nullable();
                $table->decimal('converted_value', 10, 2)->nullable();

                // Cost tracking
                $table->decimal('lead_cost', 10, 2)->nullable();
                $table->boolean('cost_charged')->default(false);

                // Response time tracking
                $table->integer('response_time_minutes')->nullable();

                $table->json('raw_data')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status', 'created_at']);
                $table->index(['platform', 'external_lead_id']);
            });
        }

        // Marketplace Analytics
        if (!Schema::hasTable('marketplace_analytics')) {
            Schema::create('marketplace_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('integration_id')->constrained('marketplace_integrations')->onDelete('cascade');
                $table->date('date');

                $table->integer('impressions')->default(0);
                $table->integer('clicks')->default(0);
                $table->integer('leads')->default(0);
                $table->integer('conversions')->default(0);
                $table->decimal('spend', 10, 2)->default(0);
                $table->decimal('revenue', 10, 2)->default(0);

                $table->decimal('ctr', 5, 2)->nullable(); // Click-through rate
                $table->decimal('conversion_rate', 5, 2)->nullable();
                $table->decimal('cost_per_lead', 10, 2)->nullable();
                $table->decimal('cost_per_conversion', 10, 2)->nullable();
                $table->decimal('roas', 8, 2)->nullable(); // Return on ad spend

                $table->timestamps();

                $table->unique(['integration_id', 'date']);
                $table->index(['tenant_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_analytics');
        Schema::dropIfExists('marketplace_leads');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('marketplace_integrations');
    }
};
