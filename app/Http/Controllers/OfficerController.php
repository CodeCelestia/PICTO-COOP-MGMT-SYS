<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Officer;
use App\Models\OfficerTermHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsActivityWithChanges;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OfficerController extends Controller
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
     * Display a listing of officers.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Officer::with(['member', 'cooperative']);

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

        $officers = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Officers/Index', [
            'officers' => $officers,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'coop_id', 'per_page']),
        ]);
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Officers & Committees',
            'description' => 'Select a cooperative to view officers and committees.',
            'targetUrl' => '/officers',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new officer.
     */
    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name', 'region', 'classification', 'status')->orderBy('name');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id', 'gender', 'date_joined', 'membership_status')
            ->with('roles')
            ->orderBy('last_name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Officers/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'gender' => $member->gender,
                    'date_joined' => optional($member->date_joined)->toDateString(),
                    'status' => $member->membership_status ?? 'Active',
                    'member_code' => null,
                    'role_names' => $member->roles->pluck('name')->values()->all(),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created officer.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validator = Validator::make($request->all(), [
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'member_id' => ['required', 'exists:members,id'],
            'position' => ['required', 'string', 'max:100'],
            'committee' => ['nullable', 'string', 'max:100'],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            'status' => ['required', Rule::in(['Active', 'Retired', 'Removed', 'Resigned'])],
            'reason_for_change' => [Rule::requiredIf(fn () => strtolower((string) $request->input('status', '')) !== 'active'), 'nullable', 'string', 'max:500'],
            'election_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $validated = $validator->validate();

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope($validated['coop_id']);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $reason = $validated['reason_for_change'] ?? null;
        $electionYear = $validated['election_year'] ?? null;
        unset($validated['reason_for_change'], $validated['election_year']);

        DB::transaction(function () use ($validated, $reason, $electionYear) {
            $officer = Officer::create($validated);
            $this->logTermHistory($officer, $validated, $reason ?: 'Initial appointment', $electionYear);

            $this->logDetailedActivity(
                'created',
                $officer,
                [],
                $officer->fresh()->getAttributes(),
                'Officers'
            );
        });

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', 'Officer created successfully.');
        }

        return redirect()->route('officers.index')
            ->with('success', 'Officer created successfully.');
    }

    /**
     * Store multiple officers in bulk.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;
        $officers = $request->input('officers', []);

        if (!is_array($officers) || count($officers) === 0) {
            return back()->withErrors(['officers' => 'At least one officer is required.']);
        }

        // Validate and process each officer
        $createdCount = 0;
        $errors = [];

        DB::transaction(function () use ($officers, $coopId, &$createdCount, &$errors) {
            foreach ($officers as $index => $officerData) {
                $validator = Validator::make($officerData, [
                    'coop_id' => $this->isCoopAdmin() && $coopId
                        ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                        : ['required', 'exists:cooperatives,id'],
                    'member_id' => ['required', 'exists:members,id'],
                    'position' => ['required', 'string', 'max:100'],
                    'committee' => ['nullable', 'string', 'max:100'],
                    'term_start' => ['nullable', 'date'],
                    'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
                    'status' => ['required', Rule::in(['Active', 'Retired', 'Removed', 'Resigned'])],
                    'reason_for_change' => [Rule::requiredIf(fn () => strtolower((string) ($officerData['status'] ?? '')) !== 'active'), 'nullable', 'string', 'max:500'],
                    'election_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
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

                $reason = $validated['reason_for_change'] ?? null;
                $electionYear = $validated['election_year'] ?? null;
                unset($validated['reason_for_change'], $validated['election_year']);

                $officer = Officer::create($validated);
                $this->logTermHistory($officer, $validated, $reason ?: 'Initial appointment', $electionYear);

                $this->logDetailedActivity(
                    'created',
                    $officer,
                    [],
                    $officer->fresh()->getAttributes(),
                    'Officers'
                );

                $createdCount++;
            }
        });

        if (count($errors) > 0) {
            return back()->withErrors(['officers' => 'Created ' . $createdCount . ' officer(s). Some entries had errors.']);
        }

        $returnTo = $this->resolveInternalReturnTo($request);
        $message = $createdCount === 1 ? 'Officer created successfully.' : $createdCount . ' officers created successfully.';

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', $message);
        }

        return redirect()->route('officers.index')->with('success', $message);
    }

    /**
     * Show the form for editing an officer.
     */
    public function edit(Officer $officer): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($officer->coop_id);

        $officer->load(['member', 'cooperative']);
        $latestHistory = $officer->termHistory()->latest('recorded_at')->first();

        $officer->setAttribute('reason_for_change', $latestHistory?->reason_for_change);
        $officer->setAttribute('election_year', $latestHistory?->election_year);

        return Inertia::render('Officers/Edit', [
            'officer' => $officer,
            'cooperative' => $officer->cooperative?->only([
                'id',
                'name',
                'region',
                'classification',
                'status',
            ]),
            'termHistory' => $officer->termHistory()
                ->latest('recorded_at')
                ->get()
                ->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'position' => $history->position,
                        'committee' => $history->committee,
                        'term_start' => optional($history->term_start)->toDateString(),
                        'term_end' => optional($history->term_end)->toDateString(),
                        'status' => $history->status,
                        'reason_for_change' => $history->reason_for_change,
                        'election_year' => $history->election_year,
                        'recorded_by' => $history->recorded_by,
                        'recorded_at' => optional($history->recorded_at)->toDateTimeString(),
                    ];
                }),
        ]);
    }

    /**
     * Update an officer.
     */
    public function update(Request $request, Officer $officer): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($officer->coop_id);

        $coopId = $user?->coop_id;
        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'position' => ['required', 'string', 'max:100'],
            'committee' => ['nullable', 'string', 'max:100'],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            'status' => ['required', Rule::in(['Active', 'Retired', 'Removed', 'Resigned'])],
            'reason_for_change' => [Rule::requiredIf(fn () => strtolower((string) $request->input('status', '')) !== 'active'), 'nullable', 'string', 'max:1000'],
            'election_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope($validated['coop_id']);

        $reason = $validated['reason_for_change'] ?? null;
        $electionYear = $validated['election_year'] ?? null;
        unset($validated['reason_for_change'], $validated['election_year']);

        $oldValues = $officer->getAttributes();

        $historySnapshot = array_intersect_key($validated, array_flip([
            'coop_id',
            'position',
            'committee',
            'term_start',
            'term_end',
            'status',
        ]));

        $hasChanges = false;
        foreach ($historySnapshot as $key => $value) {
            if ((string) ($officer->getOriginal($key) ?? '') !== (string) ($value ?? '')) {
                $hasChanges = true;
                break;
            }
        }

        DB::transaction(function () use ($officer, $validated, $historySnapshot, $hasChanges, $reason, $electionYear, $oldValues) {
            $officer->update($validated);

            if ($hasChanges) {
                $this->logTermHistory(
                    $officer,
                    $historySnapshot,
                    $reason ?: 'Updated officer term',
                    $electionYear
                );
            }

            $this->logDetailedActivity(
                'updated',
                $officer,
                $oldValues,
                $officer->fresh()->getAttributes(),
                'Officers'
            );
        });

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', 'Officer updated successfully.');
        }

        return redirect()->route('officers.index')
            ->with('success', 'Officer updated successfully.');
    }

    /**
     * Remove an officer.
     */
    public function destroy(Officer $officer): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($officer->coop_id);

        $oldValues = $officer->getAttributes();
        $officer->delete();

        $this->logDetailedActivity(
            'deleted',
            $officer,
            $oldValues,
            [],
            'Officers'
        );

        return redirect()->back()
            ->with('success', 'Officer deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $officer = Officer::withTrashed()->findOrFail($id);
        $this->enforceCoopScope($officer->coop_id);
        $officer->restore();

        return redirect()->route('officers.index')
            ->with('success', 'Officer restored successfully.');
    }

    private function logTermHistory(
        Officer $officer,
        array $payload,
        string $reason,
        ?int $electionYearOverride = null
    ): void
    {
        $termStart = $payload['term_start'] ?? null;
        $electionYear = $electionYearOverride;

        if ($electionYear === null && !empty($termStart)) {
            $electionYear = (int) date('Y', strtotime($termStart));
        }

        OfficerTermHistory::create([
            'officer_id' => $officer->id,
            'member_id' => $payload['member_id'] ?? $officer->member_id,
            'coop_id' => $payload['coop_id'] ?? $officer->coop_id,
            'position' => $payload['position'] ?? $officer->position,
            'committee' => $payload['committee'] ?? $officer->committee,
            'term_start' => $payload['term_start'] ?? $officer->term_start,
            'term_end' => $payload['term_end'] ?? $officer->term_end,
            'status' => $payload['status'] ?? $officer->status,
            'reason_for_change' => $reason,
            'election_year' => $electionYear,
            'recorded_by' => auth()->user()?->name,
            'recorded_at' => now(),
        ]);
    }
}
