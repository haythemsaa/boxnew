<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BoxResource;
use App\Models\Box;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Boxes
 */
class BoxController extends Controller
{
    /**
     * List all boxes
     *
     * Returns a paginated list of storage boxes belonging to the authenticated tenant.
     * Results can be filtered by site, status, size category, or search term.
     *
     * @queryParam site_id integer Filter by site ID. Example: 1
     * @queryParam status string Filter by status (available, occupied, reserved, maintenance, inactive). Example: available
     * @queryParam size_category string Filter by size (small, medium, large, extra_large). Example: medium
     * @queryParam search string Search by name or code. Example: A-101
     * @queryParam per_page integer Number of results per page (default: 15). Example: 25
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Box A-101",
     *       "code": "A-101",
     *       "status": "available",
     *       "size_category": "medium",
     *       "dimensions": {"length": 3, "width": 3, "height": 2.5},
     *       "volume": 22.5,
     *       "base_price": 120.00,
     *       "current_price": 130.00,
     *       "site": {"id": 1, "name": "Paris Centre"}
     *     }
     *   ],
     *   "meta": {"current_page": 1, "last_page": 5, "per_page": 15, "total": 75}
     * }
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
     * Get boxes by site
     *
     * Returns a paginated list of boxes belonging to a specific site.
     *
     * @urlParam site integer required The site ID. Example: 1
     * @queryParam status string Filter by status. Example: available
     * @queryParam per_page integer Results per page. Example: 15
     *
     * @response 200 {
     *   "data": [{"id": 1, "name": "Box A-101", "status": "available"}],
     *   "meta": {"current_page": 1, "total": 10}
     * }
     * @response 403 {"message": "Forbidden"}
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
     * Create a new box
     *
     * Creates a new storage box in the system. Volume is automatically calculated from dimensions.
     *
     * @bodyParam site_id integer required The ID of the site. Example: 1
     * @bodyParam name string required Box name. Example: Box A-102
     * @bodyParam code string Box reference code. Example: A-102
     * @bodyParam floor string Floor location. Example: 1
     * @bodyParam zone string Zone location. Example: A
     * @bodyParam size_category string required Size category (small, medium, large, extra_large). Example: medium
     * @bodyParam length numeric required Length in meters. Example: 3.0
     * @bodyParam width numeric required Width in meters. Example: 3.0
     * @bodyParam height numeric required Height in meters. Example: 2.5
     * @bodyParam base_price numeric required Base monthly price in EUR. Example: 120.00
     * @bodyParam current_price numeric Current price (defaults to base_price). Example: 130.00
     * @bodyParam status string required Status (available, occupied, reserved, maintenance, inactive). Example: available
     * @bodyParam condition string Condition (excellent, good, fair, needs_repair). Example: excellent
     * @bodyParam features object JSON features object. Example: {"climate_control": true}
     * @bodyParam access_code string Access code. Example: 1234
     * @bodyParam notes string Additional notes.
     *
     * @response 201 {
     *   "data": {
     *     "id": 2,
     *     "name": "Box A-102",
     *     "status": "available",
     *     "volume": 22.5
     *   }
     * }
     * @response 422 {"message": "Validation error", "errors": {}}
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
     * Get box details
     *
     * Returns detailed information about a specific box including site and current contract.
     *
     * @urlParam box integer required The box ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Box A-101",
     *     "site": {"id": 1, "name": "Paris Centre"},
     *     "current_contract": {"id": 1, "customer": {"name": "Jean Dupont"}}
     *   }
     * }
     * @response 403 {"message": "Forbidden"}
     * @response 404 {"message": "Not found"}
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
     * Update a box
     *
     * Updates box information. Volume is recalculated if dimensions change.
     *
     * @urlParam box integer required The box ID. Example: 1
     *
     * @bodyParam name string Box name. Example: Box A-101 Updated
     * @bodyParam status string Status. Example: maintenance
     * @bodyParam current_price numeric Current price. Example: 140.00
     *
     * @response 200 {"data": {"id": 1, "name": "Box A-101 Updated"}}
     * @response 403 {"message": "Forbidden"}
     * @response 422 {"message": "Validation error"}
     */
    public function update(Request $request, Box $box): BoxResource
    {
        // Ensure tenant can only update their own boxes
        if ($box->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['sometimes', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
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
     * Delete a box
     *
     * Deletes a storage box. Cannot delete boxes with active contracts.
     *
     * @urlParam box integer required The box ID. Example: 1
     *
     * @response 200 {"message": "Box deleted successfully"}
     * @response 403 {"message": "Forbidden"}
     * @response 422 {"message": "Cannot delete box with an active contract."}
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
