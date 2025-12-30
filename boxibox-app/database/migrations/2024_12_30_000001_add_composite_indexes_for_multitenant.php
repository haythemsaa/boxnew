<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for multi-tenant performance optimization
 * These indexes improve query performance when filtering by tenant_id
 */
return new class extends Migration
{
    /**
     * Tables and their composite indexes
     * Format: 'table_name' => [['column1', 'column2', ...], ...]
     */
    protected array $indexes = [
        // Core tables
        'boxes' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'site_id'],
            ['tenant_id', 'site_id', 'status'],
        ],
        'contracts' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'customer_id'],
            ['tenant_id', 'start_date'],
            ['tenant_id', 'status', 'start_date'],
        ],
        'customers' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'email'],
            ['tenant_id', 'created_at'],
        ],
        'invoices' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'customer_id'],
            ['tenant_id', 'due_date'],
            ['tenant_id', 'status', 'due_date'],
        ],
        'payments' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'customer_id'],
            ['tenant_id', 'created_at'],
            ['tenant_id', 'paid_at'],
        ],
        'sites' => [
            ['tenant_id', 'is_active'],
        ],
        // Secondary tables
        'leads' => [
            ['tenant_id', 'status'],
            ['tenant_id', 'created_at'],
        ],
        'access_logs' => [
            ['tenant_id', 'accessed_at'],
            ['tenant_id', 'box_id'],
        ],
        'iot_sensor_readings' => [
            ['sensor_id', 'recorded_at'],
            ['tenant_id', 'recorded_at'],
        ],
        'notifications' => [
            ['tenant_id', 'created_at'],
            ['tenant_id', 'is_read'],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $tableIndexes) {
                foreach ($tableIndexes as $columns) {
                    // Check if all columns exist
                    $allColumnsExist = true;
                    foreach ($columns as $column) {
                        if (!Schema::hasColumn($table, $column)) {
                            $allColumnsExist = false;
                            break;
                        }
                    }

                    if (!$allColumnsExist) {
                        continue;
                    }

                    // Generate index name
                    $indexName = $table . '_' . implode('_', $columns) . '_index';

                    // Truncate index name if too long (MySQL limit is 64 chars)
                    if (strlen($indexName) > 64) {
                        $indexName = substr($indexName, 0, 60) . '_idx';
                    }

                    // Check if index already exists before adding
                    $existingIndexes = collect(\DB::select("SHOW INDEX FROM `{$table}`"))
                        ->pluck('Key_name')
                        ->unique()
                        ->toArray();

                    if (!in_array($indexName, $existingIndexes)) {
                        try {
                            $blueprint->index($columns, $indexName);
                        } catch (\Exception $e) {
                            // Index might already exist, continue
                        }
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $tableIndexes) {
                foreach ($tableIndexes as $columns) {
                    $indexName = $table . '_' . implode('_', $columns) . '_index';

                    if (strlen($indexName) > 64) {
                        $indexName = substr($indexName, 0, 60) . '_idx';
                    }

                    try {
                        $blueprint->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Index might not exist, continue
                    }
                }
            });
        }
    }
};
