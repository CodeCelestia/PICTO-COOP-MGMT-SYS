<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\OfficeRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class OfficeController extends Controller
{
    /**
     * Show list of all offices/cooperatives
     */
    public function index()
    {
        $offices = Office::withCount('users')->paginate(25);

        return Inertia::render('super-admin/Offices/Index', [
            'offices' => $offices,
        ]);
    }

    /**
     * Show form to create new office
     */
    public function create()
    {
        // Get all office roles for selection
        $officeRoles = OfficeRole::orderBy('display_name')->get(['id', 'name', 'display_name']);
        
        // Get system roles (Spatie roles) for selection
        $systemRoles = \Spatie\Permission\Models\Role::orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        return Inertia::render('super-admin/Offices/Create', [
            'officeRoles' => $officeRoles,
            'systemRoles' => $systemRoles,
        ]);
    }

    /**
     * Store office submission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'required|string|max:100|unique:offices,code',
            'cooperative_type'       => 'nullable|string|max:100',
            'registration_number'    => 'nullable|string|max:100',
            'date_registered'        => 'nullable|date',
            'asset_size'             => 'nullable|numeric|min:0',
            'classification'         => 'nullable|string|in:Micro,Small,Medium,Large,Billion',
            'status'                 => 'required|string|in:Active,Inactive,Under Rehabilitation',
            'key_services'           => 'nullable|array',
            'key_services.*'         => 'string|max:255',
            'year_of_latest_audit'   => 'nullable|integer|min:1900|max:2100',
            'chairperson'            => 'nullable|string|max:255',
            'general_manager'        => 'nullable|string|max:255',
            'region_code'            => 'nullable|string|max:20',
            'region_name'            => 'nullable|string|max:255',
            'province_code'          => 'nullable|string|max:20',
            'province_name'          => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'          => 'nullable|string|max:20',
            'barangay_name'          => 'nullable|string|max:255',
            'contact_email'          => 'nullable|email',
            'contact_phone'          => 'nullable|string|max:20',
            // Admin account fields
            'admin_name'             => 'required|string|max:255',
            'admin_email'            => 'required|email|unique:users,email',
            'admin_password'         => 'required|string|min:8|confirmed',            'admin_system_role'          => 'required|string|exists:roles,name',
            'admin_office_role_id'       => 'required|integer|exists:office_roles,id',        ]);

        DB::beginTransaction();

        try {
            // Create the office
            $office = Office::create([
                'name'                   => $validated['name'],
                'code'                   => $validated['code'],
                'cooperative_type'       => $validated['cooperative_type'] ?? null,
                'registration_number'    => $validated['registration_number'] ?? null,
                'date_registered'        => $validated['date_registered'] ?? null,
                'asset_size'             => $validated['asset_size'] ?? null,
                'classification'         => $validated['classification'] ?? null,
                'status'                 => $validated['status'],
                'key_services'           => $validated['key_services'] ?? null,
                'year_of_latest_audit'   => $validated['year_of_latest_audit'] ?? null,
                'chairperson'            => $validated['chairperson'] ?? null,
                'general_manager'        => $validated['general_manager'] ?? null,
                'region_code'            => $validated['region_code'] ?? null,
                'region_name'            => $validated['region_name'] ?? null,
                'province_code'          => $validated['province_code'] ?? null,
                'province_name'          => $validated['province_name'] ?? null,
                'city_municipality_code' => $validated['city_municipality_code'] ?? null,
                'city_municipality_name' => $validated['city_municipality_name'] ?? null,
                'barangay_code'          => $validated['barangay_code'] ?? null,
                'barangay_name'          => $validated['barangay_name'] ?? null,
                'contact_email'          => $validated['contact_email'] ?? null,
                'contact_phone'          => $validated['contact_phone'] ?? null,
            ]);

            // Create the admin user account
            $user = User::create([
                'name'     => $validated['admin_name'],
                'email'    => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
            ]);

            // Assign selected system role to the user
            $user->assignRole($validated['admin_system_role']);

            // Attach user to office with selected office role
            $office->users()->attach($user->id, [
                'office_role_id' => $validated['admin_office_role_id'],
                'assigned_by'    => auth()->id(),
                'assigned_at'    => now(),
            ]);

            DB::commit();

            return redirect()->route('super-admin.offices.index')
                ->with('success', 'Office created successfully with admin account.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create office: ' . $e->getMessage()]);
        }
    }

    /**
     * Show single office with members
     */
    public function show(Office $office)
    {
        $office->load(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email')
                ->withPivot('office_role_id', 'assigned_at');
        }]);

        return Inertia::render('super-admin/Offices/Show', [
            'office' => $office,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Office $office)
    {
        return Inertia::render('super-admin/Offices/Edit', [
            'office' => $office,
        ]);
    }

    /**
     * Update office
     */
    public function update(Request $request, Office $office)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'required|string|max:100|unique:offices,code,' . $office->id,
            'cooperative_type'       => 'nullable|string|max:100',
            'registration_number'    => 'nullable|string|max:100',
            'date_registered'        => 'nullable|date',
            'asset_size'             => 'nullable|numeric|min:0',
            'classification'         => 'nullable|string|in:Micro,Small,Medium,Large,Billion',
            'status'                 => 'required|string|in:Active,Inactive,Under Rehabilitation',
            'key_services'           => 'nullable|array',
            'key_services.*'         => 'string|max:255',
            'year_of_latest_audit'   => 'nullable|integer|min:1900|max:2100',
            'chairperson'            => 'nullable|string|max:255',
            'general_manager'        => 'nullable|string|max:255',
            'region_code'            => 'nullable|string|max:20',
            'region_name'            => 'nullable|string|max:255',
            'province_code'          => 'nullable|string|max:20',
            'province_name'          => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'          => 'nullable|string|max:20',
            'barangay_name'          => 'nullable|string|max:255',
            'contact_email'          => 'nullable|email',
            'contact_phone'          => 'nullable|string|max:20',
        ]);

        $office->update($validated);

        return redirect()->route('super-admin.offices.show', $office)->with('success', 'Office updated successfully.');
    }

    /**
     * Delete office
     */
    public function destroy(Office $office)
    {
        $office->delete();

        return redirect()->route('super-admin.offices.index')->with('success', 'Office deleted successfully.');
    }
}
