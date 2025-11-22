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
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('floor_id')->nullable()->constrained()->onDelete('set null');

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);

            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->decimal('scale', 10, 2)->default(1);
            $table->string('unit', 10)->default('meters');

            $table->json('elements'); // Walls, boxes, corridors, etc.

            $table->string('background_image')->nullable();
            $table->decimal('background_opacity', 3, 2)->default(0.5);

            $table->boolean('show_grid')->default(true);
            $table->integer('grid_size')->default(10);

            $table->integer('total_boxes_on_plan')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floor_plans');
    }
};
