<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Advanced performance optimization indexes and generated columns
 * Based on DBA audit recommendations for high-traffic tables
 */
return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a column exists on a table
     */
    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // 1. INVOICES - Generated column for remaining amount
        // =====================================================
        if (!$this->columnExists('invoices', 'remaining_amount')) {
            try {
                DB::statement("
                    ALTER TABLE invoices
                    ADD COLUMN remaining_amount DECIMAL(10,2)
                    GENERATED ALWAYS AS (total - paid_amount) STORED
                ");
            } catch (\Exception $e) {
                // Column may already exist or database doesn't support generated columns
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            // Index on remaining amount for filtering unpaid invoices
            if ($this->columnExists('invoices', 'remaining_amount') && !$this->indexExists('invoices', 'idx_invoices_remaining')) {
                $table->index('remaining_amount', 'idx_invoices_remaining');
            }

            // Covering index for invoice listing queries
            if (!$this->indexExists('invoices', 'idx_invoices_listing')) {
                $table->index(['tenant_id', 'status', 'due_date', 'total'], 'idx_invoices_listing');
            }
        });

        // =====================================================
        // 2. BOXES - Generated column for area and covering indexes
        // =====================================================
        if (!$this->columnExists('boxes', 'area_m2')) {
            try {
                DB::statement("
                    ALTER TABLE boxes
                    ADD COLUMN area_m2 DECIMAL(8,2)
                    GENERATED ALWAYS AS (length * width) STORED
                ");
            } catch (\Exception $e) {
                // Column may already exist or database doesn't support generated columns
            }
        }

        Schema::table('boxes', function (Blueprint $table) {
            // Index for area-based searches
            if ($this->columnExists('boxes', 'area_m2') && !$this->indexExists('boxes', 'idx_boxes_area')) {
                $table->index(['tenant_id', 'status', 'area_m2'], 'idx_boxes_area');
            }

            // Covering index for box listing with site
            if (!$this->indexExists('boxes', 'idx_boxes_listing')) {
                $table->index(['tenant_id', 'site_id', 'status', 'current_price'], 'idx_boxes_listing');
            }
        });

        // =====================================================
        // 3. CONTRACTS - Covering indexes for dashboard queries
        // =====================================================
        Schema::table('contracts', function (Blueprint $table) {
            // Covering index for active contracts listing
            if (!$this->indexExists('contracts', 'idx_contracts_active_covering')) {
                $table->index(
                    ['tenant_id', 'status', 'customer_id', 'monthly_price', 'start_date'],
                    'idx_contracts_active_covering'
                );
            }

            // Index for expiring contracts queries
            if (!$this->indexExists('contracts', 'idx_contracts_expiring')) {
                $table->index(['tenant_id', 'status', 'end_date'], 'idx_contracts_expiring');
            }
        });

        // =====================================================
        // 4. CUSTOMERS - Fulltext search for fast name lookups
        // =====================================================
        try {
            // Check if fulltext index exists
            $ftIndex = DB::select("SHOW INDEX FROM customers WHERE Index_type = 'FULLTEXT' AND Key_name = 'ft_customer_search'");
            if (empty($ftIndex)) {
                DB::statement("ALTER TABLE customers ADD FULLTEXT INDEX ft_customer_search (first_name, last_name, email, company_name)");
            }
        } catch (\Exception $e) {
            // Fulltext might not be supported or columns don't exist
        }

        // =====================================================
        // 5. ACTIVITY_LOGS - Timeline and subject indexes
        // =====================================================
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                // Subject timeline for entity audit trail
                if ($this->columnExists('activity_logs', 'subject_type') &&
                    $this->columnExists('activity_logs', 'subject_id') &&
                    $this->columnExists('activity_logs', 'created_at') &&
                    !$this->indexExists('activity_logs', 'idx_subject_timeline')) {
                    $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_subject_timeline');
                }

                // User activity timeline
                if ($this->columnExists('activity_logs', 'user_id') &&
                    $this->columnExists('activity_logs', 'created_at') &&
                    !$this->indexExists('activity_logs', 'idx_user_activity')) {
                    $table->index(['user_id', 'created_at'], 'idx_user_activity');
                }

                // Tenant event timeline for filtering
                if ($this->columnExists('activity_logs', 'tenant_id') &&
                    $this->columnExists('activity_logs', 'event') &&
                    $this->columnExists('activity_logs', 'created_at') &&
                    !$this->indexExists('activity_logs', 'idx_tenant_event_timeline')) {
                    $table->index(['tenant_id', 'event', 'created_at'], 'idx_tenant_event_timeline');
                }
            });
        }

        // =====================================================
        // 6. CHAT_MESSAGES - Conversation role filtering
        // =====================================================
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if ($this->columnExists('chat_messages', 'conversation_id') &&
                    $this->columnExists('chat_messages', 'role') &&
                    $this->columnExists('chat_messages', 'created_at') &&
                    !$this->indexExists('chat_messages', 'idx_conv_role_timeline')) {
                    $table->index(['conversation_id', 'role', 'created_at'], 'idx_conv_role_timeline');
                }
            });
        }

        // =====================================================
        // 7. IOT_READINGS - Sensor data with anomaly filtering
        // =====================================================
        if (Schema::hasTable('iot_readings')) {
            Schema::table('iot_readings', function (Blueprint $table) {
                if ($this->columnExists('iot_readings', 'sensor_id') &&
                    $this->columnExists('iot_readings', 'recorded_at') &&
                    $this->columnExists('iot_readings', 'is_anomaly') &&
                    !$this->indexExists('iot_readings', 'idx_readings_anomaly')) {
                    $table->index(['sensor_id', 'recorded_at', 'is_anomaly'], 'idx_readings_anomaly');
                }

                // Covering index for readings with value
                if ($this->columnExists('iot_readings', 'sensor_id') &&
                    $this->columnExists('iot_readings', 'recorded_at') &&
                    $this->columnExists('iot_readings', 'value') &&
                    !$this->indexExists('iot_readings', 'idx_readings_complete')) {
                    $table->index(['sensor_id', 'recorded_at', 'value'], 'idx_readings_complete');
                }
            });
        }

        // =====================================================
        // 8. PAYMENTS - Gateway reference lookups
        // =====================================================
        Schema::table('payments', function (Blueprint $table) {
            // Reference lookup for reconciliation
            if ($this->columnExists('payments', 'reference') && !$this->indexExists('payments', 'idx_payments_reference')) {
                $table->index('reference', 'idx_payments_reference');
            }

            // Customer payment history
            if ($this->columnExists('payments', 'customer_id') &&
                $this->columnExists('payments', 'paid_at') &&
                !$this->indexExists('payments', 'idx_payments_customer_history')) {
                $table->index(['customer_id', 'paid_at'], 'idx_payments_customer_history');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop generated columns
        if ($this->columnExists('invoices', 'remaining_amount')) {
            try {
                DB::statement("ALTER TABLE invoices DROP COLUMN remaining_amount");
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        if ($this->columnExists('boxes', 'area_m2')) {
            try {
                DB::statement("ALTER TABLE boxes DROP COLUMN area_m2");
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        // Drop fulltext index
        try {
            DB::statement("ALTER TABLE customers DROP INDEX ft_customer_search");
        } catch (\Exception $e) {
            // Ignore errors
        }

        // Drop regular indexes
        $indexesToDrop = [
            'invoices' => ['idx_invoices_remaining', 'idx_invoices_listing'],
            'boxes' => ['idx_boxes_area', 'idx_boxes_listing'],
            'contracts' => ['idx_contracts_active_covering', 'idx_contracts_expiring'],
            'activity_logs' => ['idx_subject_timeline', 'idx_user_activity', 'idx_tenant_event_timeline'],
            'chat_messages' => ['idx_conv_role_timeline'],
            'iot_readings' => ['idx_readings_anomaly', 'idx_readings_complete'],
            'payments' => ['idx_payments_reference', 'idx_payments_customer_history'],
        ];

        foreach ($indexesToDrop as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Exception $e) {
                            // Index might not exist
                        }
                    }
                });
            }
        }
    }
};
