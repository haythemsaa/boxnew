<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['tenant:id,name', 'roles:id,name']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', '!=', 'active')->count(),
            'super_admins' => User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->count(),
            'tenant_admins' => User::whereHas('roles', fn($q) => $q->where('name', 'tenant_admin'))->count(),
        ];

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'tenant_id', 'role', 'status']),
        ]);
    }

    public function create()
    {
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Users/Create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'required|in:super_admin,tenant_admin,tenant_staff',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'tenant_id' => $validated['tenant_id'],
            'status' => $validated['status'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('superadmin.users.index')
            ->with('success', "Utilisateur '{$user->name}' créé avec succès.");
    }

    public function show(User $user)
    {
        $user->load(['tenant', 'roles', 'permissions']);

        // Activity history could be added here with spatie/laravel-activitylog

        return Inertia::render('SuperAdmin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['roles']);
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Users/Edit', [
            'user' => $user,
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'required|in:super_admin,tenant_admin,tenant_staff',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'tenant_id' => $validated['tenant_id'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        // Update role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('superadmin.users.show', $user)
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('superadmin.users.index')
            ->with('success', "Utilisateur '{$user->name}' supprimé.");
    }

    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'activé' : 'suspendu';
        return back()->with('success', "Utilisateur {$message}.");
    }
}
