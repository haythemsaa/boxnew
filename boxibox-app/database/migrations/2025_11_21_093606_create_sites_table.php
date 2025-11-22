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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // Site code/reference
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->string('country')->default('FR');

            // GPS Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Opening Hours (JSON format)
            $table->json('opening_hours')->nullable();

            // Capacity
            $table->integer('total_boxes')->default(0);
            $table->integer('occupied_boxes')->default(0);
            $table->decimal('occupation_rate', 5, 2)->default(0);

            // Settings
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);

            // Images
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
