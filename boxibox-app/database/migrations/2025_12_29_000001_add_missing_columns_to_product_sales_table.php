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
        Schema::table('product_sales', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('product_sales', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            }

            if (!Schema::hasColumn('product_sales', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('contract_id')->constrained()->onDelete('set null');
            }

            if (!Schema::hasColumn('product_sales', 'sale_number')) {
                $table->string('sale_number')->nullable()->unique()->after('site_id');
            }

            if (!Schema::hasColumn('product_sales', 'status')) {
                $table->string('status')->default('pending')->after('sale_number');
            }

            if (!Schema::hasColumn('product_sales', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('status');
            }

            if (!Schema::hasColumn('product_sales', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            }

            if (!Schema::hasColumn('product_sales', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('total');
            }

            if (!Schema::hasColumn('product_sales', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('product_sales', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }

            if (!Schema::hasColumn('product_sales', 'sold_by')) {
                $table->foreignId('sold_by')->nullable()->after('notes')->constrained('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('product_sales', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('sold_at');
            }

            if (!Schema::hasColumn('product_sales', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refunded_amount');
            }

            if (!Schema::hasColumn('product_sales', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('refunded_at');
            }
        });

        // Generate sale numbers for existing records
        $sales = \DB::table('product_sales')->whereNull('sale_number')->get();
        foreach ($sales as $sale) {
            $date = date('Ymd', strtotime($sale->created_at));
            $saleNumber = 'VNT' . $date . str_pad($sale->id, 4, '0', STR_PAD_LEFT);
            \DB::table('product_sales')->where('id', $sale->id)->update(['sale_number' => $saleNumber]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $columns = [
                'contract_id', 'invoice_id', 'sale_number', 'status',
                'subtotal', 'tax_amount', 'currency', 'payment_reference',
                'paid_at', 'sold_by', 'refunded_amount', 'refunded_at', 'refund_reason'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('product_sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
