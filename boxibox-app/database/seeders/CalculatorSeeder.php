<?php

namespace Database\Seeders;

use App\Models\CalculatorCategory;
use App\Models\CalculatorItem;
use Illuminate\Database\Seeder;

class CalculatorSeeder extends Seeder
{
    public function run(): void
    {
        // Create global categories with items
        $categories = [
            [
                'name' => 'Salon',
                'icon' => 'couch',
                'order' => 1,
                'is_global' => true,
                'items' => [
                    ['name' => 'Canapé 2 places', 'icon' => 'couch', 'volume_m3' => 1.5, 'width_m' => 1.6, 'height_m' => 0.9, 'depth_m' => 0.9, 'is_stackable' => false],
                    ['name' => 'Canapé 3 places', 'icon' => 'couch', 'volume_m3' => 2.0, 'width_m' => 2.2, 'height_m' => 0.9, 'depth_m' => 0.9, 'is_stackable' => false],
                    ['name' => 'Canapé d\'angle', 'icon' => 'couch', 'volume_m3' => 3.5, 'width_m' => 2.8, 'height_m' => 0.9, 'depth_m' => 1.6, 'is_stackable' => false],
                    ['name' => 'Fauteuil', 'icon' => 'chair', 'volume_m3' => 0.8, 'width_m' => 0.8, 'height_m' => 0.9, 'depth_m' => 0.9, 'is_stackable' => true],
                    ['name' => 'Table basse', 'icon' => 'table', 'volume_m3' => 0.4, 'width_m' => 1.2, 'height_m' => 0.45, 'depth_m' => 0.6, 'is_stackable' => true],
                    ['name' => 'Meuble TV', 'icon' => 'tv', 'volume_m3' => 0.6, 'width_m' => 1.5, 'height_m' => 0.5, 'depth_m' => 0.45, 'is_stackable' => false],
                    ['name' => 'Bibliothèque', 'icon' => 'bookshelf', 'volume_m3' => 1.2, 'width_m' => 1.2, 'height_m' => 2.0, 'depth_m' => 0.35, 'is_stackable' => false],
                    ['name' => 'Lampadaire', 'icon' => 'lamp', 'volume_m3' => 0.15, 'width_m' => 0.4, 'height_m' => 1.8, 'depth_m' => 0.4, 'is_stackable' => false],
                    ['name' => 'Tapis (roulé)', 'icon' => 'rug', 'volume_m3' => 0.2, 'width_m' => 0.3, 'height_m' => 2.0, 'depth_m' => 0.3, 'is_stackable' => true],
                ],
            ],
            [
                'name' => 'Chambre',
                'icon' => 'bed',
                'order' => 2,
                'is_global' => true,
                'items' => [
                    ['name' => 'Lit simple (90cm)', 'icon' => 'bed', 'volume_m3' => 1.2, 'width_m' => 1.0, 'height_m' => 0.6, 'depth_m' => 2.0, 'is_stackable' => false],
                    ['name' => 'Lit double (140cm)', 'icon' => 'bed', 'volume_m3' => 1.8, 'width_m' => 1.5, 'height_m' => 0.6, 'depth_m' => 2.0, 'is_stackable' => false],
                    ['name' => 'Lit Queen Size (160cm)', 'icon' => 'bed', 'volume_m3' => 2.0, 'width_m' => 1.7, 'height_m' => 0.6, 'depth_m' => 2.0, 'is_stackable' => false],
                    ['name' => 'Lit King Size (180cm)', 'icon' => 'bed', 'volume_m3' => 2.3, 'width_m' => 1.9, 'height_m' => 0.6, 'depth_m' => 2.0, 'is_stackable' => false],
                    ['name' => 'Matelas (plié)', 'icon' => 'mattress', 'volume_m3' => 0.8, 'width_m' => 1.6, 'height_m' => 0.5, 'depth_m' => 1.0, 'is_stackable' => true],
                    ['name' => 'Armoire 2 portes', 'icon' => 'wardrobe', 'volume_m3' => 1.8, 'width_m' => 1.2, 'height_m' => 2.0, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Armoire 3 portes', 'icon' => 'wardrobe', 'volume_m3' => 2.4, 'width_m' => 1.8, 'height_m' => 2.0, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Commode', 'icon' => 'dresser', 'volume_m3' => 0.7, 'width_m' => 1.0, 'height_m' => 0.9, 'depth_m' => 0.5, 'is_stackable' => false],
                    ['name' => 'Table de chevet', 'icon' => 'nightstand', 'volume_m3' => 0.15, 'width_m' => 0.5, 'height_m' => 0.55, 'depth_m' => 0.4, 'is_stackable' => true],
                    ['name' => 'Coiffeuse', 'icon' => 'vanity', 'volume_m3' => 0.5, 'width_m' => 1.0, 'height_m' => 0.8, 'depth_m' => 0.5, 'is_stackable' => false],
                ],
            ],
            [
                'name' => 'Salle à manger',
                'icon' => 'utensils',
                'order' => 3,
                'is_global' => true,
                'items' => [
                    ['name' => 'Table 4 personnes', 'icon' => 'table', 'volume_m3' => 0.6, 'width_m' => 1.2, 'height_m' => 0.75, 'depth_m' => 0.8, 'is_stackable' => false],
                    ['name' => 'Table 6 personnes', 'icon' => 'table', 'volume_m3' => 0.9, 'width_m' => 1.6, 'height_m' => 0.75, 'depth_m' => 0.9, 'is_stackable' => false],
                    ['name' => 'Table 8 personnes', 'icon' => 'table', 'volume_m3' => 1.2, 'width_m' => 2.0, 'height_m' => 0.75, 'depth_m' => 1.0, 'is_stackable' => false],
                    ['name' => 'Chaise', 'icon' => 'chair', 'volume_m3' => 0.25, 'width_m' => 0.45, 'height_m' => 0.9, 'depth_m' => 0.5, 'is_stackable' => true],
                    ['name' => 'Buffet', 'icon' => 'cabinet', 'volume_m3' => 1.2, 'width_m' => 1.6, 'height_m' => 0.9, 'depth_m' => 0.45, 'is_stackable' => false],
                    ['name' => 'Vaisselier', 'icon' => 'cabinet', 'volume_m3' => 1.8, 'width_m' => 1.2, 'height_m' => 2.0, 'depth_m' => 0.45, 'is_stackable' => false],
                ],
            ],
            [
                'name' => 'Cuisine',
                'icon' => 'kitchen',
                'order' => 4,
                'is_global' => true,
                'items' => [
                    ['name' => 'Réfrigérateur', 'icon' => 'fridge', 'volume_m3' => 1.0, 'width_m' => 0.6, 'height_m' => 1.8, 'depth_m' => 0.65, 'is_stackable' => false],
                    ['name' => 'Réfrigérateur américain', 'icon' => 'fridge', 'volume_m3' => 1.5, 'width_m' => 0.9, 'height_m' => 1.8, 'depth_m' => 0.7, 'is_stackable' => false],
                    ['name' => 'Congélateur', 'icon' => 'freezer', 'volume_m3' => 0.8, 'width_m' => 0.6, 'height_m' => 1.5, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Lave-vaisselle', 'icon' => 'dishwasher', 'volume_m3' => 0.4, 'width_m' => 0.6, 'height_m' => 0.85, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Four', 'icon' => 'oven', 'volume_m3' => 0.3, 'width_m' => 0.6, 'height_m' => 0.6, 'depth_m' => 0.55, 'is_stackable' => false],
                    ['name' => 'Four micro-ondes', 'icon' => 'microwave', 'volume_m3' => 0.08, 'width_m' => 0.5, 'height_m' => 0.3, 'depth_m' => 0.4, 'is_stackable' => true],
                    ['name' => 'Robot de cuisine', 'icon' => 'blender', 'volume_m3' => 0.05, 'width_m' => 0.3, 'height_m' => 0.4, 'depth_m' => 0.25, 'is_stackable' => true],
                ],
            ],
            [
                'name' => 'Bureau',
                'icon' => 'desktop',
                'order' => 5,
                'is_global' => true,
                'items' => [
                    ['name' => 'Bureau', 'icon' => 'desk', 'volume_m3' => 0.7, 'width_m' => 1.4, 'height_m' => 0.75, 'depth_m' => 0.7, 'is_stackable' => false],
                    ['name' => 'Chaise de bureau', 'icon' => 'chair', 'volume_m3' => 0.4, 'width_m' => 0.6, 'height_m' => 1.1, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Caisson de bureau', 'icon' => 'filing', 'volume_m3' => 0.2, 'width_m' => 0.4, 'height_m' => 0.6, 'depth_m' => 0.5, 'is_stackable' => true],
                    ['name' => 'Étagère', 'icon' => 'shelf', 'volume_m3' => 0.6, 'width_m' => 0.8, 'height_m' => 1.8, 'depth_m' => 0.3, 'is_stackable' => false],
                    ['name' => 'Ordinateur (tour + écran)', 'icon' => 'computer', 'volume_m3' => 0.15, 'width_m' => 0.5, 'height_m' => 0.5, 'depth_m' => 0.5, 'is_stackable' => false],
                    ['name' => 'Imprimante', 'icon' => 'printer', 'volume_m3' => 0.08, 'width_m' => 0.45, 'height_m' => 0.25, 'depth_m' => 0.4, 'is_stackable' => true],
                ],
            ],
            [
                'name' => 'Salle de bain',
                'icon' => 'bath',
                'order' => 6,
                'is_global' => true,
                'items' => [
                    ['name' => 'Lave-linge', 'icon' => 'washing', 'volume_m3' => 0.45, 'width_m' => 0.6, 'height_m' => 0.85, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Sèche-linge', 'icon' => 'dryer', 'volume_m3' => 0.45, 'width_m' => 0.6, 'height_m' => 0.85, 'depth_m' => 0.6, 'is_stackable' => false],
                    ['name' => 'Meuble de salle de bain', 'icon' => 'cabinet', 'volume_m3' => 0.35, 'width_m' => 0.8, 'height_m' => 0.85, 'depth_m' => 0.45, 'is_stackable' => false],
                    ['name' => 'Armoire de toilette', 'icon' => 'cabinet', 'volume_m3' => 0.15, 'width_m' => 0.6, 'height_m' => 0.7, 'depth_m' => 0.2, 'is_stackable' => false],
                ],
            ],
            [
                'name' => 'Jardin / Extérieur',
                'icon' => 'tree',
                'order' => 7,
                'is_global' => true,
                'items' => [
                    ['name' => 'Table de jardin', 'icon' => 'table', 'volume_m3' => 0.5, 'width_m' => 1.5, 'height_m' => 0.75, 'depth_m' => 0.9, 'is_stackable' => false],
                    ['name' => 'Chaise de jardin', 'icon' => 'chair', 'volume_m3' => 0.2, 'width_m' => 0.5, 'height_m' => 0.9, 'depth_m' => 0.5, 'is_stackable' => true],
                    ['name' => 'Parasol', 'icon' => 'umbrella', 'volume_m3' => 0.1, 'width_m' => 0.2, 'height_m' => 1.5, 'depth_m' => 0.2, 'is_stackable' => true],
                    ['name' => 'Barbecue', 'icon' => 'grill', 'volume_m3' => 0.4, 'width_m' => 0.6, 'height_m' => 1.0, 'depth_m' => 0.5, 'is_stackable' => false],
                    ['name' => 'Tondeuse', 'icon' => 'mower', 'volume_m3' => 0.3, 'width_m' => 0.5, 'height_m' => 0.5, 'depth_m' => 1.0, 'is_stackable' => false],
                    ['name' => 'Vélo adulte', 'icon' => 'bicycle', 'volume_m3' => 0.5, 'width_m' => 0.5, 'height_m' => 1.0, 'depth_m' => 1.8, 'is_stackable' => false],
                    ['name' => 'Vélo enfant', 'icon' => 'bicycle', 'volume_m3' => 0.25, 'width_m' => 0.35, 'height_m' => 0.7, 'depth_m' => 1.2, 'is_stackable' => false],
                ],
            ],
            [
                'name' => 'Cartons / Divers',
                'icon' => 'box',
                'order' => 8,
                'is_global' => true,
                'items' => [
                    ['name' => 'Carton petit (livres)', 'icon' => 'box', 'volume_m3' => 0.04, 'width_m' => 0.35, 'height_m' => 0.28, 'depth_m' => 0.35, 'is_stackable' => true],
                    ['name' => 'Carton moyen', 'icon' => 'box', 'volume_m3' => 0.07, 'width_m' => 0.45, 'height_m' => 0.35, 'depth_m' => 0.45, 'is_stackable' => true],
                    ['name' => 'Carton grand', 'icon' => 'box', 'volume_m3' => 0.12, 'width_m' => 0.55, 'height_m' => 0.45, 'depth_m' => 0.55, 'is_stackable' => true],
                    ['name' => 'Carton penderie', 'icon' => 'box', 'volume_m3' => 0.4, 'width_m' => 0.5, 'height_m' => 1.2, 'depth_m' => 0.5, 'is_stackable' => false],
                    ['name' => 'Valise', 'icon' => 'suitcase', 'volume_m3' => 0.1, 'width_m' => 0.5, 'height_m' => 0.7, 'depth_m' => 0.3, 'is_stackable' => true],
                    ['name' => 'Malle', 'icon' => 'trunk', 'volume_m3' => 0.2, 'width_m' => 0.7, 'height_m' => 0.5, 'depth_m' => 0.5, 'is_stackable' => true],
                ],
            ],
            [
                'name' => 'Loisirs / Sports',
                'icon' => 'sport',
                'order' => 9,
                'is_global' => true,
                'items' => [
                    ['name' => 'Skis + bâtons', 'icon' => 'ski', 'volume_m3' => 0.08, 'width_m' => 0.2, 'height_m' => 1.8, 'depth_m' => 0.15, 'is_stackable' => true],
                    ['name' => 'Snowboard', 'icon' => 'snowboard', 'volume_m3' => 0.06, 'width_m' => 0.3, 'height_m' => 1.6, 'depth_m' => 0.1, 'is_stackable' => true],
                    ['name' => 'Planche de surf', 'icon' => 'surfboard', 'volume_m3' => 0.15, 'width_m' => 0.5, 'height_m' => 2.0, 'depth_m' => 0.1, 'is_stackable' => true],
                    ['name' => 'Golf (sac + clubs)', 'icon' => 'golf', 'volume_m3' => 0.15, 'width_m' => 0.35, 'height_m' => 1.2, 'depth_m' => 0.35, 'is_stackable' => false],
                    ['name' => 'Table de ping-pong (pliée)', 'icon' => 'pingpong', 'volume_m3' => 0.6, 'width_m' => 1.5, 'height_m' => 1.5, 'depth_m' => 0.3, 'is_stackable' => false],
                    ['name' => 'Tapis de course', 'icon' => 'treadmill', 'volume_m3' => 0.8, 'width_m' => 0.9, 'height_m' => 1.5, 'depth_m' => 0.7, 'is_stackable' => false],
                ],
            ],
            [
                'name' => 'Enfants',
                'icon' => 'child',
                'order' => 10,
                'is_global' => true,
                'items' => [
                    ['name' => 'Lit bébé', 'icon' => 'crib', 'volume_m3' => 0.6, 'width_m' => 0.7, 'height_m' => 1.0, 'depth_m' => 1.2, 'is_stackable' => false],
                    ['name' => 'Lit enfant', 'icon' => 'bed', 'volume_m3' => 0.8, 'width_m' => 0.9, 'height_m' => 0.5, 'depth_m' => 1.8, 'is_stackable' => false],
                    ['name' => 'Poussette', 'icon' => 'stroller', 'volume_m3' => 0.3, 'width_m' => 0.5, 'height_m' => 1.0, 'depth_m' => 0.8, 'is_stackable' => false],
                    ['name' => 'Chaise haute', 'icon' => 'highchair', 'volume_m3' => 0.25, 'width_m' => 0.5, 'height_m' => 1.0, 'depth_m' => 0.5, 'is_stackable' => false],
                    ['name' => 'Parc bébé (plié)', 'icon' => 'playpen', 'volume_m3' => 0.15, 'width_m' => 0.8, 'height_m' => 0.2, 'depth_m' => 0.8, 'is_stackable' => true],
                    ['name' => 'Jouets (carton)', 'icon' => 'toys', 'volume_m3' => 0.07, 'width_m' => 0.45, 'height_m' => 0.35, 'depth_m' => 0.45, 'is_stackable' => true],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $items = $categoryData['items'];
            unset($categoryData['items']);

            $category = CalculatorCategory::create([
                'tenant_id' => null,
                'name' => $categoryData['name'],
                'icon' => $categoryData['icon'],
                'order' => $categoryData['order'],
                'is_global' => true,
                'is_active' => true,
            ]);

            foreach ($items as $order => $itemData) {
                CalculatorItem::create([
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'icon' => $itemData['icon'],
                    'volume_m3' => $itemData['volume_m3'],
                    'width_m' => $itemData['width_m'] ?? null,
                    'height_m' => $itemData['height_m'] ?? null,
                    'depth_m' => $itemData['depth_m'] ?? null,
                    'is_stackable' => $itemData['is_stackable'] ?? true,
                    'order' => $order,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Calculator categories and items seeded successfully!');
    }
}
