<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix the role enum to include 'agent' and 'customer' values for live chat.
     */
    public function up(): void
    {
        // Modify the enum to include all necessary roles
        DB::statement("ALTER TABLE chat_messages MODIFY COLUMN role ENUM('user', 'assistant', 'system', 'agent', 'customer') DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum (will fail if 'agent' or 'customer' values exist)
        DB::statement("ALTER TABLE chat_messages MODIFY COLUMN role ENUM('user', 'assistant', 'system') DEFAULT 'user'");
    }
};
