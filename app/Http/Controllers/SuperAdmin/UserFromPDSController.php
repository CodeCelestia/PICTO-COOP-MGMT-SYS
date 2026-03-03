<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserFromPDSController extends Controller
{
    /**
     * Show form to create user from PDS
     */
    public function create(PersonalDataSheet $pds)
    {
        // Check if user already exists for this PDS
        if ($pds->user) {
            return redirect()->route('super-admin.pds.show', $pds)
                ->with('info', 'User account already exists for this PDS.');
        }

        // Get all available system roles
        $systemRoles = Role::orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        return Inertia::render('super-admin/PDS/CreateUser', [
            'pds' => $pds,
            'systemRoles' => $systemRoles,
        ]);
    }

    /**
     * Store user account created from PDS
     */
    public function store(Request $request, PersonalDataSheet $pds)
    {
        // Check if user already exists
        if ($pds->user) {
            return redirect()->route('super-admin.pds.index')
                ->with('error', 'User account already exists for this PDS.');
        }

        // Get valid roles from database
        $validRoles = Role::pluck('name')->toArray();

        $validated = $request->validate([
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:' . implode(',', $validRoles),
        ]);

        try {
            $user = User::create([
                'name'               => trim("{$pds->first_name} {$pds->last_name}"),
                'email'              => $validated['email'],
                'password'           => Hash::make($validated['password']),
                'pds_id'             => $pds->id,
                'email_verified_at'  => now(),
            ]);

            $user->assignRole($validated['role']);

            return redirect()->route('super-admin.pds.index')
                ->with('success', "Account created for {$pds->first_name} {$pds->last_name} with role: {$validated['role']}.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
