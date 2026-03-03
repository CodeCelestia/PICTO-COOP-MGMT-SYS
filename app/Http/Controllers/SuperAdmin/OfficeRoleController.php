<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\OfficeRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OfficeRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = OfficeRole::orderBy('display_name')->paginate(20);

        return Inertia::render('super-admin/OfficeRoles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('super-admin/OfficeRoles/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:office_roles,name|alpha_dash',
            'display_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create office role
            OfficeRole::create($validated);

            // Create corresponding Spatie role if it doesn't exist
            if (!Role::where('name', $validated['name'])->exists()) {
                Role::create(['name' => $validated['name']]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });

        return redirect()->route('super-admin.office-roles.index')
            ->with('success', 'Office role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OfficeRole $officeRole)
    {
        // Get all assignments for this role
        $assignments = DB::table('office_user_roles')
            ->join('offices', 'offices.id', '=', 'office_user_roles.office_id')
            ->join('users', 'users.id', '=', 'office_user_roles.user_id')
            ->where('office_user_roles.office_role_id', $officeRole->id)
            ->select([
                'offices.id as office_id',
                'offices.name as office_name',
                DB::raw("COALESCE(offices.code, '') as office_code"),
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'office_user_roles.assigned_at',
            ])
            ->orderBy('offices.name')
            ->orderBy('users.name')
            ->get();

        return Inertia::render('super-admin/OfficeRoles/Show', [
            'role' => [
                'id'           => $officeRole->id,
                'name'         => $officeRole->name,
                'display_name' => $officeRole->display_name,
                'description'  => $officeRole->description,
                'is_system'    => $officeRole->is_system,
            ],
            'assignments' => $assignments,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OfficeRole $officeRole)
    {
        return Inertia::render('super-admin/OfficeRoles/Edit', [
            'role' => $officeRole,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OfficeRole $officeRole)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|alpha_dash|unique:office_roles,name,' . $officeRole->id,
            'display_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);

        DB::transaction(function () use ($officeRole, $validated) {
            $oldName = $officeRole->name;
            
            // Update office role
            $officeRole->update($validated);

            // Sync Spatie role name if changed
            if ($oldName !== $validated['name']) {
                /** @var Role|null $spatieRole */
                $spatieRole = Role::query()->where('name', $oldName)->first();
                if ($spatieRole) {
                    $spatieRole->update(['name' => $validated['name']]);
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                } else {
                    // Create if doesn't exist
                    Role::create(['name' => $validated['name']]);
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                }
            }
        });

        return redirect()->route('super-admin.office-roles.index')
            ->with('success', 'Office role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficeRole $officeRole)
    {
        if ($officeRole->is_system) {
            return redirect()->back()
                ->withErrors(['error' => 'System roles cannot be deleted.']);
        }

        DB::transaction(function () use ($officeRole) {
            $roleName = $officeRole->name;
            
            // Delete office role
            $officeRole->delete();

            // Delete corresponding Spatie role if it exists and is not a system role
            /** @var Role|null $spatieRole */
            $spatieRole = Role::query()->where('name', $roleName)->first();
            if ($spatieRole) {
                // Check if it's not assigned to any users
                if ($spatieRole->users()->count() === 0) {
                    $spatieRole->delete();
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                }
            }
        });

        return redirect()->route('super-admin.office-roles.index')
            ->with('success', 'Office role deleted successfully.');
    }
}
