<?php

namespace App\Http\Controllers\OfficeAdmin;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class OfficePDSController extends Controller
{
    /**
     * Display PDS records for the office admin's office
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        $pdsRecords = PersonalDataSheet::where('office_id', $office->id)
            ->with(['user.offices'])
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        // Get available system roles for account creation
        $systemRoles = Role::orderBy('name')
            ->get(['name'])
            ->mapWithKeys(fn ($role) => [$role->name => ucwords(str_replace('_', ' ', $role->name))])
            ->toArray();

        return Inertia::render('office-admin/PDS/Index', [
            'pdsRecords' => $pdsRecords,
            'systemRoles' => $systemRoles,
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
            ],
        ]);
    }

    /**
     * Show form to create new PDS
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        return Inertia::render('office-admin/PDS/Create', [
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
            ],
        ]);
    }

    /**
     * Store new PDS record
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'last_name'         => 'required|string|max:255',
            'name_extension'    => 'nullable|string|max:20',
            'date_of_birth'     => 'required|date',
            'place_of_birth'    => 'nullable|string|max:255',
            'gender'            => 'required|in:Male,Female,Other',
            'civil_status'      => 'nullable|string|max:50',
            'height'            => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'blood_type'        => 'nullable|string|max:5',
            'citizenship'       => 'required|string|max:255',
            'phone_number'      => 'nullable|string|max:20',
            'email'             => 'required|email|unique:personal_data_sheets,email',
            'region_code'       => 'nullable|string|max:20',
            'region_name'       => 'nullable|string|max:255',
            'province_code'     => 'nullable|string|max:20',
            'province_name'     => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'     => 'nullable|string|max:20',
            'barangay_name'     => 'nullable|string|max:255',
            'street_address'    => 'nullable|string|max:255',
        ]);

        $validated['office_id'] = $office->id;

        $pds = PersonalDataSheet::create($validated);

        activity('office_pds')
            ->causedBy($user)
            ->performedOn($pds)
            ->log('office_admin.created_pds');

        return redirect()->route('office-admin.pds.index')
            ->with('success', 'PDS record created successfully.');
    }

    /**
     * Display specific PDS record
     */
    public function show(Request $request, PersonalDataSheet $pd): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office || $pd->office_id !== $office->id) {
            return redirect()->route('office-admin.pds.index')
                ->with('error', 'You do not have access to this PDS record.');
        }

        $pd->load('user');
        return Inertia::render('office-admin/PDS/Show', ['pds' => $pd]);
    }

    /**
     * Show form to edit PDS
     */
    public function edit(Request $request, PersonalDataSheet $pd): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office || $pd->office_id !== $office->id) {
            return redirect()->route('office-admin.pds.index')
                ->with('error', 'You do not have access to this PDS record.');
        }

        return Inertia::render('office-admin/PDS/Edit', ['pds' => $pd]);
    }

    /**
     * Update PDS record
     */
    public function update(Request $request, PersonalDataSheet $pd): RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office || $pd->office_id !== $office->id) {
            return redirect()->route('office-admin.pds.index')
                ->with('error', 'You do not have access to this PDS record.');
        }

        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'last_name'         => 'required|string|max:255',
            'name_extension'    => 'nullable|string|max:20',
            'date_of_birth'     => 'required|date',
            'place_of_birth'    => 'nullable|string|max:255',
            'gender'            => 'required|in:Male,Female,Other',
            'civil_status'      => 'nullable|string|max:50',
            'height'            => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'blood_type'        => 'nullable|string|max:5',
            'citizenship'       => 'required|string|max:255',
            'phone_number'      => 'nullable|string|max:20',
            'email'             => 'required|email|unique:personal_data_sheets,email,' . $pd->id,
            'region_code'       => 'nullable|string|max:20',
            'region_name'       => 'nullable|string|max:255',
            'province_code'     => 'nullable|string|max:20',
            'province_name'     => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'     => 'nullable|string|max:20',
            'barangay_name'     => 'nullable|string|max:255',
            'street_address'    => 'nullable|string|max:255',
        ]);

        $pd->update($validated);

        activity('office_pds')
            ->causedBy($user)
            ->performedOn($pd)
            ->log('office_admin.updated_pds');

        return redirect()->route('office-admin.pds.index')
            ->with('success', 'PDS record updated successfully.');
    }

    /**
     * Delete PDS record
     */
    public function destroy(Request $request, PersonalDataSheet $pd): RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office || $pd->office_id !== $office->id) {
            return redirect()->route('office-admin.pds.index')
                ->with('error', 'You do not have access to this PDS record.');
        }

        $pd->delete();

        activity('office_pds')
            ->causedBy($user)
            ->performedOn($pd)
            ->log('office_admin.deleted_pds');

        return redirect()->route('office-admin.pds.index')
            ->with('success', 'PDS record deleted successfully.');
    }

    /**
     * Store user account created from PDS
     */
    public function createUser(Request $request, PersonalDataSheet $pd): RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        // Verify PDS belongs to office admin's office
        if (!$office || $pd->office_id !== $office->id) {
            return redirect()->route('office-admin.pds.index')
                ->with('error', 'You do not have access to this PDS record.');
        }

        // Check if user already exists
        if ($pd->user) {
            return redirect()->route('office-admin.pds.index')
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
            $newUser = User::create([
                'name'               => trim("{$pd->first_name} {$pd->last_name}"),
                'email'              => $validated['email'],
                'password'           => Hash::make($validated['password']),
                'pds_id'             => $pd->id,
                'email_verified_at'  => now(),
            ]);

            $newUser->assignRole($validated['role']);

            activity('office_user')
                ->causedBy($user)
                ->performedOn($newUser)
                ->log('office_admin.created_user_from_pds');

            return redirect()->route('office-admin.pds.index')
                ->with('success', "Account created for {$pd->first_name} {$pd->last_name} with role: {$validated['role']}.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
