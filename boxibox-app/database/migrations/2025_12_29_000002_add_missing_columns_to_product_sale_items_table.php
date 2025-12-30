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
        Schema::table('product_sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('product_sale_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('product_sale_items', 'product_sku')) {
                $table->string('product_sku')->nullable()->after('product_name');
            }

            if (!Schema::hasColumn('product_sale_items', 'product_category')) {
                $table->string('product_category')->nullable()->after('product_sku');
            }

            if (!Schema::hasColumn('product_sale_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(20.00)->after('unit_price');
            }

            if (!Schema::hasColumn('product_sale_items', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            }

            if (!Schema::hasColumn('product_sale_items', 'notes')) {
                $table->text('notes')->nullable()->after('total');
            }
        });

        // Populate product snapshots for existing items
        $items = \DB::table('product_sale_items')
            ->whereNull('product_name')
            ->get();

        foreach ($items as $item) {
            $product = \DB::table('products')->find($item->product_id);
            if ($product) {
                \DB::table('product_sale_items')
                    ->where('id', $item->id)
                    ->update([
                        'product_name' => $product->name ?? 'Unknown',
                        'product_sku' => $product->sku ?? null,
                        'product_category' => $product->category ?? null,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sale_items', function (Blueprint $table) {
            $columns = ['product_name', 'product_sku', 'product_category', 'tax_rate', 'tax_amount', 'notes'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('product_sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
