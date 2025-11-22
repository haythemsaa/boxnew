<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_month']);
            $table->decimal('value', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->decimal('min_rental_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applicable_to')->nullable(); // site_ids, box_categories, etc.
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
