<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Valet Storage Items - Items stored by valet service
        Schema::create('valet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('barcode')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // furniture, electronics, boxes, clothing, etc.
            $table->string('size')->default('medium'); // small, medium, large, extra_large
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('volume_m3', 8, 3)->nullable();
            $table->string('condition')->default('good'); // excellent, good, fair, damaged
            $table->json('photos')->nullable();
            $table->string('storage_location')->nullable(); // Shelf/Bin location
            $table->string('status')->default('stored'); // pending_pickup, in_transit_to_storage, stored, pending_delivery, in_transit_to_customer, delivered, disposed
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->boolean('is_fragile')->default(false);
            $table->boolean('requires_climate_control')->default(false);
            $table->text('special_instructions')->nullable();
            $table->date('storage_start_date')->nullable();
            $table->date('storage_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('barcode');
        });

        // Valet Pickups & Deliveries
        Schema::create('valet_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('type'); // pickup, delivery, pickup_delivery (both)
            $table->string('status')->default('pending'); // pending, confirmed, scheduled, in_progress, completed, cancelled
            $table->date('requested_date');
            $table->string('time_slot')->nullable(); // morning, afternoon, evening, specific time
            $table->time('scheduled_time_start')->nullable();
            $table->time('scheduled_time_end')->nullable();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vehicle_type')->nullable(); // van, truck, bike

            // Address
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('postal_code');
            $table->string('country')->default('France');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('floor')->nullable();
            $table->boolean('has_elevator')->default(false);
            $table->string('access_code')->nullable();
            $table->text('access_instructions')->nullable();

            // Contact
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();

            // Pricing
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('distance_fee', 10, 2)->default(0);
            $table->decimal('floor_fee', 10, 2)->default(0);
            $table->decimal('item_fee', 10, 2)->default(0);
            $table->decimal('total_fee', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);

            $table->text('notes')->nullable();
            $table->text('driver_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('completion_photo')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'requested_date']);
            $table->index(['assigned_driver_id', 'status']);
        });

        // Order Items (link orders to items)
        Schema::create('valet_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valet_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('valet_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_description')->nullable(); // For new items during pickup
            $table->string('category')->nullable();
            $table->string('size')->nullable();
            $table->integer('quantity')->default(1);
            $table->boolean('is_new_item')->default(false); // True for pickup of new items
            $table->string('status')->default('pending'); // pending, picked_up, delivered
            $table->timestamps();
        });

        // Valet Pricing Rules
        Schema::create('valet_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type'); // pickup, delivery, storage_small, storage_medium, storage_large, distance_per_km, floor_fee
            $table->decimal('price', 10, 2);
            $table->string('unit')->default('fixed'); // fixed, per_km, per_floor, per_item, per_m3, monthly
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });

        // Valet Zones (delivery/pickup zones with pricing)
        Schema::create('valet_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('postal_codes'); // Array of postal codes in this zone
            $table->decimal('pickup_fee', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->integer('min_lead_time_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'site_id']);
        });

        // Valet Settings
        Schema::create('valet_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('valet_enabled')->default(true);
            $table->boolean('allow_same_day')->default(false);
            $table->integer('min_lead_time_hours')->default(24);
            $table->integer('max_items_per_order')->default(50);
            $table->time('earliest_time')->default('08:00');
            $table->time('latest_time')->default('20:00');
            $table->json('available_days')->nullable(); // [1,2,3,4,5] for Mon-Fri
            $table->json('time_slots')->nullable(); // Available time slots
            $table->decimal('free_delivery_threshold', 10, 2)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('pickup_instructions')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'site_id']);
        });

        // Driver/Vehicle management for valet
        Schema::create('valet_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone');
            $table->string('license_number')->nullable();
            $table->string('vehicle_type'); // van, truck, bike
            $table->string('vehicle_plate')->nullable();
            $table->decimal('max_capacity_kg', 8, 2)->nullable();
            $table->decimal('max_capacity_m3', 8, 2)->nullable();
            $table->string('status')->default('available'); // available, busy, offline
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Driver routes/schedules
        Schema::create('valet_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('valet_driver_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('planned'); // planned, in_progress, completed
            $table->integer('total_stops')->default(0);
            $table->integer('completed_stops')->default(0);
            $table->decimal('total_distance_km', 10, 2)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('optimized_order')->nullable(); // Order of order IDs
            $table->timestamps();

            $table->index(['tenant_id', 'date']);
            $table->index(['valet_driver_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valet_routes');
        Schema::dropIfExists('valet_drivers');
        Schema::dropIfExists('valet_settings');
        Schema::dropIfExists('valet_zones');
        Schema::dropIfExists('valet_pricing');
        Schema::dropIfExists('valet_order_items');
        Schema::dropIfExists('valet_orders');
        Schema::dropIfExists('valet_items');
    }
};
