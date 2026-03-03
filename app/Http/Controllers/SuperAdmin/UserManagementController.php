<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateUserRoleRequest;
use App\Models\OfficeRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    /**
     * Show user management panel for super admins.
     */
    public function index(): Response
    {
        // ── Office role groups ─────────────────────────────────────────────
        $rawGroups = DB::table('office_user_roles')
            ->join('offices', 'offices.id', '=', 'office_user_roles.office_id')
            ->join('users', 'users.id', '=', 'office_user_roles.user_id')
            ->join('office_roles', 'office_roles.id', '=', 'office_user_roles.office_role_id')
            ->select([
                'office_roles.name as role_name',
                'office_roles.display_name as role_display_name',
                'offices.id as office_id',
                'offices.name as office_name',
                DB::raw("COALESCE(offices.code, '') as office_code"),
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'office_user_roles.assigned_at',
            ])
            ->get()
            ->groupBy('role_name');

        // Get all office roles from database
        $officeRoles = OfficeRole::orderBy('display_name')->get();
        $roleGroups  = [];
        foreach ($officeRoles as $role) {
            $roleGroups[$role->name] = ($rawGroups[$role->name] ?? collect())->values()->all();
        }

        // Map office roles with metadata for frontend
        $officeRolesData = $officeRoles->map(fn (OfficeRole $r): array => [
            'id'           => $r->id,
            'name'         => $r->name,
            'display_name' => $r->display_name,
            'description'  => $r->description,
            'is_system'    => $r->is_system,
        ])->values()->all();

        // ── Spatie roles with permissions & user counts ────────────────────
        $spatieRoles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r): array => [
                'id'          => $r->id,
                'name'        => $r->name,
                'permissions' => $r->permissions->pluck('name')->sort()->values()->all(),
                'users_count' => $r->users()->count(),
            ])
            ->values()
            ->all();

        // ── All permissions grouped by module prefix ───────────────────────
        $allPermissions = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->groupBy(fn (string $p): string => explode('.', $p)[0])
            ->map(fn ($perms): array => $perms->values()->all())
            ->toArray();

        return Inertia::render('super-admin/Users', [
            'users' => User::query()
                ->with('roles:id,name')
                ->latest()
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $u): array => [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'created_at' => $u->created_at?->toDateTimeString(),
                    'roles'      => $u->getRoleNames()->values()->all(),
                ])
                ->all(),
            'systemRoles'    => Role::query()->orderBy('name')->pluck('name')->all(),
            'officeRoles'    => $officeRolesData,
            'roleGroups'     => $roleGroups,
            'spatieRoles'    => $spatieRoles,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Update a user's primary system role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = $request->validated('role');

        $user->syncRoles([$role]);

        activity('user_management')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['assigned_role' => $role])
            ->log('super_admin.updated_user_role');

        return back()->with('success', "Role updated for {$user->name}.");
    }

    /**
     * Show form to create a new user
     */
    public function create()
    {
        $systemRoles = Role::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('super-admin/Users/Create', [
            'systemRoles' => $systemRoles,
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        activity('user_management')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['role' => $validated['role']])
            ->log('super_admin.created_user');

        return redirect()->route('super-admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Show form to edit a user
     */
    public function edit(User $user)
    {
        $systemRoles = Role::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('super-admin/Users/Edit', [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
            ],
            'systemRoles' => $systemRoles,
        ]);
    }

    /**
     * Update a user's basic information
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        activity('user_management')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['role' => $validated['role']])
            ->log('super_admin.updated_user');

        return redirect()->route('super-admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Delete a user
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $userName = $user->name;
        
        activity('user_management')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('super_admin.deleted_user');

        $user->delete();

        return back()->with('success', "User {$userName} deleted successfully.");
    }

    /**
     * Update a user's primary system role.
     */
    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        // Guard: super_admin must always keep all permissions (or you can remove this)
        if ($role->name === 'super_admin') {
            return back()->withErrors(['role' => 'Super Admin permissions cannot be modified here.']);
        }

        $validated = $request->validate([
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('user_management')
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['permissions' => $validated['permissions'] ?? []])
            ->log('super_admin.updated_role_permissions');

        return back()->with('success', "Permissions for \"{$role->name}\" updated.");
    }
}
