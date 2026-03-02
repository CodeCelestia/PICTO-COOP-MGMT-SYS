<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->select([
                'office_user_roles.role_name',
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

        $officeRoles = OfficeUserRoleController::OFFICE_ROLES;
        $roleGroups  = [];
        foreach ($officeRoles as $role) {
            $roleGroups[$role] = ($rawGroups[$role] ?? collect())->values()->all();
        }

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
     * Update the permissions granted to a Spatie role.
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
