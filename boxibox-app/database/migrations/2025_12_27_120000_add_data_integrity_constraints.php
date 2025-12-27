<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data integrity constraints migration
 * Adds CHECK constraints to ensure data consistency across the application
 */
return new class extends Migration
{
    /**
     * Check if a constraint exists
     */
    private function constraintExists(string $table, string $constraintName): bool
    {
        try {
            $result = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?
            ", [$table, $constraintName]);
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // INVOICES - Ensure paid amount is valid
        // =====================================================
        if (!$this->constraintExists('invoices', 'chk_invoice_paid_amount')) {
            try {
                DB::statement("
                    ALTER TABLE invoices
                    ADD CONSTRAINT chk_invoice_paid_amount
                    CHECK (paid_amount >= 0 AND paid_amount <= total)
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // =====================================================
        // CONTRACTS - Ensure dates are coherent
        // =====================================================
        if (!$this->constraintExists('contracts', 'chk_contract_dates')) {
            try {
                DB::statement("
                    ALTER TABLE contracts
                    ADD CONSTRAINT chk_contract_dates
                    CHECK (
                        start_date IS NOT NULL AND
                        (end_date IS NULL OR end_date >= start_date) AND
                        (actual_end_date IS NULL OR actual_end_date >= start_date)
                    )
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // Ensure discount percentage is capped at 30%
        if (!$this->constraintExists('contracts', 'chk_contract_discount')) {
            try {
                DB::statement("
                    ALTER TABLE contracts
                    ADD CONSTRAINT chk_contract_discount
                    CHECK (discount_percentage IS NULL OR (discount_percentage >= 0 AND discount_percentage <= 30))
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // =====================================================
        // BOXES - Ensure prices are non-negative
        // =====================================================
        if (!$this->constraintExists('boxes', 'chk_box_prices')) {
            try {
                DB::statement("
                    ALTER TABLE boxes
                    ADD CONSTRAINT chk_box_prices
                    CHECK (
                        base_price >= 0 AND
                        (current_price IS NULL OR current_price >= 0)
                    )
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // Ensure dimensions are positive
        if (!$this->constraintExists('boxes', 'chk_box_dimensions')) {
            try {
                DB::statement("
                    ALTER TABLE boxes
                    ADD CONSTRAINT chk_box_dimensions
                    CHECK (
                        length > 0 AND
                        width > 0 AND
                        height > 0
                    )
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // =====================================================
        // PAYMENTS - Ensure amount is positive
        // =====================================================
        if (!$this->constraintExists('payments', 'chk_payment_amount')) {
            try {
                DB::statement("
                    ALTER TABLE payments
                    ADD CONSTRAINT chk_payment_amount
                    CHECK (amount > 0)
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // =====================================================
        // CUSTOMERS - Ensure type-specific data
        // =====================================================
        if (!$this->constraintExists('customers', 'chk_customer_type_data')) {
            try {
                DB::statement("
                    ALTER TABLE customers
                    ADD CONSTRAINT chk_customer_type_data
                    CHECK (
                        (type = 'company' AND company_name IS NOT NULL AND company_name != '') OR
                        (type = 'individual' AND first_name IS NOT NULL AND last_name IS NOT NULL)
                    )
                ");
            } catch (\Exception $e) {
                // Constraint might already exist or not supported
            }
        }

        // =====================================================
        // LOYALTY_POINTS - Ensure points are non-negative
        // =====================================================
        if (Schema::hasTable('loyalty_points')) {
            if (!$this->constraintExists('loyalty_points', 'chk_loyalty_points_balance')) {
                try {
                    DB::statement("
                        ALTER TABLE loyalty_points
                        ADD CONSTRAINT chk_loyalty_points_balance
                        CHECK (current_balance >= 0 AND total_points_earned >= 0)
                    ");
                } catch (\Exception $e) {
                    // Constraint might already exist or not supported
                }
            }
        }

        // =====================================================
        // SITES - Ensure occupation rate is valid percentage
        // =====================================================
        if (Schema::hasColumn('sites', 'occupation_rate')) {
            if (!$this->constraintExists('sites', 'chk_site_occupation_rate')) {
                try {
                    DB::statement("
                        ALTER TABLE sites
                        ADD CONSTRAINT chk_site_occupation_rate
                        CHECK (occupation_rate IS NULL OR (occupation_rate >= 0 AND occupation_rate <= 100))
                    ");
                } catch (\Exception $e) {
                    // Constraint might already exist or not supported
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $constraints = [
            'invoices' => ['chk_invoice_paid_amount'],
            'contracts' => ['chk_contract_dates', 'chk_contract_discount'],
            'boxes' => ['chk_box_prices', 'chk_box_dimensions'],
            'payments' => ['chk_payment_amount'],
            'customers' => ['chk_customer_type_data'],
            'loyalty_points' => ['chk_loyalty_points_balance'],
            'sites' => ['chk_site_occupation_rate'],
        ];

        foreach ($constraints as $table => $tableConstraints) {
            if (Schema::hasTable($table)) {
                foreach ($tableConstraints as $constraint) {
                    try {
                        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
                    } catch (\Exception $e) {
                        // Constraint might not exist
                    }
                }
            }
        }
    }
};
