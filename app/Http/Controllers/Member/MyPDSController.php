<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyPDSController extends Controller
{
    /**
     * Show member's own PDS
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $pds = $user->personalDataSheet;

        if (!$pds) {
            return redirect()->route('member.dashboard')
                ->with('error', 'No PDS record found for your account.');
        }

        return Inertia::render('member/MyPDS/Show', [
            'pds' => $pds,
        ]);
    }

    /**
     * Show form to edit member's own PDS
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $pds = $user->personalDataSheet;

        if (!$pds) {
            return redirect()->route('member.dashboard')
                ->with('error', 'No PDS record found for your account.');
        }

        return Inertia::render('member/MyPDS/Edit', [
            'pds' => $pds,
        ]);
    }

    /**
     * Update member's own PDS
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pds = $user->personalDataSheet;

        if (!$pds) {
            return redirect()->route('member.dashboard')
                ->with('error', 'No PDS record found for your account.');
        }

        $validated = $request->validate([
            'phone_number'      => 'nullable|string|max:20',
            'email'             => 'required|email|unique:personal_data_sheets,email,' . $pds->id,
            // Residential
            'region_code'       => 'nullable|string|max:20',
            'region_name'       => 'nullable|string|max:255',
            'province_code'     => 'nullable|string|max:20',
            'province_name'     => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'     => 'nullable|string|max:20',
            'barangay_name'     => 'nullable|string|max:255',
            'street_address'    => 'nullable|string|max:255',
            'res_house'         => 'nullable|string|max:100',
            'res_subdivision'   => 'nullable|string|max:255',
            'res_zip'           => 'nullable|string|max:10',
            // Contact info
            'telephone_no'      => 'nullable|string|max:20',
            // Other allowed fields
            'civil_status'      => 'nullable|string|max:50',
            'height'            => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'blood_type'        => 'nullable|string|max:5',
        ]);

        $pds->update($validated);

        // Sync email to user account if changed
        if ($validated['email'] !== $user->email) {
            $user->update(['email' => $validated['email']]);
        }

        activity('member_pds')
            ->causedBy($user)
            ->performedOn($pds)
            ->log('member.updated_own_pds');

        return redirect()->route('member.my-pds.show')
            ->with('success', 'Your PDS has been updated successfully.');
    }
}
