<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;
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
        return Inertia::render('super-admin/Offices/Create');
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
        ]);

        Office::create($validated);

        return redirect()->route('super-admin.offices.index')->with('success', 'Office created successfully.');
    }

    /**
     * Show single office with members
     */
    public function show(Office $office)
    {
        $office->load(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email')
                ->withPivot('role_name', 'assigned_at');
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
