<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfficeUserRoleController extends Controller
{
    const OFFICE_ROLES = [
        'member',
        'officer',
        'committee_member',
        'general_manager',
        'chairperson',
    ];

    /**
     * Show users in an office with their roles
     */
    public function index(Office $office)
    {
        // Get users with their roles in this office
        $usersInOffice = $office->users()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('role_name', 'assigned_by', 'assigned_at')
            ->paginate(25);

        return Inertia::render('super-admin/Offices/Users', [
            'office' => $office,
            'users' => $usersInOffice,
            'availableRoles' => self::OFFICE_ROLES,
        ]);
    }

    /**
     * Assign user to office with role
     */
    public function assignToOffice(Request $request, Office $office)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_name' => 'required|in:' . implode(',', self::OFFICE_ROLES),
        ]);

        $office->users()->syncWithoutDetaching([
            $validated['user_id'] => [
                'role_name' => $validated['role_name'],
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
            'role_name' => 'required|in:' . implode(',', self::OFFICE_ROLES),
        ]);

        $office->users()->updateExistingPivot($user->id, [
            'role_name' => $validated['role_name'],
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
