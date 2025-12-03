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
                'configuration' => [
                    'canvas_width' => 1200,
                    'canvas_height' => 800,
                    'show_grid' => true,
                ],
                'statistics' => null,
            ]);
        }

        // Get all boxes with their contracts and customers
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

        // Calculate statistics
        $stats = [
            'total' => $boxes->count(),
            'occupied' => $boxes->where('status', 'occupied')->count(),
            'available' => $boxes->where('status', 'available')->count(),
            'reserved' => $boxes->where('status', 'reserved')->count(),
            'maintenance' => $boxes->where('status', 'maintenance')->count(),
            'ending' => $boxes->where('status', 'ending')->count(),
        ];
        $stats['occupancy_rate'] = $stats['total'] > 0
            ? round(($stats['occupied'] / $stats['total']) * 100, 1)
            : 0;

        return Inertia::render('Tenant/Plan/Interactive', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'boxes' => $boxes,
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
            ->get();

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
            'elements.*.element_type' => 'required|string',
            'elements.*.x' => 'required|numeric',
            'elements.*.y' => 'required|numeric',
            'elements.*.width' => 'required|numeric',
            'elements.*.height' => 'required|numeric',
        ]);

        // Delete existing elements and recreate
        PlanElement::where('site_id', $site->id)->delete();

        foreach ($request->elements as $index => $elementData) {
            PlanElement::create([
                'site_id' => $site->id,
                'floor_id' => $elementData['floor_id'] ?? null,
                'element_type' => $elementData['element_type'],
                'box_id' => $elementData['box_id'] ?? null,
                'x' => $elementData['x'],
                'y' => $elementData['y'],
                'width' => $elementData['width'],
                'height' => $elementData['height'],
                'rotation' => $elementData['rotation'] ?? 0,
                'z_index' => $elementData['z_index'] ?? $index,
                'fill_color' => $elementData['fill_color'] ?? null,
                'stroke_color' => $elementData['stroke_color'] ?? null,
                'stroke_width' => $elementData['stroke_width'] ?? 1,
                'opacity' => $elementData['opacity'] ?? 1,
                'font_size' => $elementData['font_size'] ?? null,
                'text_color' => $elementData['text_color'] ?? null,
                'label' => $elementData['label'] ?? null,
                'description' => $elementData['description'] ?? null,
                'properties' => $elementData['properties'] ?? null,
                'is_locked' => $elementData['is_locked'] ?? false,
                'is_visible' => $elementData['is_visible'] ?? true,
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
                    'amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date->format('d/m/Y'),
                ];
            }) ?? [],
        ]);
    }
}
