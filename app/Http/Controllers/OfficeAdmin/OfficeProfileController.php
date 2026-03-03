<?php

namespace App\Http\Controllers\OfficeAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeProfileController extends Controller
{
    /**
     * Show office profile
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        return Inertia::render('office-admin/Profile/Show', [
            'office' => $office,
        ]);
    }

    /**
     * Show form to edit office profile
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        return Inertia::render('office-admin/Profile/Edit', [
            'office' => $office,
        ]);
    }

    /**
     * Update office profile
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $office = $user->offices()->first();

        if (!$office) {
            return redirect()->route('office-admin.dashboard')
                ->with('error', 'No office found for your account.');
        }

        $validated = $request->validate([
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'chairperson' => 'nullable|string|max:255',
            'general_manager' => 'nullable|string|max:255',
            'key_services' => 'nullable|string',
        ]);

        $office->update($validated);

        activity('office_profile')
            ->causedBy($user)
            ->performedOn($office)
            ->log('office_admin.updated_profile');

        return redirect()->route('office-admin.profile.show')
            ->with('success', 'Office profile updated successfully.');
    }
}
