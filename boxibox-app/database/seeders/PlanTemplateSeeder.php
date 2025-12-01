<?php

namespace Database\Seeders;

use App\Models\PlanTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PlanTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first tenant for demo templates
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->warn('Aucun tenant trouvé. Créez d\'abord un tenant.');
            return;
        }

        // Standard warehouse template
        PlanTemplate::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Entrepôt standard',
            ],
            [
                'description' => 'Mise en page classique d\'entrepôt avec petits, moyens et grands boxes',
                'category' => 'standard',
                'width' => 1920,
                'height' => 1080,
                'is_public' => true,
                'template_data' => $this->getStandardWarehouseTemplate(),
            ]
        );

        // Premium high-density template
        PlanTemplate::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Entrepôt haute densité',
            ],
            [
                'description' => 'Optimisé pour maximiser l\'utilisation de l\'espace',
                'category' => 'premium',
                'width' => 2400,
                'height' => 1600,
                'is_public' => true,
                'template_data' => $this->getHighDensityTemplate(),
            ]
        );

        // Multi-floor warehouse template
        PlanTemplate::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Entrepôt multi-étages',
            ],
            [
                'description' => 'Structure pour entrepôt sur plusieurs niveaux',
                'category' => 'premium',
                'width' => 1920,
                'height' => 2400,
                'is_public' => true,
                'template_data' => $this->getMultiFloorTemplate(),
            ]
        );

        // Compact unit storage template
        PlanTemplate::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Stockage de petites unités',
            ],
            [
                'description' => 'Optimisé pour les unités de stockage de petite et moyenne taille',
                'category' => 'standard',
                'width' => 1600,
                'height' => 1200,
                'is_public' => true,
                'template_data' => $this->getCompactUnitTemplate(),
            ]
        );

        // Climate-controlled warehouse template
        PlanTemplate::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Entrepôt climatisé',
            ],
            [
                'description' => 'Avec zones climatisées pour stockage sensible à la température',
                'category' => 'premium',
                'width' => 2000,
                'height' => 1400,
                'is_public' => true,
                'template_data' => $this->getClimateControlledTemplate(),
            ]
        );

        $this->command->info('Plan templates créés avec succès!');
    }

    /**
     * Standard warehouse template data
     */
    private function getStandardWarehouseTemplate(): array
    {
        return [
            // Outer walls
            ['x' => 40, 'y' => 60, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 40, 'y' => 1000, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 948, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 1872, 'y' => 60, 'width' => 8, 'height' => 948, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            // Main entrance
            ['x' => 900, 'y' => 1000, 'width' => 120, 'height' => 12, 'element_type' => 'door', 'fill_color' => '#78350f', 'stroke_color' => '#451a03', 'label' => 'Entrée'],
            // Office
            ['x' => 1700, 'y' => 80, 'width' => 160, 'height' => 120, 'element_type' => 'office', 'fill_color' => '#dbeafe', 'stroke_color' => '#1e40af', 'label' => 'Accueil'],
            // Elevator
            ['x' => 1750, 'y' => 220, 'width' => 80, 'height' => 80, 'element_type' => 'lift', 'fill_color' => '#fef3c7', 'stroke_color' => '#92400e', 'label' => 'Ascenseur'],
        ];
    }

    /**
     * High-density warehouse template
     */
    private function getHighDensityTemplate(): array
    {
        return [
            // Additional walls for section dividers
            ['x' => 40, 'y' => 60, 'width' => 2320, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 40, 'y' => 1560, 'width' => 2320, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 1508, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            ['x' => 2352, 'y' => 60, 'width' => 8, 'height' => 1508, 'element_type' => 'wall', 'fill_color' => '#374151', 'stroke_color' => '#1f2937'],
            // Internal dividers
            ['x' => 1200, 'y' => 60, 'width' => 4, 'height' => 1508, 'element_type' => 'wall', 'fill_color' => '#6b7280', 'stroke_color' => '#4b5563'],
            // Main entrance
            ['x' => 1150, 'y' => 1560, 'width' => 150, 'height' => 12, 'element_type' => 'door', 'fill_color' => '#78350f', 'stroke_color' => '#451a03', 'label' => 'Entrée'],
            // Office area
            ['x' => 2150, 'y' => 80, 'width' => 180, 'height' => 150, 'element_type' => 'office', 'fill_color' => '#dbeafe', 'stroke_color' => '#1e40af', 'label' => 'Bureau'],
            // Elevator
            ['x' => 2200, 'y' => 250, 'width' => 100, 'height' => 100, 'element_type' => 'lift', 'fill_color' => '#fef3c7', 'stroke_color' => '#92400e', 'label' => 'Ascenseur'],
        ];
    }

    /**
     * Multi-floor warehouse template
     */
    private function getMultiFloorTemplate(): array
    {
        return [
            // Ground floor walls
            ['x' => 40, 'y' => 60, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 580, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 528, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 1872, 'y' => 60, 'width' => 8, 'height' => 528, 'element_type' => 'wall', 'fill_color' => '#374151'],
            // Floor 1 walls
            ['x' => 40, 'y' => 650, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 1170, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 650, 'width' => 8, 'height' => 520, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 1872, 'y' => 650, 'width' => 8, 'height' => 520, 'element_type' => 'wall', 'fill_color' => '#374151'],
            // Floor 2 walls
            ['x' => 40, 'y' => 1240, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 1760, 'width' => 1840, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 1240, 'width' => 8, 'height' => 520, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 1872, 'y' => 1240, 'width' => 8, 'height' => 520, 'element_type' => 'wall', 'fill_color' => '#374151'],
            // Stairs between floors
            ['x' => 1750, 'y' => 600, 'width' => 80, 'height' => 100, 'element_type' => 'stairs', 'fill_color' => '#e5e7eb', 'label' => 'Escalier 1'],
            ['x' => 1750, 'y' => 1190, 'width' => 80, 'height' => 100, 'element_type' => 'stairs', 'fill_color' => '#e5e7eb', 'label' => 'Escalier 2'],
            // Labels
            ['x' => 60, 'y' => 40, 'width' => 400, 'height' => 30, 'element_type' => 'label', 'label' => 'Rez-de-chaussée', 'font_size' => 16],
            ['x' => 60, 'y' => 620, 'width' => 400, 'height' => 30, 'element_type' => 'label', 'label' => 'Étage 1', 'font_size' => 16],
            ['x' => 60, 'y' => 1210, 'width' => 400, 'height' => 30, 'element_type' => 'label', 'label' => 'Étage 2', 'font_size' => 16],
        ];
    }

    /**
     * Compact unit storage template
     */
    private function getCompactUnitTemplate(): array
    {
        return [
            // Walls
            ['x' => 40, 'y' => 60, 'width' => 1520, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 1160, 'width' => 1520, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 1108, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 1552, 'y' => 60, 'width' => 8, 'height' => 1108, 'element_type' => 'wall', 'fill_color' => '#374151'],
            // Entrance
            ['x' => 750, 'y' => 1160, 'width' => 100, 'height' => 12, 'element_type' => 'door', 'fill_color' => '#78350f'],
            // Interior divider for units
            ['x' => 800, 'y' => 60, 'width' => 4, 'height' => 1108, 'element_type' => 'wall', 'fill_color' => '#6b7280'],
            // Office corner
            ['x' => 1450, 'y' => 80, 'width' => 120, 'height' => 100, 'element_type' => 'office', 'fill_color' => '#dbeafe'],
            // Elevator
            ['x' => 1480, 'y' => 200, 'width' => 60, 'height' => 60, 'element_type' => 'lift', 'fill_color' => '#fef3c7'],
        ];
    }

    /**
     * Climate-controlled warehouse template
     */
    private function getClimateControlledTemplate(): array
    {
        return [
            // Outer walls
            ['x' => 40, 'y' => 60, 'width' => 1920, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 1360, 'width' => 1920, 'height' => 8, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 40, 'y' => 60, 'width' => 8, 'height' => 1308, 'element_type' => 'wall', 'fill_color' => '#374151'],
            ['x' => 1952, 'y' => 60, 'width' => 8, 'height' => 1308, 'element_type' => 'wall', 'fill_color' => '#374151'],
            // Climate control zones
            ['x' => 60, 'y' => 80, 'width' => 900, 'height' => 600, 'element_type' => 'zone', 'fill_color' => '#cffafe', 'stroke_color' => '#06b6d4', 'label' => 'Zone climatisée (+15°C)'],
            ['x' => 980, 'y' => 80, 'width' => 900, 'height' => 600, 'element_type' => 'zone', 'fill_color' => '#fce7f3', 'stroke_color' => '#ec4899', 'label' => 'Zone standard'],
            // Regular storage zone
            ['x' => 60, 'y' => 700, 'width' => 1820, 'height' => 600, 'element_type' => 'zone', 'fill_color' => '#f3f4f6', 'stroke_color' => '#6b7280', 'label' => 'Zone standard'],
            // Main entrance
            ['x' => 950, 'y' => 1360, 'width' => 100, 'height' => 12, 'element_type' => 'door', 'fill_color' => '#78350f'],
            // Office
            ['x' => 1850, 'y' => 80, 'width' => 120, 'height' => 100, 'element_type' => 'office', 'fill_color' => '#dbeafe'],
        ];
    }
}
