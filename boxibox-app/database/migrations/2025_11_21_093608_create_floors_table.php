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
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->integer('floor_number'); // 0 = Ground, 1 = First, -1 = Basement
            $table->string('name')->nullable(); // e.g., "Ground Floor", "Basement"
            $table->unsignedBigInteger('floor_plan_id')->nullable(); // FK constraint will be added later after floor_plans table exists
            $table->integer('total_boxes')->default(0);
            $table->decimal('total_area', 10, 2)->nullable(); // in m²
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'site_id', 'building_id']);
            $table->unique(['building_id', 'floor_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
