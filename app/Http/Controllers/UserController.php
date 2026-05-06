<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Cooperative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    private function authorizeUserManagement(string $permission): void
    {
        if (! auth()->user()?->can($permission)) {
            abort(403);
        }
    }

    private function resolveAccountType(?Role $role): string
    {
        return $role?->name ?? 'Member';
    }
    public function index()
    {
        $this->authorizeUserManagement('read user-accounts');

        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'account_type' => $user->account_type,
                'account_status' => $user->account_status,
                'coop_id' => $user->coop_id,
                'profile_photo' => $user->profile_photo,
                'last_login_at' => $user->last_login_at,
                'created_by' => $user->created_by,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'level' => $role->level,
                        'assigned_at' => $role->pivot->assigned_at,
                        'assigned_by' => $role->pivot->assigned_by,
                        'status' => $role->pivot->status,
                        'expires_at' => $role->pivot->expires_at,
                    ];
                }),
            ];
        });

        $roles = Role::orderBy('level')->get();
        $cooperatives = Cooperative::select('id', 'name', 'classification')->orderBy('name')->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'availableRoles' => $roles,
            'cooperatives' => $cooperatives,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeUserManagement('create user-accounts');

        $roleIds = $request->input('role_ids', []);
        $roles = Role::whereIn('id', $roleIds)->get();
        $requiresCoop = $roles->isNotEmpty()
            ? ! $roles->contains(fn (Role $role) => $role->hasPermissionTo('view-all-cooperatives'))
            : false;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'coop_id' => [$requiresCoop ? 'required' : 'nullable', 'exists:cooperatives,id'],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $firstRole = !empty($validated['role_ids'])
            ? Role::find($validated['role_ids'][0])
            : null;
        $accountType = $this->resolveAccountType($firstRole);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'coop_id' => $validated['coop_id'] ?? null,
            'account_type' => $accountType,
            'account_status' => 'Active', // Admin-created users are auto-approved
            'created_by' => auth()->user()->name,
            'password_changed_at' => now(),
        ]);

        // Assign roles if provided (using Spatie)
        if (!empty($validated['role_ids'])) {
            foreach ($validated['role_ids'] as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    // Spatie's assignRole only needs the role name or model
                    $user->assignRole($role);
                }
            }
        }

        return redirect()->back()->with('success', "User '{$user->name}' created successfully!");
    }

    public function assignRole(Request $request, User $user)
    {
        $this->authorizeUserManagement('assign roles');

        $roleId = $request->input('role_id');
        $role = Role::findOrFail($roleId);

        $requiresCoop = ! $role->hasPermissionTo('view-all-cooperatives');

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'coop_id' => [Rule::requiredIf($requiresCoop), 'nullable', 'exists:cooperatives,id'],
        ]);
        
        // Spatie's assignRole only needs the role
        $user->assignRole($role);

        $updates = [
            'account_type' => $role->name,
        ];

        if ($requiresCoop) {
            $updates['coop_id'] = $request->input('coop_id');
        }

        $user->update($updates);

        return redirect()->back()->with('success', "Role '{$role->name}' assigned successfully!");
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserManagement('update user-accounts');

        $requiresCoop = ! $user->can('view-all-cooperatives');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'account_status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended', 'Locked', 'Pending Approval'])],
            'coop_id' => [$requiresCoop ? 'required' : 'nullable', 'exists:cooperatives,id'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'account_status' => $validated['account_status'],
            'coop_id' => $validated['coop_id'] ?? null,
        ]);

        return redirect()->back()->with('success', "User '{$user->name}' updated successfully!");
    }

    public function destroy(User $user)
    {
        $this->authorizeUserManagement('delete user-accounts');

        if (auth()->id() === $user->id) {
            abort(403);
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully!');
    }

    public function removeRole(Request $request, User $user)
    {
        $this->authorizeUserManagement('assign roles');

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        
        // Spatie's removeRole only needs the role
        $user->removeRole($role);

        return redirect()->back()->with('success', "Role '{$role->name}' removed successfully!");
    }
}
