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
        Schema::table('products', function (Blueprint $table) {
            // Taux de TVA applicable au produit
            $table->decimal('tax_rate', 5, 2)->default(20.00)->after('price');

            // Quantités min/max par commande
            $table->unsignedInteger('min_quantity')->default(1)->after('stock_quantity');
            $table->unsignedInteger('max_quantity')->nullable()->after('min_quantity');

            // Nécessite un contrat actif pour acheter
            $table->boolean('requires_contract')->default(false)->after('max_quantity');

            // Image du produit
            $table->string('image_path')->nullable()->after('requires_contract');

            // Ordre d'affichage et mise en avant
            $table->unsignedInteger('display_order')->default(0)->after('image_path');
            $table->boolean('is_featured')->default(false)->after('display_order');

            // Coût d'achat pour calcul marge
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');

            // Unité de mesure (pièce, kg, m², etc.)
            $table->string('unit', 20)->default('piece')->after('billing_period');

            // Index pour les recherches
            $table->index(['tenant_id', 'category', 'is_active']);
            $table->index(['tenant_id', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'category', 'is_active']);
            $table->dropIndex(['tenant_id', 'is_featured']);

            $table->dropColumn([
                'tax_rate',
                'min_quantity',
                'max_quantity',
                'requires_contract',
                'image_path',
                'display_order',
                'is_featured',
                'cost_price',
                'unit',
            ]);
        });
    }
};
