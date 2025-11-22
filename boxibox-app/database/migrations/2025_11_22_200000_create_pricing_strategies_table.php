<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('strategy_type'); // 'occupancy', 'seasonal', 'duration', 'promotion'
            $table->json('rules'); // Flexible JSON for strategy rules
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('min_discount_percentage', 5, 2)->default(0);
            $table->decimal('max_discount_percentage', 5, 2)->default(50);
            $table->integer('priority')->default(0); // Higher number = higher priority
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['site_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_strategies');
    }
};
