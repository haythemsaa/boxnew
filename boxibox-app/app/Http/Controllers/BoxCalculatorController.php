<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Site;
use App\Models\CalculatorWidget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoxCalculatorController extends Controller
{
    /**
     * Common items with their typical volumes in m³
     */
    private array $itemCatalog = [
        // Furniture - Living Room
        'sofa_2seats' => ['name' => 'Canape 2 places', 'volume' => 1.2, 'category' => 'living_room', 'icon' => 'couch'],
        'sofa_3seats' => ['name' => 'Canape 3 places', 'volume' => 1.8, 'category' => 'living_room', 'icon' => 'couch'],
        'armchair' => ['name' => 'Fauteuil', 'volume' => 0.6, 'category' => 'living_room', 'icon' => 'chair'],
        'coffee_table' => ['name' => 'Table basse', 'volume' => 0.3, 'category' => 'living_room', 'icon' => 'table'],
        'tv_stand' => ['name' => 'Meuble TV', 'volume' => 0.5, 'category' => 'living_room', 'icon' => 'tv'],
        'bookshelf' => ['name' => 'Bibliotheque', 'volume' => 0.8, 'category' => 'living_room', 'icon' => 'book'],

        // Furniture - Bedroom
        'single_bed' => ['name' => 'Lit simple (90x190)', 'volume' => 1.2, 'category' => 'bedroom', 'icon' => 'bed'],
        'double_bed' => ['name' => 'Lit double (140x190)', 'volume' => 1.8, 'category' => 'bedroom', 'icon' => 'bed'],
        'king_bed' => ['name' => 'Lit king size (180x200)', 'volume' => 2.5, 'category' => 'bedroom', 'icon' => 'bed'],
        'wardrobe' => ['name' => 'Armoire', 'volume' => 1.5, 'category' => 'bedroom', 'icon' => 'wardrobe'],
        'dresser' => ['name' => 'Commode', 'volume' => 0.6, 'category' => 'bedroom', 'icon' => 'dresser'],
        'nightstand' => ['name' => 'Table de chevet', 'volume' => 0.15, 'category' => 'bedroom', 'icon' => 'nightstand'],
        'mattress' => ['name' => 'Matelas', 'volume' => 0.4, 'category' => 'bedroom', 'icon' => 'bed'],

        // Furniture - Dining
        'dining_table_4' => ['name' => 'Table a manger (4 pers)', 'volume' => 0.8, 'category' => 'dining', 'icon' => 'table'],
        'dining_table_6' => ['name' => 'Table a manger (6 pers)', 'volume' => 1.2, 'category' => 'dining', 'icon' => 'table'],
        'dining_chair' => ['name' => 'Chaise', 'volume' => 0.15, 'category' => 'dining', 'icon' => 'chair'],
        'sideboard' => ['name' => 'Buffet', 'volume' => 1.0, 'category' => 'dining', 'icon' => 'cabinet'],

        // Kitchen
        'refrigerator' => ['name' => 'Refrigerateur', 'volume' => 1.2, 'category' => 'kitchen', 'icon' => 'fridge'],
        'washing_machine' => ['name' => 'Lave-linge', 'volume' => 0.5, 'category' => 'kitchen', 'icon' => 'washer'],
        'dryer' => ['name' => 'Seche-linge', 'volume' => 0.5, 'category' => 'kitchen', 'icon' => 'dryer'],
        'dishwasher' => ['name' => 'Lave-vaisselle', 'volume' => 0.4, 'category' => 'kitchen', 'icon' => 'dishwasher'],
        'microwave' => ['name' => 'Micro-ondes', 'volume' => 0.1, 'category' => 'kitchen', 'icon' => 'microwave'],

        // Office
        'desk' => ['name' => 'Bureau', 'volume' => 0.6, 'category' => 'office', 'icon' => 'desk'],
        'office_chair' => ['name' => 'Chaise de bureau', 'volume' => 0.3, 'category' => 'office', 'icon' => 'chair'],
        'filing_cabinet' => ['name' => 'Classeur', 'volume' => 0.3, 'category' => 'office', 'icon' => 'cabinet'],

        // Boxes & Misc
        'small_box' => ['name' => 'Carton petit (40x30x30)', 'volume' => 0.036, 'category' => 'boxes', 'icon' => 'box'],
        'medium_box' => ['name' => 'Carton moyen (55x35x35)', 'volume' => 0.067, 'category' => 'boxes', 'icon' => 'box'],
        'large_box' => ['name' => 'Carton grand (60x40x40)', 'volume' => 0.096, 'category' => 'boxes', 'icon' => 'box'],
        'suitcase' => ['name' => 'Valise grande', 'volume' => 0.15, 'category' => 'boxes', 'icon' => 'suitcase'],

        // Sports & Outdoor
        'bicycle' => ['name' => 'Velo', 'volume' => 0.5, 'category' => 'sports', 'icon' => 'bicycle'],
        'ski_equipment' => ['name' => 'Equipement ski', 'volume' => 0.2, 'category' => 'sports', 'icon' => 'ski'],
        'golf_clubs' => ['name' => 'Clubs de golf', 'volume' => 0.15, 'category' => 'sports', 'icon' => 'golf'],
        'treadmill' => ['name' => 'Tapis de course', 'volume' => 1.0, 'category' => 'sports', 'icon' => 'treadmill'],
    ];

    /**
     * Box size recommendations
     */
    private array $boxSizes = [
        ['min' => 0, 'max' => 2, 'size' => '2m²', 'description' => 'Ideal pour quelques cartons et petits meubles', 'volume' => 4],
        ['min' => 2, 'max' => 4, 'size' => '4m²', 'description' => 'Studio ou petits demenagements', 'volume' => 8],
        ['min' => 4, 'max' => 6, 'size' => '6m²', 'description' => 'Appartement 1-2 pieces', 'volume' => 14],
        ['min' => 6, 'max' => 10, 'size' => '10m²', 'description' => 'Appartement 2-3 pieces', 'volume' => 25],
        ['min' => 10, 'max' => 15, 'size' => '15m²', 'description' => 'Appartement 3-4 pieces', 'volume' => 40],
        ['min' => 15, 'max' => 20, 'size' => '20m²', 'description' => 'Grande maison', 'volume' => 55],
        ['min' => 20, 'max' => 30, 'size' => '30m²', 'description' => 'Tres grande maison ou commerce', 'volume' => 80],
        ['min' => 30, 'max' => 999, 'size' => '30m²+', 'description' => 'Contactez-nous pour une solution sur mesure', 'volume' => 100],
    ];

    /**
     * Display the calculator page.
     */
    public function index(Request $request)
    {
        // Get available sites for location selection
        $sites = Site::select('id', 'name', 'city', 'address')
            ->where('is_active', true)
            ->get();

        // Group items by category
        $categories = [
            'living_room' => ['name' => 'Salon', 'icon' => 'home'],
            'bedroom' => ['name' => 'Chambre', 'icon' => 'bed'],
            'dining' => ['name' => 'Salle a manger', 'icon' => 'utensils'],
            'kitchen' => ['name' => 'Cuisine', 'icon' => 'kitchen'],
            'office' => ['name' => 'Bureau', 'icon' => 'briefcase'],
            'boxes' => ['name' => 'Cartons', 'icon' => 'box'],
            'sports' => ['name' => 'Sports & Loisirs', 'icon' => 'dumbbell'],
        ];

        $itemsByCategory = [];
        foreach ($categories as $key => $category) {
            $itemsByCategory[$key] = [
                'name' => $category['name'],
                'icon' => $category['icon'],
                'items' => array_filter($this->itemCatalog, fn($item) => $item['category'] === $key),
            ];
        }

        return Inertia::render('Calculator/Index', [
            'sites' => $sites,
            'itemsByCategory' => $itemsByCategory,
            'boxSizes' => $this->boxSizes,
        ]);
    }

    /**
     * Calculate recommended box size based on items.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        // Calculate total volume
        $totalVolume = 0;
        $itemDetails = [];

        foreach ($validated['items'] as $item) {
            if (isset($this->itemCatalog[$item['id']])) {
                $catalogItem = $this->itemCatalog[$item['id']];
                $itemVolume = $catalogItem['volume'] * $item['quantity'];
                $totalVolume += $itemVolume;

                $itemDetails[] = [
                    'name' => $catalogItem['name'],
                    'quantity' => $item['quantity'],
                    'volume' => $itemVolume,
                ];
            }
        }

        // Add 20% buffer for air space and accessibility
        $recommendedVolume = $totalVolume * 1.2;

        // Find recommended box size
        $recommendedSize = null;
        foreach ($this->boxSizes as $size) {
            if ($recommendedVolume >= $size['min'] && $recommendedVolume < $size['max']) {
                $recommendedSize = $size;
                break;
            }
        }

        if (!$recommendedSize) {
            $recommendedSize = end($this->boxSizes);
        }

        // Find available boxes if site is specified
        $availableBoxes = [];
        if (!empty($validated['site_id'])) {
            $availableBoxes = Box::where('site_id', $validated['site_id'])
                ->where('status', 'available')
                ->where('volume', '>=', $totalVolume)
                ->orderBy('volume')
                ->limit(5)
                ->get()
                ->map(fn($box) => [
                    'id' => $box->id,
                    'name' => $box->name,
                    'code' => $box->number,
                    'dimensions' => $box->formatted_dimensions,
                    'volume' => $box->volume,
                    'price' => $box->current_price,
                ]);
        }

        return response()->json([
            'success' => true,
            'calculation' => [
                'total_volume' => round($totalVolume, 2),
                'recommended_volume' => round($recommendedVolume, 2),
                'item_count' => count($itemDetails),
                'items' => $itemDetails,
            ],
            'recommendation' => $recommendedSize,
            'available_boxes' => $availableBoxes,
        ]);
    }

    /**
     * Display embedded widget calculator.
     */
    public function widget(string $embedCode)
    {
        $widget = CalculatorWidget::where('embed_code', $embedCode)
            ->where('is_active', true)
            ->first();

        if (!$widget) {
            abort(404, 'Widget not found');
        }

        // Get sites for this tenant
        $query = Site::where('tenant_id', $widget->tenant_id);

        if ($widget->site_id) {
            $query->where('id', $widget->site_id);
        }

        $sites = $query->get(['id', 'name', 'city', 'address']);

        // Group items by category
        $categories = [
            'living_room' => ['name' => 'Salon', 'icon' => 'home'],
            'bedroom' => ['name' => 'Chambre', 'icon' => 'bed'],
            'dining' => ['name' => 'Salle a manger', 'icon' => 'utensils'],
            'kitchen' => ['name' => 'Cuisine', 'icon' => 'kitchen'],
            'office' => ['name' => 'Bureau', 'icon' => 'briefcase'],
            'boxes' => ['name' => 'Cartons', 'icon' => 'box'],
            'sports' => ['name' => 'Sports & Loisirs', 'icon' => 'dumbbell'],
        ];

        $itemsByCategory = [];
        foreach ($categories as $key => $category) {
            $itemsByCategory[$key] = [
                'name' => $category['name'],
                'icon' => $category['icon'],
                'items' => array_filter($this->itemCatalog, fn($item) => $item['category'] === $key),
            ];
        }

        return Inertia::render('Calculator/Widget', [
            'widget' => $widget,
            'sites' => $sites,
            'itemsByCategory' => $itemsByCategory,
            'boxSizes' => $this->boxSizes,
        ]);
    }
}
