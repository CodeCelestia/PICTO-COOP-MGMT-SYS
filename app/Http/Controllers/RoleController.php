<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

            if ($user->hasRole('Super Admin')) {
            return false;
        }

            return $user->hasRole('Coop Admin');
    }

    public function index(): Response
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        $roles = Role::with('permissions')
            ->orderBy('level')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'level' => $role->level,
                    'is_active' => (bool) $role->is_active,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ];
                    })->values(),
                ];
            });

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('RolesPermissions/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'is_active' => $validated['is_active'],
        ]);

        if (!empty($validated['permission_ids'])) {
            $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->back()->with('success', "Role '{$role->name}' created successfully!");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'is_active' => $validated['is_active'],
        ]);

        if (array_key_exists('permission_ids', $validated)) {
            $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->back()->with('success', "Role '{$role->name}' updated successfully!");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        if ($role->users()->exists()) {
            return redirect()->back()->with('error', "Role '{$role->name}' cannot be deleted because it is assigned to users.");
        }

        $role->delete();

        return redirect()->back()->with('success', "Role '{$role->name}' deleted successfully!");
    }
}
