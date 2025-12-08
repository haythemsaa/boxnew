<?php

namespace Database\Seeders;

use App\Models\PlanElement;
use App\Models\Site;
use Illuminate\Database\Seeder;

class BuxidaPlanSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::first();
        if (!$site) {
            $this->command->error('No site found');
            return;
        }

        $this->command->info("Generating Buxida layout for site: {$site->name} (ID: {$site->id})");

        // Delete existing elements
        PlanElement::where('site_id', $site->id)->delete();

        $statusColors = [
            'available' => '#4CAF50',
            'occupied' => '#2196F3',
            'reserved' => '#FF9800',
            'ending' => '#FFEB3B',
            'maintenance' => '#f44336',
            'unavailable' => '#9E9E9E',
        ];

        $elements = [];
        $zIndex = 0;

        // Helper
        $addElement = function($name, $x, $y, $w, $h, $vol, $type = 'box', $status = 'available') use (&$elements, &$zIndex, $statusColors) {
            $elements[] = [
                'name' => $name,
                'x' => $x,
                'y' => $y,
                'w' => $w,
                'h' => $h,
                'vol' => $vol,
                'type' => $type,
                'status' => $status,
                'fill' => $statusColors[$status] ?? $statusColors['available'],
                'z' => $zIndex++,
            ];
        };

        // LEFT SIDE BOXES
        $addElement('V6', 15, 120, 35, 50, 18);
        $addElement('L8', 15, 280, 35, 40, 11);
        $addElement('P3', 15, 520, 30, 25, 1.5);

        // M COLUMN
        $addElement('M14', 55, 95, 40, 35, 18);
        $addElement('M12', 55, 132, 40, 35, 18);
        $addElement('M10', 55, 169, 40, 35, 18);
        $addElement('M8', 55, 206, 40, 35, 18);
        $addElement('M6', 55, 243, 40, 35, 18);
        $addElement('M4', 55, 280, 40, 35, 18);
        $addElement('M2', 55, 317, 35, 30, 18);

        // K COLUMN
        $addElement('K12', 100, 95, 30, 28, 9);
        $addElement('K10', 100, 125, 30, 28, 9);
        $addElement('K8', 100, 155, 30, 28, 9);
        $addElement('K6', 100, 185, 30, 28, 9);
        $addElement('K4', 100, 215, 30, 28, 9);
        $addElement('K2', 100, 245, 30, 28, 9);
        $addElement('K11', 132, 95, 28, 28, 9);
        $addElement('K9', 132, 125, 28, 28, 9);
        $addElement('K7', 132, 155, 28, 28, 9);
        $addElement('K5', 132, 185, 28, 28, 16);
        $addElement('K3', 132, 215, 28, 28, 9);
        $addElement('K1', 132, 245, 28, 28, 18);

        // J COLUMN
        $addElement('J14', 165, 95, 28, 28, 18);
        $addElement('J12', 165, 125, 28, 28, 18);
        $addElement('J13', 195, 95, 45, 35, 30);
        $addElement('I16', 242, 95, 45, 35, 30);
        $addElement('I14', 195, 145, 50, 55, 35);
        $addElement('J11', 195, 202, 25, 25, 9);
        $addElement('J9', 195, 229, 25, 25, 9);
        $addElement('J7', 195, 256, 25, 25, 9);
        $addElement('J5', 195, 283, 25, 25, 9);
        $addElement('J3', 195, 310, 25, 25, 9);
        $addElement('J1', 195, 337, 25, 25, 9);
        $addElement('I12', 222, 202, 25, 25, 9);
        $addElement('I10', 222, 229, 25, 25, 9);
        $addElement('I8', 222, 256, 25, 25, 9);
        $addElement('I6', 165, 155, 28, 28, 16);
        $addElement('J8', 165, 185, 28, 28, 16);
        $addElement('J6', 165, 215, 28, 28, 9);
        $addElement('J4', 165, 283, 28, 25, 9);
        $addElement('J2', 165, 310, 28, 25, 18);

        // I COLUMN
        $addElement('I21', 290, 95, 28, 28, 9);
        $addElement('I19', 290, 125, 28, 28, 9);
        $addElement('I17', 290, 155, 28, 28, 9);
        $addElement('I15', 290, 185, 28, 28, 9);
        $addElement('I13', 290, 215, 28, 28, 9);
        $addElement('I11', 290, 245, 28, 28, 9);
        $addElement('I9', 290, 275, 28, 28, 9);
        $addElement('I7', 290, 305, 28, 28, 9);
        $addElement('I5', 290, 335, 28, 28, 9);
        $addElement('I3', 290, 365, 28, 28, 9);
        $addElement('I1', 290, 395, 28, 28, 9);

        // H COLUMN
        $addElement('H22', 322, 95, 28, 28, 9);
        $addElement('H20', 322, 125, 28, 28, 9);
        $addElement('H18', 322, 155, 28, 28, 9);
        $addElement('H16', 322, 185, 28, 28, 9);
        $addElement('H14', 322, 215, 28, 28, 9);
        $addElement('H12', 322, 245, 28, 28, 9);
        $addElement('H10', 322, 275, 28, 28, 9);
        $addElement('H8', 322, 305, 28, 28, 9);
        $addElement('H6', 322, 335, 28, 28, 9);
        $addElement('H4', 322, 365, 28, 28, 9);
        $addElement('H2', 322, 395, 28, 28, 9);
        $addElement('H19', 355, 95, 40, 45, 16);
        $addElement('H15', 355, 145, 40, 45, 16);
        $addElement('H13', 355, 195, 40, 45, 16);
        $addElement('H9', 355, 270, 40, 40, 16);
        $addElement('H7', 355, 315, 40, 35, 16);
        $addElement('H3', 355, 355, 40, 35, 16);

        // G COLUMN
        $addElement('G21', 400, 95, 35, 32, 18);
        $addElement('G19', 400, 130, 35, 32, 18);
        $addElement('G17', 400, 165, 35, 32, 18);
        $addElement('G16', 400, 200, 35, 32, 18);
        $addElement('G10', 400, 270, 40, 38, 16);
        $addElement('G8', 400, 312, 40, 35, 16);
        $addElement('G9', 442, 270, 35, 32, 18);
        $addElement('G7', 442, 305, 35, 32, 18);
        $addElement('G5', 442, 340, 35, 32, 18);
        $addElement('G3', 442, 375, 35, 32, 18);
        $addElement('G1', 442, 410, 35, 32, 18);
        $addElement('G2', 400, 410, 40, 35, 18);

        // F COLUMN
        $addElement('F14', 485, 95, 42, 35, 18);
        $addElement('F12', 485, 133, 42, 35, 18);
        $addElement('F10', 485, 171, 42, 35, 18);
        $addElement('F8', 485, 209, 42, 35, 18);
        $addElement('F6', 485, 247, 42, 35, 18);
        $addElement('F5', 485, 285, 42, 35, 18);
        $addElement('F3', 485, 323, 42, 35, 18);
        $addElement('F1', 485, 361, 42, 35, 18);
        $addElement('F13', 530, 95, 38, 35, 18);
        $addElement('F11', 530, 133, 38, 35, 18);
        $addElement('F9', 530, 171, 38, 35, 18);
        $addElement('F7', 530, 209, 38, 35, 18);
        $addElement('F2', 485, 399, 42, 35, 18);

        // E COLUMN
        $addElement('E14', 572, 95, 38, 35, 18);
        $addElement('E12', 572, 133, 38, 35, 18);
        $addElement('E10', 572, 171, 38, 35, 18);
        $addElement('E8', 572, 209, 38, 35, 18);
        $addElement('E6', 572, 247, 38, 35, 18);
        $addElement('E4', 572, 323, 38, 35, 18);
        $addElement('E2', 572, 361, 38, 35, 18);
        $addElement('E13', 612, 95, 38, 35, 18);
        $addElement('E11', 612, 133, 38, 35, 18);
        $addElement('E9', 612, 171, 38, 35, 18);
        $addElement('E7', 612, 209, 38, 35, 18);
        $addElement('E5', 612, 247, 38, 35, 18);
        $addElement('E3', 612, 323, 38, 35, 18);
        $addElement('E1', 612, 361, 38, 35, 18);

        // D COLUMN
        $addElement('D14', 655, 95, 38, 35, 18);
        $addElement('D12', 655, 133, 38, 35, 18);
        $addElement('D10', 655, 171, 38, 35, 18);
        $addElement('D8', 655, 209, 38, 35, 18);
        $addElement('D7', 655, 247, 38, 35, 18);
        $addElement('D4', 655, 323, 38, 35, 18);
        $addElement('D2', 655, 361, 38, 35, 18);
        $addElement('D13', 695, 95, 38, 35, 18);
        $addElement('D11', 695, 133, 38, 35, 18);
        $addElement('D9', 695, 171, 38, 35, 18);
        $addElement('C8', 695, 209, 38, 35, 18);
        $addElement('D3', 695, 323, 38, 35, 18);
        $addElement('D1', 695, 361, 38, 35, 18);

        // C COLUMN
        $addElement('C14', 738, 95, 38, 35, 18);
        $addElement('C12', 738, 133, 38, 35, 18);
        $addElement('C10', 738, 171, 38, 35, 18);
        $addElement('C9', 738, 209, 38, 35, 18);
        $addElement('C7', 738, 247, 38, 35, 18);
        $addElement('C5', 738, 285, 38, 35, 18);
        $addElement('C3', 738, 323, 38, 35, 18);
        $addElement('C1', 738, 361, 38, 35, 18);
        $addElement('C13', 778, 95, 38, 35, 18);
        $addElement('C11', 778, 133, 38, 35, 18);
        $addElement('C4', 778, 323, 38, 35, 18);
        $addElement('C2', 778, 361, 38, 35, 18);

        // X COLUMN
        $addElement('X9', 820, 95, 35, 35, 25);
        $addElement('X7', 820, 145, 35, 35, 25);
        $addElement('X11', 858, 95, 40, 40, 25);
        $addElement('X13', 858, 140, 40, 40, 30);
        $addElement('X14', 820, 183, 35, 30, 25);
        $addElement('X12', 820, 215, 35, 30, 25);
        $addElement('X10', 820, 247, 35, 28, 18);
        $addElement('X8', 820, 277, 35, 28, 18);
        $addElement('X6', 820, 307, 35, 28, 18);
        $addElement('X4', 820, 337, 35, 28, 18);
        $addElement('X2', 820, 367, 35, 28, 18);
        $addElement('R1', 858, 185, 22, 22, 11);
        $addElement('R2', 882, 185, 22, 22, 12);
        $addElement('R3', 858, 209, 22, 22, 11);
        $addElement('R4', 882, 209, 22, 22, 12);
        $addElement('R5', 870, 235, 30, 40, 20);
        $addElement('X5', 858, 280, 40, 35, 18);
        $addElement('X3', 858, 320, 55, 50, 63);

        // BOTTOM SECTION
        $addElement('L21', 55, 460, 45, 40, 25);
        $addElement('L6', 165, 460, 42, 40, 18);
        $addElement('L4', 210, 460, 42, 40, 25);
        $addElement('L19', 55, 520, 35, 35, 18);
        $addElement('L17', 92, 520, 35, 35, 18);
        $addElement('L15', 129, 520, 35, 35, 18);
        $addElement('L13', 166, 520, 35, 35, 18);
        $addElement('L11', 203, 520, 35, 35, 18);
        $addElement('L9', 240, 520, 35, 35, 18);
        $addElement('L7', 277, 520, 35, 35, 18);
        $addElement('L5', 314, 520, 35, 35, 18);
        $addElement('L3', 351, 520, 35, 35, 19);

        // LIFTs
        $addElement('LIFT', 105, 460, 55, 40, 0, 'lift', 'unavailable');
        $addElement('LIFT', 775, 448, 55, 40, 0, 'lift', 'unavailable');

        // B Row
        for ($i = 0; $i < 20; $i++) {
            $addElement('B' . (42 - $i * 2), 395 + $i * 22, 448, 20, 18, 3);
        }

        // A Row
        for ($i = 0; $i < 20; $i++) {
            $addElement('A' . (43 - $i), 395 + $i * 22, 520, 20, 18, 3);
        }

        // N Row
        $addElement('N1', 720, 495, 55, 50, 30);
        $addElement('N2', 778, 495, 55, 50, 50);
        $addElement('N3', 836, 495, 55, 50, 50);

        // Q Row
        $addElement('X1', 870, 380, 28, 25, 18);
        $addElement('Q1', 870, 410, 28, 22, 30);
        $addElement('Q3', 870, 434, 28, 22, 30);
        $addElement('Q5', 870, 458, 28, 22, 18);
        $addElement('Q2', 900, 380, 28, 22, 30);
        $addElement('Q4', 900, 404, 28, 22, 30);
        $addElement('Q6', 900, 428, 28, 22, 18);
        $addElement('Q8', 900, 452, 35, 30, 32);

        // O1
        $addElement('O1', 910, 95, 25, 30, 18);

        // Save all elements to database
        foreach ($elements as $el) {
            PlanElement::create([
                'site_id' => $site->id,
                'element_type' => $el['type'],
                'x' => $el['x'],
                'y' => $el['y'],
                'width' => $el['w'],
                'height' => $el['h'],
                'z_index' => $el['z'],
                'fill_color' => $el['fill'],
                'stroke_color' => '#333',
                'stroke_width' => 1,
                'label' => $el['name'],
                'properties' => [
                    'volume' => $el['vol'],
                    'status' => $el['status'],
                ],
                'is_locked' => false,
                'is_visible' => true,
            ]);
        }

        $this->command->info('Created ' . count($elements) . ' plan elements');
    }
}
