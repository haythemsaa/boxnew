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
        // Add scoring fields to leads table
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('status');
            }
            if (!Schema::hasColumn('leads', 'priority')) {
                $table->enum('priority', ['hot', 'warm', 'cold'])->default('cold')->after('score');
            }
            if (!Schema::hasColumn('leads', 'score_calculated_at')) {
                $table->timestamp('score_calculated_at')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('leads', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
        });

        // Add scoring fields to prospects table
        Schema::table('prospects', function (Blueprint $table) {
            if (!Schema::hasColumn('prospects', 'score')) {
                $table->integer('score')->default(0)->after('status');
            }
            if (!Schema::hasColumn('prospects', 'priority')) {
                $table->enum('priority', ['hot', 'warm', 'cold'])->default('cold')->after('score');
            }
            if (!Schema::hasColumn('prospects', 'score_calculated_at')) {
                $table->timestamp('score_calculated_at')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('prospects', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
        });

        // Add metadata to bookings for recovery tracking
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
        });

        // Add indexes for performance
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'score']);
        });

        Schema::table('prospects', function (Blueprint $table) {
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'priority']);
            $table->dropIndex(['tenant_id', 'score']);
            $table->dropColumn(['score', 'priority', 'score_calculated_at']);
            if (Schema::hasColumn('leads', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        Schema::table('prospects', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'priority']);
            $table->dropIndex(['tenant_id', 'score']);
            $table->dropColumn(['score', 'priority', 'score_calculated_at']);
            if (Schema::hasColumn('prospects', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
