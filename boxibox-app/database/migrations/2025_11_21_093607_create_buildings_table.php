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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->integer('total_floors')->default(1);
            $table->enum('type', ['indoor', 'outdoor', 'mixed'])->default('indoor');
            $table->text('description')->nullable();
            $table->boolean('has_elevator')->default(false);
            $table->boolean('has_security')->default(false);
            $table->json('amenities')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'site_id']);
            $table->unique(['site_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
