<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\ContractAddon;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProductsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1; // Default tenant

        // Create demo products
        $products = $this->createProducts($tenantId);

        // Create demo sales
        $this->createSales($tenantId, $products);

        // Create contract addons
        $this->createContractAddons($tenantId, $products);

        $this->command->info('Demo products, sales, and contract addons created successfully!');
    }

    private function createProducts(int $tenantId): array
    {
        $products = [
            // Cadenas et sécurité
            [
                'name' => 'Cadenas Haute Sécurité',
                'sku' => 'LOCK-001',
                'description' => 'Cadenas blindé avec clé unique, résistant aux coupures',
                'category' => 'lock',
                'type' => 'one_time',
                'price' => 24.99,
                'cost_price' => 12.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 50,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 1,
                'is_featured' => true,
            ],
            [
                'name' => 'Cadenas Standard',
                'sku' => 'LOCK-002',
                'description' => 'Cadenas classique avec 2 clés',
                'category' => 'lock',
                'type' => 'one_time',
                'price' => 12.99,
                'cost_price' => 5.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 100,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Cadenas à Code',
                'sku' => 'LOCK-003',
                'description' => 'Cadenas à combinaison 4 chiffres',
                'category' => 'lock',
                'type' => 'one_time',
                'price' => 19.99,
                'cost_price' => 8.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 30,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 3,
            ],

            // Emballage
            [
                'name' => 'Carton Standard (60x40x40)',
                'sku' => 'BOX-001',
                'description' => 'Carton double cannelure, idéal pour le déménagement',
                'category' => 'packaging',
                'type' => 'one_time',
                'price' => 3.50,
                'cost_price' => 1.20,
                'tax_rate' => 20.00,
                'stock_quantity' => 500,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Carton Livre (40x30x30)',
                'sku' => 'BOX-002',
                'description' => 'Carton renforcé pour livres et objets lourds',
                'category' => 'packaging',
                'type' => 'one_time',
                'price' => 2.50,
                'cost_price' => 0.80,
                'tax_rate' => 20.00,
                'stock_quantity' => 300,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 11,
            ],
            [
                'name' => 'Carton Penderie',
                'sku' => 'BOX-003',
                'description' => 'Carton avec barre pour vêtements sur cintres',
                'category' => 'packaging',
                'type' => 'one_time',
                'price' => 12.99,
                'cost_price' => 5.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 50,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 12,
            ],
            [
                'name' => 'Pack Déménagement (20 cartons)',
                'sku' => 'PACK-001',
                'description' => '15 cartons standard + 5 cartons livres + scotch + marqueur',
                'category' => 'packaging',
                'type' => 'one_time',
                'price' => 59.99,
                'cost_price' => 25.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 25,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 13,
                'is_featured' => true,
            ],
            [
                'name' => 'Papier Bulle (rouleau 50m)',
                'sku' => 'WRAP-001',
                'description' => 'Rouleau de papier bulle 50m x 1m',
                'category' => 'packaging',
                'type' => 'one_time',
                'price' => 24.99,
                'cost_price' => 10.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 40,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 14,
            ],
            [
                'name' => 'Scotch Emballage (lot de 3)',
                'sku' => 'TAPE-001',
                'description' => '3 rouleaux de scotch marron 50m',
                'category' => 'supplies',
                'type' => 'one_time',
                'price' => 8.99,
                'cost_price' => 3.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 100,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 15,
            ],

            // Fournitures
            [
                'name' => 'Housse Matelas 2 places',
                'sku' => 'COVER-001',
                'description' => 'Housse plastique pour matelas 140x190',
                'category' => 'supplies',
                'type' => 'one_time',
                'price' => 9.99,
                'cost_price' => 3.50,
                'tax_rate' => 20.00,
                'stock_quantity' => 30,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 20,
            ],
            [
                'name' => 'Housse Canapé 3 places',
                'sku' => 'COVER-002',
                'description' => 'Housse plastique pour canapé',
                'category' => 'supplies',
                'type' => 'one_time',
                'price' => 14.99,
                'cost_price' => 5.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 20,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 21,
            ],
            [
                'name' => 'Couverture de Protection',
                'sku' => 'BLANKET-001',
                'description' => 'Couverture matelassée pour meubles',
                'category' => 'supplies',
                'type' => 'one_time',
                'price' => 19.99,
                'cost_price' => 8.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 25,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 22,
            ],
            [
                'name' => 'Sangles d\'Arrimage (lot de 4)',
                'sku' => 'STRAP-001',
                'description' => '4 sangles à cliquet 5m',
                'category' => 'supplies',
                'type' => 'one_time',
                'price' => 29.99,
                'cost_price' => 12.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 15,
                'track_inventory' => true,
                'is_active' => true,
                'display_order' => 23,
            ],

            // Services récurrents
            [
                'name' => 'Accès Électricité Box',
                'sku' => 'ELEC-001',
                'description' => 'Prise électrique dans le box (2 prises)',
                'category' => 'electricity',
                'type' => 'recurring',
                'price' => 29.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 30,
                'requires_contract' => true,
            ],
            [
                'name' => 'Accès WiFi Premium',
                'sku' => 'WIFI-001',
                'description' => 'Accès WiFi haut débit dans le box',
                'category' => 'wifi',
                'type' => 'recurring',
                'price' => 19.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 31,
                'requires_contract' => true,
            ],
            [
                'name' => 'Assurance Complémentaire',
                'sku' => 'INS-001',
                'description' => 'Assurance tous risques jusqu\'à 10 000€',
                'category' => 'insurance',
                'type' => 'recurring',
                'price' => 14.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 32,
                'requires_contract' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Assurance Premium',
                'sku' => 'INS-002',
                'description' => 'Assurance tous risques jusqu\'à 30 000€',
                'category' => 'insurance',
                'type' => 'recurring',
                'price' => 34.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 33,
                'requires_contract' => true,
            ],
            [
                'name' => 'Nettoyage Mensuel',
                'sku' => 'CLEAN-001',
                'description' => 'Service de nettoyage mensuel du box',
                'category' => 'cleaning',
                'type' => 'recurring',
                'price' => 24.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 34,
                'requires_contract' => true,
            ],
            [
                'name' => 'Vidéosurveillance Dédiée',
                'sku' => 'SEC-001',
                'description' => 'Caméra dédiée avec accès à distance 24/7',
                'category' => 'security',
                'type' => 'recurring',
                'price' => 39.99,
                'billing_period' => 'monthly',
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 35,
                'requires_contract' => true,
            ],

            // Services ponctuels
            [
                'name' => 'Aide au Déchargement (1h)',
                'sku' => 'HELP-001',
                'description' => '1 heure d\'aide pour décharger votre véhicule',
                'category' => 'moving',
                'type' => 'one_time',
                'price' => 35.00,
                'cost_price' => 15.00,
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 40,
            ],
            [
                'name' => 'Nettoyage Fin de Location',
                'sku' => 'CLEAN-002',
                'description' => 'Nettoyage complet du box en fin de contrat',
                'category' => 'cleaning',
                'type' => 'one_time',
                'price' => 49.99,
                'cost_price' => 20.00,
                'tax_rate' => 20.00,
                'is_active' => true,
                'display_order' => 41,
            ],
            [
                'name' => 'Diable de Transport',
                'sku' => 'TOOL-001',
                'description' => 'Location diable pour la journée',
                'category' => 'other',
                'type' => 'one_time',
                'price' => 15.00,
                'cost_price' => 2.00,
                'tax_rate' => 20.00,
                'stock_quantity' => 5,
                'track_inventory' => false,
                'is_active' => true,
                'display_order' => 42,
            ],
        ];

        $createdProducts = [];
        foreach ($products as $productData) {
            $sku = $productData['sku'];
            $productData['tenant_id'] = $tenantId;
            $createdProducts[] = Product::updateOrCreate(
                ['sku' => $sku],
                $productData
            );
        }

        $this->command->info('Created ' . count($createdProducts) . ' products');

        return $createdProducts;
    }

    private function createSales(int $tenantId, array $products): void
    {
        $customers = Customer::where('tenant_id', $tenantId)->take(10)->get();
        $user = User::where('tenant_id', $tenantId)->first();

        if ($customers->isEmpty() || !$user) {
            $this->command->warn('No customers or users found, skipping sales creation');
            return;
        }

        $paymentMethods = ['cash', 'card', 'bank_transfer'];
        $oneTimeProducts = collect($products)->filter(fn($p) => $p->type === 'one_time')->values();

        // Create 30 demo sales over the last 60 days
        for ($i = 0; $i < 30; $i++) {
            $customer = $customers->random();
            $soldAt = Carbon::now()->subDays(rand(0, 60))->setTime(rand(9, 18), rand(0, 59));

            // Random 1-5 products per sale
            $saleProducts = $oneTimeProducts->random(rand(1, min(5, $oneTimeProducts->count())));

            $subtotal = 0;
            $items = [];

            foreach ($saleProducts as $product) {
                $quantity = rand(1, 3);
                $unitPrice = $product->price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $taxAmount = round($subtotal * 0.20, 2);
            $total = $subtotal + $taxAmount;

            $sale = ProductSale::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'contract_id' => $customer->contracts()->first()?->id,
                'sale_number' => 'VNT-' . $soldAt->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => rand(1, 10) > 1 ? 'completed' : 'cancelled',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_status' => rand(1, 10) > 1 ? 'paid' : 'pending',
                'notes' => rand(1, 5) === 1 ? 'Client fidèle - remise appliquée' : null,
                'sold_by' => $user->id,
                'sold_at' => $soldAt,
            ]);

            foreach ($items as $item) {
                ProductSaleItem::create([
                    'product_sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => 0,
                    'total' => $item['total'],
                ]);
            }
        }

        $this->command->info('Created 30 demo sales');
    }

    private function createContractAddons(int $tenantId, array $products): void
    {
        $contracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->take(10)
            ->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('No active contracts found, skipping addons creation');
            return;
        }

        $recurringProducts = collect($products)->filter(fn($p) => $p->type === 'recurring')->values();

        if ($recurringProducts->isEmpty()) {
            $this->command->warn('No recurring products found');
            return;
        }

        $addonsCreated = 0;

        foreach ($contracts as $contract) {
            // 50% chance to have addons
            if (rand(1, 2) === 1) {
                continue;
            }

            // Add 1-3 random addons
            $selectedAddons = $recurringProducts->random(rand(1, min(3, $recurringProducts->count())));

            foreach ($selectedAddons as $product) {
                // Check if addon already exists
                $exists = ContractAddon::where('contract_id', $contract->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $startDate = $contract->start_date ?? Carbon::now()->subMonths(rand(1, 6));
                $status = rand(1, 10) > 2 ? 'active' : 'paused';

                ContractAddon::create([
                    'contract_id' => $contract->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                    'tax_rate' => $product->tax_rate ?? 20,
                    'billing_period' => $product->billing_period ?? 'monthly',
                    'start_date' => $startDate,
                    'end_date' => null,
                    'status' => $status,
                    'next_billing_date' => Carbon::now()->startOfMonth()->addMonth(),
                    'notes' => null,
                ]);

                $addonsCreated++;
            }
        }

        $this->command->info("Created {$addonsCreated} contract addons");
    }
}
