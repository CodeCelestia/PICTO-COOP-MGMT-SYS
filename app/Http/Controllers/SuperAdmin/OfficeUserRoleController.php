<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\OfficeRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfficeUserRoleController extends Controller
{
    /**
     * Show users in an office with their roles
     */
    public function index(Office $office)
    {
        // Get users with their roles in this office
        $usersInOffice = $office->users()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('office_role_id', 'assigned_by', 'assigned_at')
            ->with(['pivot.officeRole'])
            ->paginate(25);

        $availableRoles = OfficeRole::orderBy('display_name')->get();

        return Inertia::render('super-admin/Offices/Users', [
            'office' => $office,
            'users' => $usersInOffice,
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * Assign user to office with role
     */
    public function assignToOffice(Request $request, Office $office)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'office_role_id' => 'required|exists:office_roles,id',
        ]);

        $office->users()->syncWithoutDetaching([
            $validated['user_id'] => [
                'office_role_id' => $validated['office_role_id'],
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ],
        ]);

        return redirect()->back()->with('success', 'User assigned to office successfully.');
    }

    /**
     * Update user role in an office
     */
    public function updateRole(Request $request, Office $office, User $user)
    {
        $validated = $request->validate([
            'office_role_id' => 'required|exists:office_roles,id',
        ]);

        $office->users()->updateExistingPivot($user->id, [
            'office_role_id' => $validated['office_role_id'],
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return redirect()->back()->with('success', 'User role updated successfully.');
    }

    /**
     * Remove user from office
     */
    public function removeFromOffice(Office $office, User $user)
    {
        $office->users()->detach($user->id);

        return redirect()->back()->with('success', 'User removed from office successfully.');
    }
}
