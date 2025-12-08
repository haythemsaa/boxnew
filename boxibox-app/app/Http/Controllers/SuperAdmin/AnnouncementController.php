<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('creator:id,name')
            ->withCount('reads')
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'scheduled') {
                $query->where('starts_at', '>', now());
            }
        }

        $announcements = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::active()->count(),
            'scheduled' => Announcement::where('starts_at', '>', now())->count(),
        ];

        return Inertia::render('SuperAdmin/Announcements/Index', [
            'announcements' => $announcements,
            'stats' => $stats,
            'filters' => $request->only(['type', 'status']),
        ]);
    }

    public function create()
    {
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Announcements/Create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,maintenance,feature,promotion',
            'target' => 'required|in:all,tenants,specific',
            'target_tenant_ids' => 'nullable|array',
            'target_tenant_ids.*' => 'exists:tenants,id',
            'is_active' => 'boolean',
            'is_dismissible' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['created_by'] = auth()->id();

        Announcement::create($validated);

        return redirect()->route('superadmin.announcements.index')
            ->with('success', 'Annonce créée avec succès.');
    }

    public function show(Announcement $announcement)
    {
        $announcement->load(['creator:id,name', 'reads.user:id,name']);

        return Inertia::render('SuperAdmin/Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    public function edit(Announcement $announcement)
    {
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Announcements/Edit', [
            'announcement' => $announcement,
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,maintenance,feature,promotion',
            'target' => 'required|in:all,tenants,specific',
            'target_tenant_ids' => 'nullable|array',
            'target_tenant_ids.*' => 'exists:tenants,id',
            'is_active' => 'boolean',
            'is_dismissible' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $announcement->update($validated);

        return redirect()->route('superadmin.announcements.index')
            ->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('superadmin.announcements.index')
            ->with('success', 'Annonce supprimée.');
    }

    public function toggle(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);

        return back()->with('success', $announcement->is_active ? 'Annonce activée.' : 'Annonce désactivée.');
    }
}
