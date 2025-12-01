<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\PlanConfiguration;
use App\Models\PlanElement;
use App\Models\Site;
use Illuminate\Database\Seeder;

class PlanDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = Site::all();

        foreach ($sites as $site) {
            $this->command->info("Génération du plan pour: {$site->name}");

            // Clear existing elements
            PlanElement::where('site_id', $site->id)->delete();

            // Create or update configuration
            PlanConfiguration::updateOrCreate(
                ['site_id' => $site->id],
                [
                    'canvas_width' => 1920,
                    'canvas_height' => 1200,
                    'show_grid' => true,
                    'grid_size' => 20,
                    'snap_to_grid' => true,
                    'default_box_available_color' => '#22c55e',
                    'default_box_occupied_color' => '#3b82f6',
                    'default_box_reserved_color' => '#f59e0b',
                    'default_box_maintenance_color' => '#ef4444',
                    'default_wall_color' => '#374151',
                    'default_door_color' => '#78350f',
                    'show_box_labels' => true,
                    'show_box_sizes' => true,
                    'show_legend' => true,
                    'show_statistics' => true,
                ]
            );

            // Get boxes for this site
            $boxes = Box::where('site_id', $site->id)->orderBy('number')->get();

            if ($boxes->isEmpty()) {
                $this->command->warn("Aucun box trouvé pour {$site->name}");
                continue;
            }

            $zIndex = 0;

            // Create warehouse structure elements first
            $this->createWarehouseStructure($site->id, $zIndex);
            $zIndex = 50;

            // Organize boxes in rows by size
            $boxesBySize = $boxes->groupBy(function($box) {
                if ($box->volume <= 3) return 'small';
                if ($box->volume <= 8) return 'medium';
                return 'large';
            });

            $currentY = 120;
            $sectionIndex = 0;

            foreach (['small' => 'Petits boxes', 'medium' => 'Boxes moyens', 'large' => 'Grands boxes'] as $sizeKey => $sectionName) {
                if (!isset($boxesBySize[$sizeKey]) || $boxesBySize[$sizeKey]->isEmpty()) {
                    continue;
                }

                $sectionBoxes = $boxesBySize[$sizeKey];

                // Add section label
                PlanElement::create([
                    'site_id' => $site->id,
                    'element_type' => 'label',
                    'x' => 60,
                    'y' => $currentY - 25,
                    'width' => 200,
                    'height' => 25,
                    'z_index' => $zIndex++,
                    'label' => $sectionName . ' (' . $sectionBoxes->count() . ')',
                    'fill_color' => '#1e3a5f',
                    'text_color' => '#ffffff',
                    'font_size' => 14,
                ]);

                // Place boxes in rows
                $x = 60;
                $maxRowHeight = 0;
                $boxesPerRow = $sizeKey === 'large' ? 6 : ($sizeKey === 'medium' ? 8 : 12);
                $boxCount = 0;

                foreach ($sectionBoxes as $box) {
                    // Calculate box visual dimensions based on actual size
                    $width = $this->calculateBoxWidth($box->volume, $sizeKey);
                    $height = $this->calculateBoxHeight($box->volume, $sizeKey);

                    // Check if we need a new row
                    if ($boxCount > 0 && $boxCount % $boxesPerRow === 0) {
                        $x = 60;
                        $currentY += $maxRowHeight + 15;
                        $maxRowHeight = 0;
                    }

                    // Create box element
                    PlanElement::create([
                        'site_id' => $site->id,
                        'element_type' => 'box',
                        'box_id' => $box->id,
                        'x' => $x,
                        'y' => $currentY,
                        'width' => $width,
                        'height' => $height,
                        'z_index' => $zIndex++,
                        'label' => $box->number,
                        'stroke_color' => '#1e3a5f',
                        'stroke_width' => 2,
                        'opacity' => 1,
                    ]);

                    $x += $width + 10;
                    $maxRowHeight = max($maxRowHeight, $height);
                    $boxCount++;
                }

                $currentY += $maxRowHeight + 60;
                $sectionIndex++;
            }

            // Add some corridors between sections
            $this->addCorridors($site->id, $zIndex);

            $this->command->info("  -> {$boxes->count()} boxes placés sur le plan");
        }

        $this->command->info('Plans de démonstration créés avec succès!');
    }

    /**
     * Create warehouse structure (walls, entrance, etc.)
     */
    private function createWarehouseStructure(int $siteId, int &$zIndex): void
    {
        // Outer walls
        $walls = [
            // Top wall
            ['x' => 40, 'y' => 60, 'width' => 1840, 'height' => 8],
            // Bottom wall
            ['x' => 40, 'y' => 1140, 'width' => 1840, 'height' => 8],
            // Left wall
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 1088],
            // Right wall
            ['x' => 1872, 'y' => 60, 'width' => 8, 'height' => 1088],
        ];

        foreach ($walls as $wall) {
            PlanElement::create([
                'site_id' => $siteId,
                'element_type' => 'wall',
                'x' => $wall['x'],
                'y' => $wall['y'],
                'width' => $wall['width'],
                'height' => $wall['height'],
                'z_index' => $zIndex++,
                'fill_color' => '#374151',
                'stroke_color' => '#1f2937',
                'stroke_width' => 1,
            ]);
        }

        // Main entrance door
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'door',
            'x' => 900,
            'y' => 1140,
            'width' => 120,
            'height' => 12,
            'z_index' => $zIndex++,
            'fill_color' => '#78350f',
            'stroke_color' => '#451a03',
            'stroke_width' => 2,
            'label' => 'Entrée principale',
        ]);

        // Emergency exit
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'door',
            'x' => 40,
            'y' => 500,
            'width' => 12,
            'height' => 80,
            'z_index' => $zIndex++,
            'fill_color' => '#dc2626',
            'stroke_color' => '#991b1b',
            'stroke_width' => 2,
            'label' => 'Sortie secours',
        ]);

        // Office area
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'office',
            'x' => 1700,
            'y' => 80,
            'width' => 160,
            'height' => 120,
            'z_index' => $zIndex++,
            'fill_color' => '#dbeafe',
            'stroke_color' => '#1e40af',
            'stroke_width' => 2,
            'label' => 'Accueil',
        ]);

        // Elevator
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'lift',
            'x' => 1750,
            'y' => 220,
            'width' => 80,
            'height' => 80,
            'z_index' => $zIndex++,
            'fill_color' => '#fef3c7',
            'stroke_color' => '#92400e',
            'stroke_width' => 2,
            'label' => 'Ascenseur',
        ]);

        // Stairs
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'stairs',
            'x' => 1750,
            'y' => 320,
            'width' => 80,
            'height' => 100,
            'z_index' => $zIndex++,
            'fill_color' => '#e5e7eb',
            'stroke_color' => '#4b5563',
            'stroke_width' => 2,
            'label' => 'Escalier',
        ]);

        // Title
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'label',
            'x' => 60,
            'y' => 75,
            'width' => 400,
            'height' => 30,
            'z_index' => $zIndex++,
            'label' => 'Plan du site - Rez-de-chaussée',
            'fill_color' => 'transparent',
            'text_color' => '#1e3a5f',
            'font_size' => 18,
        ]);
    }

    /**
     * Add corridors between sections
     */
    private function addCorridors(int $siteId, int $zIndex): void
    {
        // Main corridor
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'corridor',
            'x' => 900,
            'y' => 1050,
            'width' => 120,
            'height' => 90,
            'z_index' => $zIndex++,
            'fill_color' => '#f3f4f6',
            'stroke_color' => '#9ca3af',
            'stroke_width' => 1,
            'label' => 'Hall',
        ]);

        // Side corridor
        PlanElement::create([
            'site_id' => $siteId,
            'element_type' => 'corridor',
            'x' => 1650,
            'y' => 80,
            'width' => 40,
            'height' => 1060,
            'z_index' => 5, // Behind boxes
            'fill_color' => '#f9fafb',
            'stroke_color' => '#d1d5db',
            'stroke_width' => 1,
        ]);
    }

    /**
     * Calculate box width based on volume
     */
    private function calculateBoxWidth(float $volume, string $sizeCategory): int
    {
        return match($sizeCategory) {
            'small' => rand(60, 80),
            'medium' => rand(90, 120),
            'large' => rand(130, 180),
        };
    }

    /**
     * Calculate box height based on volume
     */
    private function calculateBoxHeight(float $volume, string $sizeCategory): int
    {
        return match($sizeCategory) {
            'small' => rand(50, 65),
            'medium' => rand(70, 90),
            'large' => rand(100, 130),
        };
    }
}
