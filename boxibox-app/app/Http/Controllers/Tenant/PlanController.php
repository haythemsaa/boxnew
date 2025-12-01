<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Floor;
use App\Models\PlanConfiguration;
use App\Models\PlanElement;
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
            ->select('size_m3')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM contracts WHERE contracts.box_id = boxes.id AND contracts.status = "active") THEN 1 ELSE 0 END) as occupied')
            ->groupBy('size_m3')
            ->orderBy('size_m3')
            ->get()
            ->map(function ($type) {
                return [
                    'size' => $type->size_m3 . 'm³',
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
            ->select('id', 'code', 'size_m3', 'width', 'depth', 'height', 'monthly_price', 'status')
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
            ->orderBy('code')
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
            // Calculate box dimensions based on size
            $width = max(60, min(150, $box->size_m3 * 8));
            $height = max(50, min(120, $box->size_m3 * 6));

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
                'label' => $box->code,
                'stroke_color' => '#1e3a5f',
                'stroke_width' => 2,
            ]);

            $x += $width + $padding;
            $maxRowHeight = max($maxRowHeight, $height);
        }

        return back()->with('success', 'Plan généré automatiquement');
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
                'code' => $box->code,
                'size_m3' => $box->size_m3,
                'dimensions' => "{$box->width}m x {$box->depth}m x {$box->height}m",
                'monthly_price' => $box->monthly_price,
                'status' => $box->status,
            ],
            'contract' => $activeContract ? [
                'id' => $activeContract->id,
                'number' => $activeContract->contract_number,
                'start_date' => $activeContract->start_date->format('d/m/Y'),
                'end_date' => $activeContract->end_date?->format('d/m/Y'),
                'monthly_rent' => $activeContract->monthly_rent,
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
