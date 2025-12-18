<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Site;
use App\Models\SiteMedia;
use App\Models\VirtualTour;
use App\Models\VirtualTourPanorama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;

class MediaGalleryController extends Controller
{
    /**
     * Media gallery dashboard
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->withCount('media')
            ->get(['id', 'name', 'code']);

        $selectedSiteId = $request->input('site_id', $sites->first()?->id);

        $media = SiteMedia::where('tenant_id', $tenantId)
            ->when($selectedSiteId, fn($q) => $q->where('site_id', $selectedSiteId))
            ->with('uploader:id,name')
            ->ordered()
            ->paginate(24);

        $virtualTours = VirtualTour::where('tenant_id', $tenantId)
            ->when($selectedSiteId, fn($q) => $q->where('site_id', $selectedSiteId))
            ->withCount('panoramas')
            ->get();

        $stats = [
            'total_photos' => SiteMedia::where('tenant_id', $tenantId)->photos()->count(),
            'total_videos' => SiteMedia::where('tenant_id', $tenantId)->videos()->count(),
            'total_360' => SiteMedia::where('tenant_id', $tenantId)->photos360()->count(),
            'total_tours' => VirtualTour::where('tenant_id', $tenantId)->count(),
            'storage_used' => SiteMedia::where('tenant_id', $tenantId)->sum('size'),
        ];

        return Inertia::render('Tenant/Media/Gallery', [
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'media' => $media,
            'virtualTours' => $virtualTours,
            'stats' => $stats,
            'categories' => SiteMedia::getCategoryOptions(),
            'types' => SiteMedia::getTypeOptions(),
        ]);
    }

    /**
     * Upload media
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'nullable|exists:boxes,id',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov|max:51200', // 50MB max
            'type' => 'nullable|in:photo,video,photo_360,virtual_tour',
            'category' => 'nullable|in:exterior,interior,entrance,corridor,box,security,amenities,surroundings,team,other',
            'is_360' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $site = Site::findOrFail($validated['site_id']);

        if ($site->tenant_id !== $tenantId) {
            abort(403);
        }

        $uploaded = [];
        $files = $request->file('files', []);

        foreach ($files as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "media/{$tenantId}/{$validated['site_id']}/{$filename}";

            // Store file
            Storage::disk('public')->put($path, file_get_contents($file));

            // Determine type
            $mimeType = $file->getMimeType();
            $type = $validated['type'] ?? 'photo';

            if (str_starts_with($mimeType, 'video/')) {
                $type = 'video';
            }

            // Get dimensions for images
            $width = null;
            $height = null;
            $thumbnailPath = null;

            if (str_starts_with($mimeType, 'image/')) {
                try {
                    $image = Image::read(Storage::disk('public')->path($path));
                    $width = $image->width();
                    $height = $image->height();

                    // Create thumbnail
                    $thumbnailPath = "media/{$tenantId}/{$validated['site_id']}/thumbs/{$filename}";
                    Storage::disk('public')->makeDirectory(dirname($thumbnailPath));

                    $thumbnail = $image->scale(width: 400);
                    $thumbnail->save(Storage::disk('public')->path($thumbnailPath));
                } catch (\Exception $e) {
                    // Continue without thumbnail
                }
            }

            $media = SiteMedia::create([
                'tenant_id' => $tenantId,
                'site_id' => $validated['site_id'],
                'box_id' => $validated['box_id'] ?? null,
                'type' => $type,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'width' => $width,
                'height' => $height,
                'is_360' => $validated['is_360'] ?? false,
                'category' => $validated['category'] ?? 'other',
                'uploaded_by' => $request->user()->id,
            ]);

            $uploaded[] = $media;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' fichier(s) televerse(s)',
            'media' => $uploaded,
        ]);
    }

    /**
     * Update media
     */
    public function update(Request $request, SiteMedia $media): JsonResponse
    {
        if ($media->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'alt_text' => 'nullable|string|max:255',
            'category' => 'nullable|in:exterior,interior,entrance,corridor,box,security,amenities,surroundings,team,other',
            'is_featured' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'show_on_widget' => 'nullable|boolean',
            'show_on_portal' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $media->update($validated);

        return response()->json([
            'success' => true,
            'media' => $media->fresh(),
        ]);
    }

    /**
     * Delete media
     */
    public function destroy(Request $request, SiteMedia $media): JsonResponse
    {
        if ($media->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Delete files
        Storage::disk('public')->delete($media->path);
        if ($media->thumbnail_path) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media supprime',
        ]);
    }

    /**
     * Bulk delete media
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:site_media,id',
        ]);

        $tenantId = $request->user()->tenant_id;
        $deleted = 0;

        foreach ($validated['media_ids'] as $id) {
            $media = SiteMedia::find($id);
            if ($media && $media->tenant_id === $tenantId) {
                Storage::disk('public')->delete($media->path);
                if ($media->thumbnail_path) {
                    Storage::disk('public')->delete($media->thumbnail_path);
                }
                $media->delete();
                $deleted++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$deleted} fichier(s) supprime(s)",
        ]);
    }

    /**
     * Reorder media
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:site_media,id',
            'order.*.sort_order' => 'required|integer',
        ]);

        $tenantId = $request->user()->tenant_id;

        foreach ($validated['order'] as $item) {
            SiteMedia::where('id', $item['id'])
                ->where('tenant_id', $tenantId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set featured image
     */
    public function setFeatured(Request $request, SiteMedia $media): JsonResponse
    {
        if ($media->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Remove featured from other media of same site
        SiteMedia::where('site_id', $media->site_id)
            ->where('id', '!=', $media->id)
            ->update(['is_featured' => false]);

        $media->update(['is_featured' => true]);

        // Also set as site cover image
        $media->site->update(['cover_image_path' => $media->path]);

        return response()->json([
            'success' => true,
            'message' => 'Image mise en avant',
        ]);
    }

    // ==========================================
    // VIRTUAL TOURS
    // ==========================================

    /**
     * Create virtual tour
     */
    public function createTour(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'provider' => 'nullable|string|max:50',
            'embed_code' => 'nullable|string',
            'external_url' => 'nullable|url',
        ]);

        $tenantId = $request->user()->tenant_id;

        $tour = VirtualTour::create([
            'tenant_id' => $tenantId,
            'site_id' => $validated['site_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'provider' => $validated['provider'] ?? null,
            'embed_code' => $validated['embed_code'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'tour' => $tour,
        ]);
    }

    /**
     * Show virtual tour editor
     */
    public function editTour(Request $request, VirtualTour $tour): Response
    {
        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tour->load(['panoramas', 'site']);

        $availableMedia = SiteMedia::where('tenant_id', $request->user()->tenant_id)
            ->where('site_id', $tour->site_id)
            ->where('is_360', true)
            ->get();

        return Inertia::render('Tenant/Media/TourEditor', [
            'tour' => $tour,
            'availableMedia' => $availableMedia,
        ]);
    }

    /**
     * Update virtual tour
     */
    public function updateTour(Request $request, VirtualTour $tour): JsonResponse
    {
        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'autoplay' => 'sometimes|boolean',
            'start_panorama_id' => 'nullable|exists:virtual_tour_panoramas,id',
            'embed_code' => 'nullable|string',
            'external_url' => 'nullable|url',
        ]);

        $tour->update($validated);

        return response()->json([
            'success' => true,
            'tour' => $tour->fresh(),
        ]);
    }

    /**
     * Delete virtual tour
     */
    public function deleteTour(Request $request, VirtualTour $tour): JsonResponse
    {
        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Delete panorama images
        foreach ($tour->panoramas as $panorama) {
            Storage::disk('public')->delete($panorama->image_path);
            if ($panorama->thumbnail_path) {
                Storage::disk('public')->delete($panorama->thumbnail_path);
            }
        }

        $tour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Visite virtuelle supprimee',
        ]);
    }

    /**
     * Add panorama to tour
     */
    public function addPanorama(Request $request, VirtualTour $tour): JsonResponse
    {
        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png|max:51200',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "tours/{$tour->tenant_id}/{$tour->id}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file));

        // Create thumbnail
        $thumbnailPath = "tours/{$tour->tenant_id}/{$tour->id}/thumbs/{$filename}";
        Storage::disk('public')->makeDirectory(dirname($thumbnailPath));

        try {
            $image = Image::read(Storage::disk('public')->path($path));
            $thumbnail = $image->scale(width: 300);
            $thumbnail->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            $thumbnailPath = null;
        }

        $panorama = VirtualTourPanorama::create([
            'virtual_tour_id' => $tour->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'sort_order' => $tour->panoramas()->count(),
        ]);

        // Set as start panorama if first
        if ($tour->panoramas()->count() === 1) {
            $tour->update(['start_panorama_id' => $panorama->id]);
        }

        return response()->json([
            'success' => true,
            'panorama' => $panorama,
        ]);
    }

    /**
     * Update panorama
     */
    public function updatePanorama(Request $request, VirtualTourPanorama $panorama): JsonResponse
    {
        $tour = $panorama->tour;

        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'initial_pitch' => 'sometimes|numeric',
            'initial_yaw' => 'sometimes|numeric',
            'initial_hfov' => 'sometimes|numeric',
            'hotspots' => 'nullable|array',
            'sort_order' => 'sometimes|integer',
        ]);

        $panorama->update($validated);

        return response()->json([
            'success' => true,
            'panorama' => $panorama->fresh(),
        ]);
    }

    /**
     * Delete panorama
     */
    public function deletePanorama(Request $request, VirtualTourPanorama $panorama): JsonResponse
    {
        $tour = $panorama->tour;

        if ($tour->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        Storage::disk('public')->delete($panorama->image_path);
        if ($panorama->thumbnail_path) {
            Storage::disk('public')->delete($panorama->thumbnail_path);
        }

        $panorama->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    // ==========================================
    // PUBLIC GALLERY
    // ==========================================

    /**
     * Get public gallery for site
     */
    public function publicGallery(Request $request, Site $site): JsonResponse
    {
        $media = SiteMedia::where('site_id', $site->id)
            ->public()
            ->forWidget()
            ->ordered()
            ->get(['id', 'type', 'title', 'alt_text', 'path', 'thumbnail_path', 'category', 'is_360', 'video_url']);

        $tours = VirtualTour::where('site_id', $site->id)
            ->active()
            ->public()
            ->with('panoramas:id,virtual_tour_id,name,thumbnail_path')
            ->get();

        return response()->json([
            'success' => true,
            'media' => $media->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'title' => $m->title,
                'alt' => $m->alt_text,
                'url' => $m->url,
                'thumbnail' => $m->thumbnail_url,
                'category' => $m->category,
                'is_360' => $m->is_360,
                'video_url' => $m->video_url,
            ]),
            'tours' => $tours,
        ]);
    }
}
