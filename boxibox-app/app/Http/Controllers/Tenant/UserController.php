<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs du tenant
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = User::where('tenant_id', $tenantId)
            ->with('roles');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par role
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Roles disponibles pour les tenants
        $roles = Role::whereIn('name', [
            'tenant_admin',
            'tenant_manager',
            'tenant_staff',
            'tenant_accountant',
            'tenant_viewer',
        ])->get();

        // Stats
        $stats = [
            'total' => User::where('tenant_id', $tenantId)->count(),
            'active' => User::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'admins' => User::where('tenant_id', $tenantId)
                ->whereHas('roles', fn($q) => $q->where('name', 'tenant_admin'))
                ->count(),
        ];

        // Verification des limites
        $tenant = Auth::user()->tenant;
        $canCreateUser = true;
        $limitMessage = null;
        if ($tenant->max_users && $stats['total'] >= $tenant->max_users) {
            $canCreateUser = false;
            $limitMessage = "Limite de {$tenant->max_users} utilisateurs atteinte.";
        }

        return Inertia::render('Tenant/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
            'canCreateUser' => $canCreateUser,
            'limitMessage' => $limitMessage,
            'maxUsers' => $tenant->max_users,
        ]);
    }

    /**
     * Formulaire de creation
     */
    public function create()
    {
        $tenant = Auth::user()->tenant;
        $currentCount = User::where('tenant_id', $tenant->id)->count();

        // Verification des limites
        if ($tenant->max_users && $currentCount >= $tenant->max_users) {
            return redirect()->route('tenant.users.index')
                ->with('error', "Limite de {$tenant->max_users} utilisateurs atteinte.");
        }

        $roles = Role::whereIn('name', [
            'tenant_admin',
            'tenant_manager',
            'tenant_staff',
            'tenant_accountant',
            'tenant_viewer',
        ])->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->getRoleLabel($role->name),
                'description' => $this->getRoleDescription($role->name),
            ];
        });

        return Inertia::render('Tenant/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $currentCount = User::where('tenant_id', $tenant->id)->count();

        // Verification des limites
        if ($tenant->max_users && $currentCount >= $tenant->max_users) {
            return back()->with('error', "Limite de {$tenant->max_users} utilisateurs atteinte.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        // Verifier que le role est autorise pour les tenants
        if (!in_array($validated['role'], ['tenant_admin', 'tenant_manager', 'tenant_staff', 'tenant_accountant', 'tenant_viewer'])) {
            return back()->with('error', 'Role non autorise.');
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('tenant.users.index')
            ->with('success', "Utilisateur '{$user->name}' cree avec succes.");
    }

    /**
     * Afficher un utilisateur
     */
    public function show(User $user)
    {
        $this->authorizeUser($user);
        $user->load('roles');

        return Inertia::render('Tenant/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Formulaire d'edition
     */
    public function edit(User $user)
    {
        $this->authorizeUser($user);
        $user->load('roles');

        $roles = Role::whereIn('name', [
            'tenant_admin',
            'tenant_manager',
            'tenant_staff',
            'tenant_accountant',
            'tenant_viewer',
        ])->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->getRoleLabel($role->name),
                'description' => $this->getRoleDescription($role->name),
            ];
        });

        return Inertia::render('Tenant/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    /**
     * Mettre a jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeUser($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        // Verifier que le role est autorise pour les tenants
        if (!in_array($validated['role'], ['tenant_admin', 'tenant_manager', 'tenant_staff', 'tenant_accountant', 'tenant_viewer'])) {
            return back()->with('error', 'Role non autorise.');
        }

        // Empecher de se retirer soi-meme le role admin si c'est le dernier admin
        if ($user->id === Auth::id() && $user->hasRole('tenant_admin') && $validated['role'] !== 'tenant_admin') {
            $adminCount = User::where('tenant_id', $user->tenant_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'tenant_admin'))
                ->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Impossible de retirer le role admin - vous etes le seul administrateur.');
            }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Mise a jour du role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('tenant.users.index')
            ->with('success', "Utilisateur '{$user->name}' mis a jour.");
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        $this->authorizeUser($user);

        // Empecher de se supprimer soi-meme
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Empecher de supprimer le dernier admin
        if ($user->hasRole('tenant_admin')) {
            $adminCount = User::where('tenant_id', $user->tenant_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'tenant_admin'))
                ->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('tenant.users.index')
            ->with('success', "Utilisateur '{$userName}' supprime.");
    }

    /**
     * Basculer le statut d'un utilisateur
     */
    public function toggleStatus(User $user)
    {
        $this->authorizeUser($user);

        // Empecher de se desactiver soi-meme
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas desactiver votre propre compte.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "Statut de '{$user->name}' change en {$newStatus}.");
    }

    /**
     * Verifier que l'utilisateur appartient au meme tenant
     */
    private function authorizeUser(User $user): void
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Acces non autorise.');
        }
    }

    /**
     * Obtenir le label lisible d'un role
     */
    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'tenant_admin' => 'Administrateur',
            'tenant_manager' => 'Manager',
            'tenant_staff' => 'Personnel',
            'tenant_accountant' => 'Comptable',
            'tenant_viewer' => 'Lecteur',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    /**
     * Obtenir la description d'un role
     */
    private function getRoleDescription(string $role): string
    {
        return match($role) {
            'tenant_admin' => 'Acces complet a toutes les fonctionnalites, gestion des utilisateurs et parametres.',
            'tenant_manager' => 'Gestion des clients, contrats, factures. Pas de gestion des utilisateurs.',
            'tenant_staff' => 'Operations quotidiennes: check-in, check-out, gestion des boxes.',
            'tenant_accountant' => 'Acces aux factures, paiements et rapports financiers uniquement.',
            'tenant_viewer' => 'Consultation en lecture seule de toutes les donnees.',
            default => '',
        };
    }

    /**
     * Afficher les préférences utilisateur
     */
    public function preferences()
    {
        $user = Auth::user();

        return Inertia::render('Tenant/Users/Preferences', [
            'user' => $user,
            'preferences' => $user->preferences ?? [],
        ]);
    }

    /**
     * Mettre à jour les préférences utilisateur
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'preferences' => 'nullable|array',
            'preferences.theme' => 'nullable|in:light,dark,system',
            'preferences.language' => 'nullable|in:fr,en,es,de',
            'preferences.notifications_email' => 'nullable|boolean',
            'preferences.notifications_push' => 'nullable|boolean',
            'preferences.dashboard_widgets' => 'nullable|array',
        ]);

        $user->update([
            'preferences' => $validated['preferences'] ?? [],
        ]);

        return back()->with('success', 'Préférences mises à jour.');
    }
}
