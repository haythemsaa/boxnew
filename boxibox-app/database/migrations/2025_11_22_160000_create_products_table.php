<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->enum('type', ['one_time', 'recurring'])->default('one_time');
            $table->enum('category', ['lock', 'box', 'insurance', 'cleaning', 'packaging', 'other']);
            $table->decimal('price', 10, 2);
            $table->enum('billing_period', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_inventory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
