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
        // Update leads table with AI scoring fields
        Schema::table('leads', function (Blueprint $table) {
            // Add conversion probability
            if (!Schema::hasColumn('leads', 'conversion_probability')) {
                $table->decimal('conversion_probability', 5, 2)->nullable()->after('score');
            }

            // Add score breakdown (JSON)
            if (!Schema::hasColumn('leads', 'score_breakdown')) {
                $table->json('score_breakdown')->nullable()->after('conversion_probability');
            }

            // Add score factors (JSON)
            if (!Schema::hasColumn('leads', 'score_factors')) {
                $table->json('score_factors')->nullable()->after('score_breakdown');
            }

            // Add last_contacted_at for recommendations
            if (!Schema::hasColumn('leads', 'last_contacted_at')) {
                $table->timestamp('last_contacted_at')->nullable()->after('score_calculated_at');
            }

            // Add last_activity_at for engagement tracking
            if (!Schema::hasColumn('leads', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_contacted_at');
            }
        });

        // Update priority enum to include new values
        // MySQL doesn't allow easy enum modification, so we need to use raw SQL
        try {
            DB::statement("ALTER TABLE leads MODIFY COLUMN priority ENUM('very_hot', 'hot', 'warm', 'lukewarm', 'cold') DEFAULT 'cold'");
        } catch (\Exception $e) {
            // Column might not exist or already have the right type
        }

        // Update prospects table with AI scoring fields
        Schema::table('prospects', function (Blueprint $table) {
            // Add conversion probability
            if (!Schema::hasColumn('prospects', 'conversion_probability')) {
                $table->decimal('conversion_probability', 5, 2)->nullable()->after('score');
            }

            // Add score breakdown (JSON)
            if (!Schema::hasColumn('prospects', 'score_breakdown')) {
                $table->json('score_breakdown')->nullable()->after('conversion_probability');
            }

            // Add score factors (JSON)
            if (!Schema::hasColumn('prospects', 'score_factors')) {
                $table->json('score_factors')->nullable()->after('score_breakdown');
            }

            // Add last_contacted_at for recommendations
            if (!Schema::hasColumn('prospects', 'last_contacted_at')) {
                $table->timestamp('last_contacted_at')->nullable()->after('score_calculated_at');
            }

            // Add last_activity_at for engagement tracking
            if (!Schema::hasColumn('prospects', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_contacted_at');
            }
        });

        // Update priority enum for prospects
        try {
            DB::statement("ALTER TABLE prospects MODIFY COLUMN priority ENUM('very_hot', 'hot', 'warm', 'lukewarm', 'cold') DEFAULT 'cold'");
        } catch (\Exception $e) {
            // Column might not exist or already have the right type
        }

        // Add indexes for AI scoring queries
        Schema::table('leads', function (Blueprint $table) {
            // Index for conversion probability queries
            if (!$this->indexExists('leads', 'leads_conversion_probability_index')) {
                $table->index('conversion_probability', 'leads_conversion_probability_index');
            }
        });

        Schema::table('prospects', function (Blueprint $table) {
            // Index for conversion probability queries
            if (!$this->indexExists('prospects', 'prospects_conversion_probability_index')) {
                $table->index('conversion_probability', 'prospects_conversion_probability_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if ($this->indexExists('leads', 'leads_conversion_probability_index')) {
                $table->dropIndex('leads_conversion_probability_index');
            }

            $columns = ['conversion_probability', 'score_breakdown', 'score_factors', 'last_contacted_at', 'last_activity_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('prospects', function (Blueprint $table) {
            if ($this->indexExists('prospects', 'prospects_conversion_probability_index')) {
                $table->dropIndex('prospects_conversion_probability_index');
            }

            $columns = ['conversion_probability', 'score_breakdown', 'score_factors', 'last_contacted_at', 'last_activity_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('prospects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Revert priority enum
        try {
            DB::statement("ALTER TABLE leads MODIFY COLUMN priority ENUM('hot', 'warm', 'cold') DEFAULT 'cold'");
            DB::statement("ALTER TABLE prospects MODIFY COLUMN priority ENUM('hot', 'warm', 'cold') DEFAULT 'cold'");
        } catch (\Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Check if an index exists (database-agnostic)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$table, $indexName]);
            return count($indexes) > 0;
        }

        // MySQL/MariaDB
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
