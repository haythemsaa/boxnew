<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->paginate($request->input('per_page', 15));

        return SiteResource::collection($sites);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'operating_hours' => ['nullable', 'json'],
            'amenities' => ['nullable', 'json'],
        ]);

        $site = Site::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return (new SiteResource($site))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Site $site): SiteResource
    {
        // Ensure tenant can only view their own sites
        if ($site->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $site->load('boxes');

        return new SiteResource($site);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site): SiteResource
    {
        // Ensure tenant can only update their own sites
        if ($site->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'country' => ['sometimes', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive,maintenance'],
            'operating_hours' => ['nullable', 'json'],
            'amenities' => ['nullable', 'json'],
        ]);

        $site->update($validated);

        return new SiteResource($site->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Site $site): JsonResponse
    {
        // Ensure tenant can only delete their own sites
        if ($site->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Check if site has any boxes
        if ($site->boxes()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete site with existing boxes.',
            ], 422);
        }

        $site->delete();

        return response()->json([
            'message' => 'Site deleted successfully',
        ]);
    }
}
