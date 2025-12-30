<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Item details
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'furniture',      // Meubles
                'electronics',    // Électronique
                'clothing',       // Vêtements
                'books',          // Livres/Documents
                'sports',         // Sports/Loisirs
                'tools',          // Outils
                'appliances',     // Électroménager
                'decor',          // Décoration
                'collectibles',   // Collections
                'seasonal',       // Saisonnier
                'vehicle',        // Véhicule/Pièces
                'other'           // Autre
            ])->default('other');

            // Quantity and value
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');

            // Physical attributes
            $table->string('condition')->nullable(); // new, good, fair, poor
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();

            // Dimensions (cm)
            $table->unsignedInteger('length')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('weight', 8, 2)->nullable(); // kg

            // Photos
            $table->json('photos')->nullable(); // Array of photo paths

            // Location in box
            $table->string('location_in_box')->nullable(); // e.g., "Top shelf", "Back left"

            // Tags for search
            $table->json('tags')->nullable();

            // Insurance
            $table->boolean('is_insured')->default(false);
            $table->string('insurance_policy_number')->nullable();

            // Status
            $table->enum('status', ['stored', 'removed', 'damaged', 'lost'])->default('stored');
            $table->timestamp('stored_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->text('removal_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['box_id', 'status']);
            $table->index(['contract_id', 'status']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_inventory_items');
    }
};
