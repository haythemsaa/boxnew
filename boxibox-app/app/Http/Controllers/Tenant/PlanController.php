<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Floor;
use App\Models\PlanConfiguration;
use App\Models\PlanElement;
use App\Models\PlanTemplate;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Display the interactive plan view
     */
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $siteId = $request->get('site_id');

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'code')
            ->get();

        $currentSite = $siteId
            ? Site::where('id', $siteId)->where('tenant_id', $tenant->id)->first()
            : $sites->first();

        if (!$currentSite) {
            return Inertia::render('Tenant/Plan/Index', [
                'sites' => $sites,
                'currentSite' => null,
                'elements' => [],
                'configuration' => null,
                'statistics' => null,
                'floors' => [],
            ]);
        }

        $floors = Floor::where('site_id', $currentSite->id)->get();

        $elements = PlanElement::where('site_id', $currentSite->id)
            ->with(['box.contracts' => function ($query) {
                $query->where('status', 'active')->with('customer');
            }])
            ->orderBy('z_index')
            ->get()
            ->map(function ($element) {
                return [
                    'id' => $element->id,
                    'element_type' => $element->element_type,
                    'box_id' => $element->box_id,
                    'x' => (float) $element->x,
                    'y' => (float) $element->y,
                    'width' => (float) $element->width,
                    'height' => (float) $element->height,
                    'rotation' => (float) $element->rotation,
                    'z_index' => $element->z_index,
                    'fill_color' => $element->fill_color,
                    'stroke_color' => $element->stroke_color,
                    'stroke_width' => $element->stroke_width,
                    'opacity' => (float) $element->opacity,
                    'label' => $element->label,
                    'description' => $element->description,
                    'properties' => $element->properties,
                    'is_locked' => $element->is_locked,
                    'is_visible' => $element->is_visible,
                    'status_color' => $element->status_color,
                    'box_info' => $element->box_info,
                ];
            });

        $configuration = PlanConfiguration::firstOrCreate(
            ['site_id' => $currentSite->id],
            [
                'canvas_width' => 1920,
                'canvas_height' => 1080,
            ]
        );

        // Calculate statistics
        $totalBoxes = Box::where('site_id', $currentSite->id)->count();
        $occupiedBoxes = Box::where('site_id', $currentSite->id)
            ->whereHas('contracts', function ($query) {
                $query->where('status', 'active');
            })
            ->count();
        $availableBoxes = $totalBoxes - $occupiedBoxes;

        $statistics = [
            'total' => $totalBoxes,
            'occupied' => $occupiedBoxes,
            'available' => $availableBoxes,
            'occupancy_rate' => $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 1) : 0,
        ];

        // Get box types for legend
        $boxTypes = Box::where('site_id', $currentSite->id)
            ->select('volume')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM contracts WHERE contracts.box_id = boxes.id AND contracts.status = "active") THEN 1 ELSE 0 END) as occupied')
            ->groupBy('volume')
            ->orderBy('volume')
            ->get()
            ->map(function ($type) {
                return [
                    'size' => $type->volume . 'm³',
                    'total' => $type->count,
                    'occupied' => $type->occupied,
                    'available' => $type->count - $type->occupied,
                ];
            });

        return Inertia::render('Tenant/Plan/Index', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'elements' => $elements,
            'configuration' => $configuration,
            'statistics' => $statistics,
            'boxTypes' => $boxTypes,
            'floors' => $floors,
        ]);
    }

    /**
     * Display the new interactive SVG plan view (Buxida style)
     */
    public function interactive(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $siteId = $request->get('site_id');

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'code')
            ->get();

        $currentSite = $siteId
            ? Site::where('id', $siteId)->where('tenant_id', $tenant->id)->first()
            : $sites->first();

        if (!$currentSite) {
            return Inertia::render('Tenant/Plan/Interactive', [
                'sites' => $sites,
                'currentSite' => null,
                'boxes' => [],
                'elements' => [],
                'configuration' => [
                    'canvas_width' => 1200,
                    'canvas_height' => 800,
                    'show_grid' => true,
                ],
                'statistics' => null,
            ]);
        }

        // Get plan elements from the editor (same format as editor() method)
        $elements = PlanElement::where('site_id', $currentSite->id)
            ->with(['box.contracts' => function ($query) {
                $query->where('status', 'active')->with('customer');
            }])
            ->orderBy('z_index')
            ->get()
            ->map(function ($el) {
                // Get box status from contract
                $status = 'available';
                if ($el->box) {
                    $activeContract = $el->box->contracts->first();
                    if ($activeContract) {
                        $status = 'occupied';
                        if ($activeContract->end_date && $activeContract->end_date->diffInDays(now()) <= 30) {
                            $status = 'ending';
                        }
                    } elseif ($el->box->status === 'reserved') {
                        $status = 'reserved';
                    } elseif ($el->box->status === 'maintenance') {
                        $status = 'maintenance';
                    }
                }

                return [
                    'id' => $el->id,
                    'type' => $el->element_type,
                    'x' => (float) $el->x,
                    'y' => (float) $el->y,
                    'w' => (float) $el->width,
                    'h' => (float) $el->height,
                    'fill' => $el->fill_color ?? '#4CAF50',
                    'z' => $el->z_index ?? 100,
                    'name' => $el->label ?? '',
                    'vol' => $el->properties['volume'] ?? ($el->box?->volume ?? 0),
                    'status' => $status,
                    'locked' => $el->is_locked ?? false,
                    'visible' => $el->is_visible ?? true,
                    'boxId' => $el->box_id,
                    'box' => $el->box ? [
                        'id' => $el->box->id,
                        'number' => $el->box->number,
                        'volume' => $el->box->volume,
                        'current_price' => $el->box->current_price ?? $el->box->base_price,
                    ] : null,
                    'contract' => $el->box && $el->box->contracts->first() ? [
                        'id' => $el->box->contracts->first()->id,
                        'contract_number' => $el->box->contracts->first()->contract_number,
                        'customer' => $el->box->contracts->first()->customer ? [
                            'id' => $el->box->contracts->first()->customer->id,
                            'name' => $el->box->contracts->first()->customer->full_name,
                        ] : null,
                    ] : null,
                ];
            });

        // Get all boxes with their contracts and customers (legacy support)
        $boxes = Box::where('site_id', $currentSite->id)
            ->with(['contracts' => function ($query) {
                $query->where('status', 'active')->with('customer');
            }])
            ->orderBy('number')
            ->get()
            ->map(function ($box) {
                $activeContract = $box->contracts->first();

                // Determine status based on contract and box state
                $status = 'available';
                if ($activeContract) {
                    $status = 'occupied';
                    // Check if contract is ending soon (within 30 days)
                    if ($activeContract->end_date && $activeContract->end_date->diffInDays(now()) <= 30) {
                        $status = 'ending';
                    }
                } elseif ($box->status === 'reserved') {
                    $status = 'reserved';
                } elseif ($box->status === 'maintenance') {
                    $status = 'maintenance';
                } elseif ($box->status === 'unavailable') {
                    $status = 'unavailable';
                }

                // Get position from plan element if exists
                $position = null;
                if ($box->planElement) {
                    $position = [
                        'x' => $box->planElement->x,
                        'y' => $box->planElement->y,
                        'width' => $box->planElement->width,
                        'height' => $box->planElement->height,
                    ];
                }

                return [
                    'id' => $box->id,
                    'number' => $box->number,
                    'volume' => $box->volume,
                    'dimensions' => $box->length && $box->width && $box->height
                        ? "{$box->length}m × {$box->width}m × {$box->height}m"
                        : null,
                    'current_price' => $box->current_price ?? $box->base_price,
                    'status' => $status,
                    'position' => $position,
                    'contract' => $activeContract ? [
                        'id' => $activeContract->id,
                        'contract_number' => $activeContract->contract_number,
                        'start_date' => $activeContract->start_date?->format('Y-m-d'),
                        'end_date' => $activeContract->end_date?->format('Y-m-d'),
                        'monthly_rent' => $activeContract->monthly_price,
                        'customer' => $activeContract->customer ? [
                            'id' => $activeContract->customer->id,
                            'name' => $activeContract->customer->full_name,
                            'email' => $activeContract->customer->email,
                            'phone' => $activeContract->customer->phone,
                        ] : null,
                    ] : null,
                ];
            });

        // Get configuration
        $config = PlanConfiguration::where('site_id', $currentSite->id)->first();
        $configuration = [
            'canvas_width' => $config?->canvas_width ?? 1200,
            'canvas_height' => $config?->canvas_height ?? 800,
            'show_grid' => $config?->show_grid ?? true,
            'grid_size' => $config?->grid_size ?? 20,
        ];

        // Calculate statistics from elements if available, otherwise from boxes
        $statsSource = $elements->count() > 0 ? $elements : $boxes;
        $stats = [
            'total' => $statsSource->count(),
            'occupied' => $statsSource->where('status', 'occupied')->count(),
            'available' => $statsSource->where('status', 'available')->count(),
            'reserved' => $statsSource->where('status', 'reserved')->count(),
            'maintenance' => $statsSource->where('status', 'maintenance')->count(),
            'ending' => $statsSource->where('status', 'ending')->count(),
        ];
        $stats['occupancy_rate'] = $stats['total'] > 0
            ? round(($stats['occupied'] / $stats['total']) * 100, 1)
            : 0;

        return Inertia::render('Tenant/Plan/Interactive', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'boxes' => $boxes,
            'elements' => $elements,
            'configuration' => $configuration,
            'statistics' => $stats,
        ]);
    }

    /**
     * Display the plan editor
     */
    public function editor(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $siteId = $request->get('site_id');

        $sites = Site::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'code')
            ->get();

        $currentSite = $siteId
            ? Site::where('id', $siteId)->where('tenant_id', $tenant->id)->first()
            : $sites->first();

        if (!$currentSite) {
            return Inertia::render('Tenant/Plan/Editor', [
                'sites' => $sites,
                'currentSite' => null,
                'elements' => [],
                'configuration' => null,
                'boxes' => [],
                'floors' => [],
            ]);
        }

        $floors = Floor::where('site_id', $currentSite->id)->get();

        $elements = PlanElement::where('site_id', $currentSite->id)
            ->orderBy('z_index')
            ->get()
            ->map(function ($el) {
                return [
                    'id' => $el->id,
                    'type' => $el->element_type,
                    'x' => (float) $el->x,
                    'y' => (float) $el->y,
                    'w' => (float) $el->width,
                    'h' => (float) $el->height,
                    'fill' => $el->fill_color ?? '#4CAF50',
                    'z' => $el->z_index ?? 100,
                    'name' => $el->label ?? '',
                    'vol' => $el->properties['volume'] ?? 0,
                    'status' => $el->properties['status'] ?? 'available',
                    'locked' => $el->is_locked ?? false,
                    'visible' => $el->is_visible ?? true,
                    'boxId' => $el->box_id,
                ];
            });

        $configuration = PlanConfiguration::firstOrCreate(
            ['site_id' => $currentSite->id],
            [
                'canvas_width' => 1920,
                'canvas_height' => 1080,
            ]
        );

        // Get all boxes for this site (for linking)
        $boxes = Box::where('site_id', $currentSite->id)
            ->select('id', 'number', 'volume', 'length', 'width', 'height', 'current_price', 'base_price', 'status')
            ->get();

        // Get boxes not yet on the plan
        $boxesOnPlan = PlanElement::where('site_id', $currentSite->id)
            ->whereNotNull('box_id')
            ->pluck('box_id');

        $unplacedBoxes = $boxes->whereNotIn('id', $boxesOnPlan);

        return Inertia::render('Tenant/Plan/Editor', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'elements' => $elements,
            'configuration' => $configuration,
            'boxes' => $boxes,
            'unplacedBoxes' => $unplacedBoxes->values(),
            'floors' => $floors,
        ]);
    }

    /**
     * Save plan elements
     */
    public function saveElements(Request $request, Site $site)
    {
        $tenant = auth()->user()->tenant;

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'elements' => 'required|array',
        ]);

        // Delete existing elements and recreate
        PlanElement::where('site_id', $site->id)->delete();

        foreach ($request->elements as $index => $elementData) {
            // Support both formats: old format (element_type, width, height) and new format (type, w, h)
            $elementType = $elementData['type'] ?? $elementData['element_type'] ?? 'box';
            $width = $elementData['w'] ?? $elementData['width'] ?? 35;
            $height = $elementData['h'] ?? $elementData['height'] ?? 30;
            $zIndex = $elementData['z'] ?? $elementData['z_index'] ?? $index;
            $fillColor = $elementData['fill'] ?? $elementData['fill_color'] ?? null;
            $label = $elementData['name'] ?? $elementData['label'] ?? null;
            $boxId = $elementData['boxId'] ?? $elementData['box_id'] ?? null;
            $isLocked = $elementData['locked'] ?? $elementData['is_locked'] ?? false;
            $isVisible = $elementData['visible'] ?? $elementData['is_visible'] ?? true;

            // Store extra properties
            $properties = [
                'volume' => $elementData['vol'] ?? 0,
                'status' => $elementData['status'] ?? 'available',
            ];

            PlanElement::create([
                'site_id' => $site->id,
                'floor_id' => $elementData['floor_id'] ?? null,
                'element_type' => $elementType,
                'box_id' => $boxId,
                'x' => $elementData['x'],
                'y' => $elementData['y'],
                'width' => $width,
                'height' => $height,
                'rotation' => $elementData['rotation'] ?? 0,
                'z_index' => $zIndex,
                'fill_color' => $fillColor,
                'stroke_color' => $elementData['stroke_color'] ?? '#333',
                'stroke_width' => $elementData['stroke_width'] ?? 1,
                'opacity' => $elementData['opacity'] ?? 1,
                'font_size' => $elementData['font_size'] ?? null,
                'text_color' => $elementData['text_color'] ?? null,
                'label' => $label,
                'description' => $elementData['description'] ?? null,
                'properties' => $properties,
                'is_locked' => $isLocked,
                'is_visible' => $isVisible,
            ]);
        }

        return back()->with('success', 'Plan sauvegardé avec succès');
    }

    /**
     * Save plan configuration
     */
    public function saveConfiguration(Request $request, Site $site)
    {
        $tenant = auth()->user()->tenant;

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'canvas_width' => 'required|integer|min:800|max:4000',
            'canvas_height' => 'required|integer|min:600|max:3000',
        ]);

        PlanConfiguration::updateOrCreate(
            ['site_id' => $site->id],
            $request->only([
                'canvas_width',
                'canvas_height',
                'show_grid',
                'grid_size',
                'snap_to_grid',
                'default_box_available_color',
                'default_box_occupied_color',
                'default_box_reserved_color',
                'default_box_maintenance_color',
                'default_wall_color',
                'default_door_color',
                'background_image',
                'background_opacity',
                'initial_zoom',
                'initial_x',
                'initial_y',
                'show_box_labels',
                'show_box_sizes',
                'show_legend',
                'show_statistics',
            ])
        );

        return back()->with('success', 'Configuration sauvegardée');
    }

    /**
     * Generate Buxida-style layout for the plan
     */
    public function generateBuxidaLayout(Request $request, Site $site)
    {
        $tenant = auth()->user()->tenant;

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

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

        // Helper to add element
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

        // ============ LEFT SIDE BOXES ============
        $addElement('V6', 15, 120, 35, 50, 18);
        $addElement('L8', 15, 280, 35, 40, 11);
        $addElement('P3', 15, 520, 30, 25, 1.5);

        // ============ M COLUMN ============
        $addElement('M14', 55, 95, 40, 35, 18);
        $addElement('M12', 55, 132, 40, 35, 18);
        $addElement('M10', 55, 169, 40, 35, 18);
        $addElement('M8', 55, 206, 40, 35, 18);
        $addElement('M6', 55, 243, 40, 35, 18);
        $addElement('M4', 55, 280, 40, 35, 18);
        $addElement('M2', 55, 317, 35, 30, 18);

        // ============ K COLUMN ============
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

        // ============ J COLUMN ============
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

        // ============ I COLUMN ============
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

        // ============ H COLUMN ============
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

        // ============ G COLUMN ============
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

        // ============ F COLUMN ============
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

        // ============ E COLUMN ============
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

        // ============ D COLUMN ============
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

        // ============ C COLUMN ============
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

        // ============ X COLUMN ============
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

        // ============ BOTTOM SECTION ============
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

        return back()->with('success', 'Plan Buxida généré avec succès (' . count($elements) . ' éléments)');
    }

    /**
     * Auto-generate plan from boxes
     */
    public function autoGenerate(Request $request, Site $site)
    {
        $tenant = auth()->user()->tenant;

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $boxes = Box::where('site_id', $site->id)
            ->orderBy('number')
            ->get();

        // Delete existing elements
        PlanElement::where('site_id', $site->id)->delete();

        // Auto-layout boxes in a grid
        $x = 50;
        $y = 100;
        $maxRowHeight = 0;
        $canvasWidth = 1800;
        $padding = 10;

        foreach ($boxes as $index => $box) {
            // Calculate box dimensions based on volume
            $width = max(60, min(150, $box->volume * 8));
            $height = max(50, min(120, $box->volume * 6));

            // Check if we need to wrap to next row
            if ($x + $width > $canvasWidth) {
                $x = 50;
                $y += $maxRowHeight + $padding;
                $maxRowHeight = 0;
            }

            PlanElement::create([
                'site_id' => $site->id,
                'element_type' => 'box',
                'box_id' => $box->id,
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
                'z_index' => $index,
                'label' => $box->number,
                'stroke_color' => '#1e3a5f',
                'stroke_width' => 2,
            ]);

            $x += $width + $padding;
            $maxRowHeight = max($maxRowHeight, $height);
        }

        return back()->with('success', 'Plan généré automatiquement');
    }

    /**
     * Display template selector
     */
    public function templates(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $templates = PlanTemplate::where('tenant_id', $tenant->id)
            ->orWhere('is_public', true)
            ->orderBy('category')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentPlans = Site::where('tenant_id', $tenant->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'updated_at']);

        return Inertia::render('Tenant/Plan/TemplateSelector', [
            'templates' => $templates,
            'recentPlans' => $recentPlans,
        ]);
    }

    /**
     * Create a new plan from template
     */
    public function create(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $request->validate([
            'template_id' => 'nullable|exists:plan_templates,id',
        ]);

        // Get first site or redirect to templates if none exist
        $site = Site::where('tenant_id', $tenant->id)->first();

        if (!$site) {
            return redirect()->route('tenant.sites.create')
                ->with('warning', 'Veuillez d\'abord créer un site');
        }

        // If template is provided, copy its elements
        if ($request->template_id) {
            $template = PlanTemplate::findOrFail($request->template_id);

            // Create configuration from template
            PlanConfiguration::updateOrCreate(
                ['site_id' => $site->id],
                [
                    'canvas_width' => $template->width,
                    'canvas_height' => $template->height,
                ]
            );

            // Copy template elements if available
            if ($template->template_data) {
                PlanElement::where('site_id', $site->id)->delete();

                foreach ($template->template_data as $elementData) {
                    PlanElement::create([
                        'site_id' => $site->id,
                        'element_type' => $elementData['element_type'],
                        'x' => $elementData['x'],
                        'y' => $elementData['y'],
                        'width' => $elementData['width'],
                        'height' => $elementData['height'],
                        'rotation' => $elementData['rotation'] ?? 0,
                        'fill_color' => $elementData['fill_color'] ?? null,
                        'stroke_color' => $elementData['stroke_color'] ?? null,
                        'stroke_width' => $elementData['stroke_width'] ?? 1,
                        'opacity' => $elementData['opacity'] ?? 1,
                        'label' => $elementData['label'] ?? null,
                    ]);
                }
            }
        } else {
            // Create default configuration
            PlanConfiguration::firstOrCreate(
                ['site_id' => $site->id],
                [
                    'canvas_width' => 1920,
                    'canvas_height' => 1080,
                ]
            );
        }

        return redirect()->route('tenant.plan.editor', ['site_id' => $site->id])
            ->with('success', 'Plan créé avec succès');
    }

    /**
     * Get floors for a site (API endpoint)
     */
    public function getFloors(Site $site)
    {
        $tenant = auth()->user()->tenant;

        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $floors = Floor::where('site_id', $site->id)
            ->orderBy('floor_number')
            ->get(['id', 'name', 'floor_number', 'total_area']);

        return response()->json([
            'floors' => $floors,
            'count' => $floors->count(),
        ]);
    }

    /**
     * Get box details for popup
     */
    public function getBoxDetails(Box $box)
    {
        $tenant = auth()->user()->tenant;

        if ($box->site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $box->load(['contracts' => function ($query) {
            $query->where('status', 'active')
                ->with(['customer', 'invoices' => function ($q) {
                    $q->latest()->take(3);
                }]);
        }]);

        $activeContract = $box->contracts->first();

        return response()->json([
            'box' => [
                'id' => $box->id,
                'number' => $box->number,
                'volume' => $box->volume,
                'dimensions' => "{$box->length}m x {$box->width}m x {$box->height}m",
                'price' => $box->current_price ?? $box->base_price,
                'status' => $box->status,
            ],
            'contract' => $activeContract ? [
                'id' => $activeContract->id,
                'number' => $activeContract->contract_number,
                'start_date' => $activeContract->start_date->format('d/m/Y'),
                'end_date' => $activeContract->end_date?->format('d/m/Y'),
                'monthly_price' => $activeContract->monthly_price,
            ] : null,
            'customer' => $activeContract?->customer ? [
                'id' => $activeContract->customer->id,
                'name' => $activeContract->customer->full_name,
                'email' => $activeContract->customer->email,
                'phone' => $activeContract->customer->phone,
            ] : null,
            'recent_invoices' => $activeContract?->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'amount' => $invoice->total,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date->format('d/m/Y'),
                ];
            }) ?? [],
        ]);
    }
}
