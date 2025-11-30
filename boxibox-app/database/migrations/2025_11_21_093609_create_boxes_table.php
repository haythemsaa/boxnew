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
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('floor_id')->nullable()->constrained()->onDelete('set null');

            // Identification
            $table->string('number')->index(); // Box number/reference
            $table->string('name')->nullable(); // Optional friendly name

            // Dimensions (in meters)
            $table->decimal('length', 8, 2); // Length in meters
            $table->decimal('width', 8, 2);  // Width in meters
            $table->decimal('height', 8, 2); // Height in meters
            $table->decimal('volume', 10, 2)->virtualAs('length * width * height'); // Calculated volume m³

            // Status
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'unavailable'])->default('available');
            $table->date('available_from')->nullable();

            // Pricing
            $table->decimal('base_price', 10, 2); // Base monthly price
            $table->decimal('current_price', 10, 2)->nullable(); // Current price (after dynamic pricing)

            // Features (checkboxes)
            $table->boolean('climate_controlled')->default(false);
            $table->boolean('has_electricity')->default(false);
            $table->boolean('has_alarm')->default(false);
            $table->boolean('has_camera')->default(false);
            $table->boolean('drive_up_access')->default(false);
            $table->boolean('ground_floor')->default(false);
            $table->boolean('indoor')->default(true);

            // Floor Plan Position
            $table->json('position')->nullable(); // {x, y} coordinates on floor plan

            // Current rental
            $table->unsignedBigInteger('current_contract_id')->nullable(); // FK will be added later
            $table->unsignedBigInteger('current_customer_id')->nullable(); // FK will be added later
            $table->date('occupied_since')->nullable();

            // Access
            $table->string('access_code')->nullable();
            $table->string('lock_serial')->nullable(); // Smart lock serial number

            // Media
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();

            // Notes
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable(); // Staff only

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'site_id']);
            $table->index('status');
            $table->index(['tenant_id', 'number']);
            $table->unique(['site_id', 'number']); // Unique box number per site
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
