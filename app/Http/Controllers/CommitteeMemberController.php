<?php

namespace App\Http\Controllers;

use App\Models\CommitteeMember;
use App\Models\Cooperative;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
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
        $returnTo = request()->query('return_to', '');
        $resolvedCoopId = (int) (request()->integer('coop_id') ?: ($user?->coop_id ?: 0));

        if ($resolvedCoopId === 0 && is_string($returnTo) && preg_match('#/cooperatives/(\d+)#', $returnTo, $matches)) {
            $resolvedCoopId = (int) $matches[1];
        }

        if ($resolvedCoopId === 0 && is_string($returnTo)) {
            $queryString = parse_url($returnTo, PHP_URL_QUERY) ?: '';
            parse_str($queryString, $returnQuery);

            if (!empty($returnQuery['coop_id'])) {
                $resolvedCoopId = (int) $returnQuery['coop_id'];
            }
        }

        if ($resolvedCoopId === 0) {
            $resolvedCoopId = (int) (Cooperative::query()->orderBy('name')->value('id') ?? 0);
        }

        $cooperative = $resolvedCoopId > 0 ? Cooperative::select('id', 'name', 'region', 'classification', 'status')->find($resolvedCoopId) : null;
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id', 'gender', 'date_joined', 'membership_status')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Committee Member');
            })
            ->with('roles')
            ->orderBy('last_name');

        if ($resolvedCoopId > 0) {
            $membersQuery->where('coop_id', $resolvedCoopId);
        }

        return Inertia::render('CommitteeMembers/Create', [
            'cooperative' => $cooperative,
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                    'gender' => $member->gender,
                    'date_joined' => optional($member->date_joined)->toDateString(),
                    'status' => $member->membership_status ?? 'Active',
                    'member_code' => null,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'role_names' => $member->roles->pluck('name')->values()->all(),
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

        $returnTo = $request->query('return_to', '');
        $resolvedCoopId = $request->integer('coop_id') ?: $coopId;
        if (!$resolvedCoopId && is_string($returnTo) && preg_match('#/cooperatives/(\d+)#', $returnTo, $matches)) {
            $resolvedCoopId = (int) $matches[1];
        }

        $validated = $request->validate([
            'coop_id' => $resolvedCoopId
                ? ['required', 'exists:cooperatives,id', Rule::in([(int) $resolvedCoopId])]
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
        } elseif ($resolvedCoopId) {
            $validated['coop_id'] = (int) $resolvedCoopId;
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
     * Store multiple committee members in bulk.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;
        $items = $request->input('committee_members', []);

        if (!is_array($items) || count($items) === 0) {
            return back()->withErrors(['committee_members' => 'At least one committee member is required.']);
        }

        $createdCount = 0;
        $errors = [];

        DB::transaction(function () use ($items, $coopId, &$createdCount, &$errors) {
            foreach ($items as $index => $data) {
                $validator = validator($data, [
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

                if ($validator->fails()) {
                    $errors[$index] = $validator->errors()->first();
                    continue;
                }

                $validated = $validator->validate();

                if ($this->isCoopAdmin() && $coopId) {
                    $validated['coop_id'] = $coopId;
                }

                $this->enforceCoopScope($validated['coop_id']);

                $member = Member::find($validated['member_id']);
                if ($member && $member->coop_id !== (int) $validated['coop_id']) {
                    $errors[$index] = 'Selected member does not belong to this cooperative.';
                    continue;
                }

                $hasCommitteeRole = $member?->roles()
                    ->where('name', 'Committee Member')
                    ->exists() ?? false;

                if (!$hasCommitteeRole) {
                    $errors[$index] = 'Selected member does not have the Committee Member role.';
                    continue;
                }

                $committeeMember = CommitteeMember::create([
                    'coop_id' => $validated['coop_id'],
                    'member_id' => $validated['member_id'],
                    'committee_name' => $validated['committee_name'],
                    'role' => $validated['role'] ?? null,
                    'date_assigned' => $validated['date_assigned'] ?? null,
                    'date_removed' => $validated['date_removed'] ?? null,
                    'status' => $validated['status'],
                ]);

                $this->logDetailedActivity(
                    'created',
                    $committeeMember,
                    [],
                    $committeeMember->fresh()->getAttributes(),
                    'Committee Members'
                );

                $createdCount++;
            }
        });

        if (count($errors) > 0) {
            return back()->withErrors(['committee_members' => 'Created ' . $createdCount . ' committee member(s). Some entries had errors.']);
        }

        $returnTo = $this->resolveInternalReturnTo($request);
        $message = $createdCount === 1 ? 'Committee member created successfully.' : $createdCount . ' committee members created successfully.';

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', $message);
        }

        return redirect()->route('committee-members.index')->with('success', $message);
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

        $committeeMember->load(['member', 'cooperative']);

        return Inertia::render('CommitteeMembers/Edit', [
            'committeeMember' => $committeeMember,
            'cooperative' => $committeeMember->cooperative?->only([
                'id',
                'name',
                'region',
                'classification',
                'status',
            ]),
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
            'committee_name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:100'],
            'date_assigned' => ['nullable', 'date'],
            'date_removed' => ['nullable', 'date', 'after_or_equal:date_assigned'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

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
