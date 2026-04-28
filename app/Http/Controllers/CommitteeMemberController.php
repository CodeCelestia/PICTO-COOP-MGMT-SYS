<?php

namespace App\Http\Controllers;

use App\Models\CommitteeMember;
use App\Models\Cooperative;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivityWithChanges;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeMemberController extends Controller
{
    use LogsActivityWithChanges;

    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->coop_id && ! $user->can('view-all-cooperatives'))
            : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->can('view-all-cooperatives') : false;
    }

    private function isOfficer(): bool
    {
        $user = auth()->user();

        return $user
            ? (! $user->can('view-all-cooperatives') && $user->can('read officers-&-committees'))
            : false;
    }

    private function enforceCoopScope(int $coopId): void
    {
        $user = auth()->user();

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id && $coopId !== $user->coop_id) {
            abort(403);
        }
    }

    /**
     * Display a listing of committee members.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = CommitteeMember::with(['member', 'cooperative']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $committeeMembers = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('CommitteeMembers/Index', [
            'committeeMembers' => $committeeMembers,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'coop_id', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new committee member.
     */
    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Committee Member');
            })
            ->orderBy('last_name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('CommitteeMembers/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created committee member.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'member_id' => ['required', 'exists:members,id'],
            'committee_name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:100'],
            'date_assigned' => ['nullable', 'date'],
            'date_removed' => ['nullable', 'date', 'after_or_equal:date_assigned'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope($validated['coop_id']);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $hasCommitteeRole = $member?->roles()
            ->where('name', 'Committee Member')
            ->exists() ?? false;

        if (!$hasCommitteeRole) {
            return back()->withErrors(['member_id' => 'Selected member does not have the Committee Member role.']);
        }

        $committeeMember = CommitteeMember::create($validated);

        $this->logDetailedActivity(
            'created',
            $committeeMember,
            [],
            $committeeMember->fresh()->getAttributes(),
            'Committee Members'
        );

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', 'Committee member added successfully.');
        }

        return redirect()->route('committee-members.index')
            ->with('success', 'Committee member added successfully.');
    }

    /**
     * Show the form for editing a committee member.
     */
    public function edit(CommitteeMember $committeeMember): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($committeeMember->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'Committee Member');
            })
            ->orderBy('last_name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('CommitteeMembers/Edit', [
            'committeeMember' => $committeeMember->load(['member', 'cooperative']),
            'cooperatives' => $cooperativesQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
        ]);
    }

    /**
     * Update a committee member.
     */
    public function update(Request $request, CommitteeMember $committeeMember): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($committeeMember->coop_id);

        $coopId = $user?->coop_id;
        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'member_id' => ['required', 'exists:members,id'],
            'committee_name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:100'],
            'date_assigned' => ['nullable', 'date'],
            'date_removed' => ['nullable', 'date', 'after_or_equal:date_assigned'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope($validated['coop_id']);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $hasCommitteeRole = $member?->roles()
            ->where('name', 'Committee Member')
            ->exists() ?? false;

        if (!$hasCommitteeRole) {
            return back()->withErrors(['member_id' => 'Selected member does not have the Committee Member role.']);
        }

        $oldValues = $committeeMember->getAttributes();
        $committeeMember->update($validated);

        $this->logDetailedActivity(
            'updated',
            $committeeMember,
            $oldValues,
            $committeeMember->fresh()->getAttributes(),
            'Committee Members'
        );

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', 'Committee member updated successfully.');
        }

        return redirect()->route('committee-members.index')
            ->with('success', 'Committee member updated successfully.');
    }

    /**
     * Remove a committee member.
     */
    public function destroy(CommitteeMember $committeeMember): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($committeeMember->coop_id);

        $oldValues = $committeeMember->getAttributes();
        $committeeMember->delete();

        $this->logDetailedActivity(
            'deleted',
            $committeeMember,
            $oldValues,
            [],
            'Committee Members'
        );

        return redirect()->back()
            ->with('success', 'Committee member removed successfully.');
    }
}
