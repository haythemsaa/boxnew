<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use App\Models\StaffShift;
use App\Models\StaffTask;
use App\Models\User;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = StaffProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['user', 'site']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('position', $request->role);
        }

        $staffProfiles = $query->paginate(15)->withQueryString();

        // Calculate hours worked this week
        $weekStart = now()->startOfWeek();
        $hoursThisWeek = StaffShift::where('tenant_id', $tenantId)
            ->where('shift_date', '>=', $weekStart)
            ->where('status', 'completed')
            ->get()
            ->sum('duration_hours');

        $stats = [
            'total_staff' => StaffProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))->count(),
            'on_shift_today' => StaffShift::where('tenant_id', $tenantId)
                ->whereDate('shift_date', now())
                ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                ->count(),
            'active_tasks' => StaffTask::where('tenant_id', $tenantId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
            'hours_this_week' => round($hoursThisWeek, 1),
        ];

        // Today's shifts
        $todayShifts = StaffShift::where('tenant_id', $tenantId)
            ->whereDate('shift_date', now())
            ->with(['user', 'site'])
            ->orderBy('start_time')
            ->get()
            ->map(fn($shift) => [
                'id' => $shift->id,
                'staff' => [
                    'user' => $shift->user,
                ],
                'site' => $shift->site,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'shift_type' => $shift->type ?? 'regular',
            ]);

        return Inertia::render('Tenant/Staff/Index', [
            'staffProfiles' => $staffProfiles,
            'stats' => $stats,
            'todayShifts' => $todayShifts,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Tenant/Staff/Create', [
            'availableUsers' => User::where('tenant_id', $tenantId)
                ->whereDoesntHave('staffProfile')
                ->get(['id', 'name', 'email']),
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Determine if we're creating a new user or using an existing one
        $createNewUser = $request->boolean('create_new_user');

        if ($createNewUser) {
            // Validate new user fields
            $request->validate([
                'new_user_name' => 'required|string|max:255',
                'new_user_email' => 'required|email|unique:users,email',
                'new_user_password' => 'required|string|min:8|confirmed',
            ]);

            // Create the new user
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $request->new_user_name,
                'email' => $request->new_user_email,
                'password' => bcrypt($request->new_user_password),
            ]);

            $userId = $user->id;
        } else {
            $request->validate([
                'user_id' => ['required', 'exists:users,id', 'unique:staff_profiles,user_id', new \App\Rules\SameTenantUser($tenantId)],
            ]);
            $userId = $request->user_id;
        }

        // Validate staff profile fields
        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'position' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:50',
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'skills' => 'nullable|array',
        ]);

        // Map employee_number to employee_id (database column name)
        if (isset($validated['employee_number'])) {
            $validated['employee_id'] = $validated['employee_number'];
            unset($validated['employee_number']);
        }

        $validated['user_id'] = $userId;
        $validated['tenant_id'] = $tenantId;

        StaffProfile::create($validated);

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Profil employé créé avec succès.');
    }

    public function show(StaffProfile $staff)
    {
        $this->authorize('view', $staff);

        $staff->load(['user', 'site']);

        // Load shifts for this user
        $shifts = StaffShift::where('user_id', $staff->user_id)
            ->latest('shift_date')
            ->limit(10)
            ->get();

        // Stats du mois
        $monthStart = now()->startOfMonth();
        $hoursWorked = StaffShift::where('user_id', $staff->user_id)
            ->where('shift_date', '>=', $monthStart)
            ->where('status', 'completed')
            ->get()
            ->sum('duration_hours');

        $tasksCompleted = StaffTask::where('assigned_to', $staff->user_id)
            ->where('completed_at', '>=', $monthStart)
            ->count();

        return Inertia::render('Tenant/Staff/Show', [
            'staff' => $staff,
            'shifts' => $shifts,
            'monthlyStats' => [
                'hours_worked' => round($hoursWorked, 1),
                'tasks_completed' => $tasksCompleted,
            ],
        ]);
    }

    public function edit(StaffProfile $staff)
    {
        $this->authorize('update', $staff);
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Tenant/Staff/Edit', [
            'staff' => $staff->load('user'),
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, StaffProfile $staff)
    {
        $this->authorize('update', $staff);
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'position' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'contract_type' => 'required|in:full_time,part_time,contractor,intern',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'skills' => 'nullable|array',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $staff->update($validated);

        return redirect()->route('tenant.staff.show', $staff)
            ->with('success', 'Profil mis à jour.');
    }

    // Planning
    public function schedule(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $weekStart = $request->input('week_start', now()->startOfWeek()->toDateString());
        $weekEnd = Carbon::parse($weekStart)->endOfWeek()->toDateString();

        $shifts = StaffShift::where('tenant_id', $tenantId)
            ->whereBetween('shift_date', [$weekStart, $weekEnd])
            ->with(['user', 'site'])
            ->get()
            ->groupBy('user_id');

        $staff = StaffProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->with('user')
            ->get();

        return Inertia::render('Tenant/Staff/Schedule', [
            'shifts' => $shifts,
            'staff' => $staff,
            'weekStart' => $weekStart,
            'sites' => Site::where('tenant_id', $tenantId)->get(['id', 'name']),
        ]);
    }

    public function storeShift(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'shift_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'nullable|in:regular,overtime,on_call,training,meeting',
            'notes' => 'nullable|string',
        ]);

        StaffShift::create([
            'tenant_id' => $tenantId,
            'user_id' => $validated['user_id'],
            'site_id' => $validated['site_id'],
            'shift_date' => $validated['shift_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'type' => $validated['type'] ?? 'regular',
            'status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Shift planifié.');
    }

    public function updateShift(Request $request, StaffShift $shift)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'nullable|in:regular,overtime,on_call,training,meeting',
            'status' => 'required|in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
            'notes' => 'nullable|string',
        ]);

        $shift->update([
            'site_id' => $validated['site_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'type' => $validated['type'] ?? 'regular',
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Shift mis à jour.');
    }

    public function deleteShift(StaffShift $shift)
    {
        $shift->delete();
        return back()->with('success', 'Shift supprimé.');
    }

    // Tâches
    public function tasks(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = StaffTask::where('tenant_id', $tenantId)
            ->with(['assignedTo', 'assignedBy', 'site']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('assigned_to', $request->user_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Staff/Tasks', [
            'tasks' => $tasks,
            'staff' => StaffProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
                ->with('user')
                ->get(),
            'filters' => $request->only(['status', 'user_id', 'priority']),
        ]);
    }

    public function storeTask(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string',
        ]);

        StaffTask::create([
            'tenant_id' => $tenantId,
            'assigned_to' => $validated['user_id'],
            'assigned_by' => Auth::id(),
            'site_id' => $validated['site_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_date' => $validated['due_date'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurrence_pattern' => $validated['recurrence_pattern'] ?? null,
        ]);

        return back()->with('success', 'Tâche créée.');
    }

    public function updateTaskStatus(Request $request, StaffTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $task->status = $validated['status'];
        if ($validated['status'] === 'completed') {
            $task->completed_at = now();
        }
        $task->save();

        return back()->with('success', 'Statut mis à jour.');
    }

    // Performance
    public function performance(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $period = $request->input('period', 'month');

        $periodStart = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Calculate performance metrics for all staff
        $staff = StaffProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('user')
            ->get();

        $performances = $staff->map(function ($staffProfile) use ($periodStart) {
            $userId = $staffProfile->user_id;

            $tasksCompleted = StaffTask::where('assigned_to', $userId)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $periodStart)
                ->count();

            $totalTasks = StaffTask::where('assigned_to', $userId)
                ->where('created_at', '>=', $periodStart)
                ->count();

            $shiftsWorked = StaffShift::where('user_id', $userId)
                ->where('shift_date', '>=', $periodStart)
                ->where('status', 'completed')
                ->count();

            $shiftsScheduled = StaffShift::where('user_id', $userId)
                ->where('shift_date', '>=', $periodStart)
                ->count();

            $hoursWorked = StaffShift::where('user_id', $userId)
                ->where('shift_date', '>=', $periodStart)
                ->where('status', 'completed')
                ->get()
                ->sum('duration_hours');

            return [
                'staff' => $staffProfile,
                'tasks_completed' => $tasksCompleted,
                'total_tasks' => $totalTasks,
                'completion_rate' => $totalTasks > 0 ? round(($tasksCompleted / $totalTasks) * 100) : 0,
                'shifts_worked' => $shiftsWorked,
                'shifts_scheduled' => $shiftsScheduled,
                'attendance_rate' => $shiftsScheduled > 0 ? round(($shiftsWorked / $shiftsScheduled) * 100) : 100,
                'hours_worked' => round($hoursWorked, 1),
            ];
        });

        return Inertia::render('Tenant/Staff/Performance', [
            'performances' => $performances,
            'period' => $period,
        ]);
    }

    public function generatePerformanceReport(StaffProfile $staff, Request $request)
    {
        $periodStart = Carbon::parse($request->input('period_start', now()->startOfMonth()));
        $periodEnd = Carbon::parse($request->input('period_end', now()->endOfMonth()));
        $userId = $staff->user_id;

        $tasksCompleted = StaffTask::where('assigned_to', $userId)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->count();

        $tasksOnTime = StaffTask::where('assigned_to', $userId)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->whereRaw('DATE(completed_at) <= due_date')
            ->count();

        $shiftsWorked = StaffShift::where('user_id', $userId)
            ->whereBetween('shift_date', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->count();

        $shiftsScheduled = StaffShift::where('user_id', $userId)
            ->whereBetween('shift_date', [$periodStart, $periodEnd])
            ->count();

        $hoursWorked = StaffShift::where('user_id', $userId)
            ->whereBetween('shift_date', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->get()
            ->sum('duration_hours');

        $report = [
            'staff' => $staff->load('user'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'tasks_completed' => $tasksCompleted,
            'tasks_on_time' => $tasksOnTime,
            'attendance_rate' => $shiftsScheduled > 0 ? round(($shiftsWorked / $shiftsScheduled) * 100) : 100,
            'hours_worked' => round($hoursWorked, 1),
        ];

        return back()->with([
            'success' => 'Rapport de performance généré.',
            'report' => $report,
        ]);
    }
}
