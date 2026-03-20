<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Coop Admin') || $user->account_type === 'Coop Admin')
            : false;
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
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'is_active' => $validated['is_active'],
        ]);

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
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'is_active' => $validated['is_active'],
        ]);

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
