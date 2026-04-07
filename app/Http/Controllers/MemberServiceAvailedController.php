<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberServiceAvailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberServiceAvailedController extends Controller
{
    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Coop Admin') : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Provincial Admin') : false;
    }

    private function isOfficer(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Officer') : false;
    }

    private function ensureMemberScope(Member $member): void
    {
        $user = auth()->user();

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }
    }

    /**
     * Store a newly created service availment.
     */
    public function store(Request $request, Member $member): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->ensureMemberScope($member);

        $validated = $request->validate([
            'service_type' => ['required', Rule::in([
                'Loan',
                'Marketing',
                'Training',
                'Savings',
                'Insurance',
                'Technical Assistance',
                'Other',
            ])],
            'service_detail' => ['nullable', 'string', 'max:255'],
            'date_availed' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['Active', 'Completed', 'Pending', 'Cancelled'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['member_id'] = $member->id;
        $validated['coop_id'] = $member->coop_id;
        $validated['recorded_by'] = auth()->user()?->name;

        MemberServiceAvailed::create($validated);

        return back()->with('success', 'Service availment added successfully.');
    }

    /**
     * Update an existing service availment.
     */
    public function update(Request $request, Member $member, MemberServiceAvailed $service): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->ensureMemberScope($member);

        if ($service->member_id !== $member->id) {
            abort(404);
        }

        $validated = $request->validate([
            'service_type' => ['required', Rule::in([
                'Loan',
                'Marketing',
                'Training',
                'Savings',
                'Insurance',
                'Technical Assistance',
                'Other',
            ])],
            'service_detail' => ['nullable', 'string', 'max:255'],
            'date_availed' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['Active', 'Completed', 'Pending', 'Cancelled'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $service->update($validated);

        return back()->with('success', 'Service availment updated successfully.');
    }

    /**
     * Remove the specified service availment.
     */
    public function destroy(Member $member, MemberServiceAvailed $service): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->ensureMemberScope($member);

        if ($service->member_id !== $member->id) {
            abort(404);
        }

        $service->delete();

        return back()->with('success', 'Service availment deleted successfully.');
    }
}
