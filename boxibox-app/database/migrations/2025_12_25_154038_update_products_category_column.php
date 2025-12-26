<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the category enum to include all new categories
        DB::statement("ALTER TABLE products MODIFY COLUMN category ENUM('lock', 'box', 'packaging', 'supplies', 'electricity', 'wifi', 'insurance', 'cleaning', 'security', 'moving', 'other') NOT NULL DEFAULT 'other'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN category ENUM('lock', 'box', 'insurance', 'cleaning', 'packaging', 'other') NOT NULL");
    }
};
