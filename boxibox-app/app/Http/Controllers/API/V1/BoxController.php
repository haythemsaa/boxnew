<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BoxResource;
use App\Models\Box;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $boxes = Box::where('tenant_id', $tenantId)
            ->with(['site'])
            ->when($request->input('site_id'), function ($query, $siteId) {
                $query->where('site_id', $siteId);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('size_category'), function ($query, $category) {
                $query->where('size_category', $category);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->paginate($request->input('per_page', 15));

        return BoxResource::collection($boxes);
    }

    /**
     * Get boxes by site.
     */
    public function bySite(Request $request, Site $site): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own sites
        if ($site->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $boxes = $site->boxes()
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->paginate($request->input('per_page', 15));

        return BoxResource::collection($boxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'size_category' => ['required', 'in:small,medium,large,extra_large'],
            'length' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,reserved,maintenance,inactive'],
            'condition' => ['nullable', 'in:excellent,good,fair,needs_repair'],
            'features' => ['nullable', 'json'],
            'access_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        // Ensure site belongs to tenant
        $site = Site::findOrFail($validated['site_id']);
        if ($site->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Calculate volume
        $volume = $validated['length'] * $validated['width'] * $validated['height'];

        $box = Box::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'volume' => $volume,
            'current_price' => $validated['current_price'] ?? $validated['base_price'],
        ]);

        return (new BoxResource($box->load('site')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Box $box): BoxResource
    {
        // Ensure tenant can only view their own boxes
        if ($box->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $box->load(['site', 'currentContract.customer']);

        return new BoxResource($box);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Box $box): BoxResource
    {
        // Ensure tenant can only update their own boxes
        if ($box->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'site_id' => ['sometimes', 'exists:sites,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'size_category' => ['sometimes', 'in:small,medium,large,extra_large'],
            'length' => ['sometimes', 'numeric', 'min:0'],
            'width' => ['sometimes', 'numeric', 'min:0'],
            'height' => ['sometimes', 'numeric', 'min:0'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:available,occupied,reserved,maintenance,inactive'],
            'condition' => ['nullable', 'in:excellent,good,fair,needs_repair'],
            'features' => ['nullable', 'json'],
            'access_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        // Recalculate volume if dimensions changed
        if (isset($validated['length']) || isset($validated['width']) || isset($validated['height'])) {
            $length = $validated['length'] ?? $box->length;
            $width = $validated['width'] ?? $box->width;
            $height = $validated['height'] ?? $box->height;
            $validated['volume'] = $length * $width * $height;
        }

        $box->update($validated);

        return new BoxResource($box->fresh()->load('site'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Box $box): JsonResponse
    {
        // Ensure tenant can only delete their own boxes
        if ($box->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Check if box has an active contract
        if ($box->currentContract()->exists()) {
            return response()->json([
                'message' => 'Cannot delete box with an active contract.',
            ], 422);
        }

        $box->delete();

        return response()->json([
            'message' => 'Box deleted successfully',
        ]);
    }
}
