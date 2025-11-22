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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'occupation_based',
                'seasonal',
                'duration_discount',
                'size_based',
                'promotional'
            ]);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);

            $table->json('conditions');

            $table->enum('adjustment_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('adjustment_value', 10, 2);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();

            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->boolean('stackable')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
