<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\CooperativeType;
use App\Models\CooperativeStatusHistory;
use App\Models\Member;
use App\Models\Officer;
use App\Models\CommitteeMember;
use App\Models\LoanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CooperativeController extends Controller
{
    private function canViewAllCooperatives(): bool
    {
        $user = auth()->user();

        return $user ? $user->can('view-all-cooperatives') : false;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Cooperative::query();

        if (!$this->canViewAllCooperatives() && $user?->coop_id) {
            $query->where('id', $user->coop_id);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->input('status') === 'Archived') {
            $query->onlyTrashed();
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Coop Type filter
        if ($request->filled('coop_type')) {
            $query->whereHas('types', function ($typeQuery) use ($request) {
                $typeQuery->where('name', $request->coop_type);
            });
        }

        // Geographical filters (Region -> Province -> Municipality)
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        if ($request->filled('municipality')) {
            $query->where('city_municipality', $request->municipality);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 500));

        $cooperatives = $query->with('types')->orderBy('name')->paginate($perPage)->withQueryString();

        $cooperatives->getCollection()->transform(function ($cooperative) {
            $latestAccreditation = $cooperative->accreditations()
                ->orderByDesc('date_granted')
                ->first(['id', 'cooperative_id', 'level', 'date_granted']);

            $cooperative->setAttribute('latest_accreditation', $latestAccreditation ? [
                'id' => $latestAccreditation->id,
                'cooperative_id' => $latestAccreditation->cooperative_id,
                'level' => $latestAccreditation->level,
                'date_granted' => optional($latestAccreditation->date_granted)->toDateString(),
            ] : null);

            return $cooperative;
        });

        return Inertia::render('Cooperatives/Index', [
            'cooperatives' => $cooperatives,
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'coop_type', 'region', 'province', 'municipality', 'per_page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Cooperatives/Create', [
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('create coop-master-profile')) {
            abort(403, 'You do not have permission to create cooperative profiles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives',
            'type_ids' => 'required|array|min:1',
            'type_ids.*' => 'integer|exists:cooperative_types,id',
            'classification' => 'nullable|in:micro,small,medium,large',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive,Dissolved,Suspended',
        ]);

        $typeIds = $validated['type_ids'];
        unset($validated['type_ids']);

        $cooperative = Cooperative::create($validated);
        $cooperative->types()->sync($typeIds);

        CooperativeStatusHistory::create([
            'coop_id' => $cooperative->id,
            'previous_status' => null,
            'new_status' => $cooperative->status,
            'change_reason' => 'Initial registration',
            'changed_by' => auth()->user()?->name ?? 'System',
            'changed_at' => now(),
            'remarks' => 'Initial cooperative status set during creation.',
        ]);

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative created successfully.');
    }

    public function edit(Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$this->canViewAllCooperatives() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Cooperatives/Edit', [
            'cooperative' => $cooperative->load('types'),
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'statusHistory' => $cooperative->statusHistory()
                ->latest('changed_at')
                ->get()
                ->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'previous_status' => $history->previous_status,
                        'new_status' => $history->new_status,
                        'change_reason' => $history->change_reason,
                        'changed_by' => $history->changed_by,
                        'changed_at' => optional($history->changed_at)->toDateTimeString(),
                        'remarks' => $history->remarks,
                    ];
                }),
        ]);
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$request->user()?->can('update coop-master-profile')) {
            abort(403, 'You do not have permission to update cooperative profiles.');
        }

        if (!$this->canViewAllCooperatives() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives,registration_number,' . $cooperative->id,
            'type_ids' => 'required|array|min:1',
            'type_ids.*' => 'integer|exists:cooperative_types,id',
            'classification' => 'nullable|in:micro,small,medium,large',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive,Dissolved,Suspended',
            'change_reason' => 'nullable|string|max:500',
            'status_remarks' => 'nullable|string|max:500',
        ]);

        if ($request->input('status') !== $cooperative->status) {
            $validator->sometimes('change_reason', ['required', 'string', 'max:500'], fn () => true);
        }

        $validated = $validator->validate();

        $typeIds = $validated['type_ids'];
        unset($validated['type_ids']);

        $previousStatus = $cooperative->status;
        $newStatus = $validated['status'];
        $changeReason = $validated['change_reason'] ?? null;
        $statusRemarks = $validated['status_remarks'] ?? null;
        unset($validated['change_reason'], $validated['status_remarks']);

        $cooperative->update($validated);
        $cooperative->types()->sync($typeIds);

        if ($previousStatus !== $newStatus) {
            CooperativeStatusHistory::create([
                'coop_id' => $cooperative->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'change_reason' => $changeReason,
                'changed_by' => auth()->user()?->name ?? 'System',
                'changed_at' => now(),
                'remarks' => $statusRemarks,
            ]);
        }

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative updated successfully.');
    }

    public function destroy(Cooperative $cooperative)
    {
        if (!auth()->user()?->can('delete coop-master-profile')) {
            abort(403, 'You do not have permission to delete cooperative profiles.');
        }

        $cooperative->delete();

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative deleted successfully.');
    }

    public function restore(int $id)
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $cooperative = Cooperative::withTrashed()->findOrFail($id);
        $cooperative->restore();

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative restored successfully.');
    }

    public function show(Request $request, ?Cooperative $cooperative = null)
    {
        $user = auth()->user();
        $canViewAll = $this->canViewAllCooperatives();

        if ($canViewAll) {
            if ($cooperative) {
                $cooperative = $cooperative->load(['types', 'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                }]);
            } elseif ($user?->coop_id) {
                $cooperative = Cooperative::with(['types', 'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                }])->findOrFail($user->coop_id);
            } else {
                return redirect()->route('cooperatives.index');
            }
        } else {
            if (!$user?->coop_id) {
                abort(404);
            }

            $cooperative = Cooperative::with(['types', 'accreditations' => function ($query) {
                $query->orderByDesc('date_granted');
            }])->findOrFail($user->coop_id);
        }

        $memberSearch = $request->input('members_search');
        $memberStatus = $request->input('members_membership_status');
        $memberPerPage = (int) $request->input('members_per_page', 15);
        $memberPerPage = max(1, min($memberPerPage, 500));

        $membersQuery = Member::with(['cooperative', 'user'])
            ->withCount([
                'officers as active_officers_count' => function ($q) {
                    $q->where('status', 'Active');
                },
            ])
            ->where('coop_id', $cooperative->id);

        if ($memberSearch) {
            $membersQuery->where(function ($q) use ($memberSearch) {
                $q->where('first_name', 'like', "%{$memberSearch}%")
                    ->orWhere('last_name', 'like', "%{$memberSearch}%")
                    ->orWhere('email', 'like', "%{$memberSearch}%");
            });
        }

        if ($memberStatus === 'Archived') {
            $membersQuery->onlyTrashed();
        } elseif ($memberStatus) {
            $membersQuery->where('membership_status', $memberStatus);
        }

        $members = $membersQuery->latest()->paginate($memberPerPage)->withQueryString();

        $officerSearch = $request->input('officers_search');
        $officerStatus = $request->input('officers_status');
        $officerPerPage = (int) $request->input('officers_per_page', 15);
        $officerPerPage = max(1, min($officerPerPage, 500));

        $officersQuery = Officer::with(['member', 'cooperative'])
            ->where('coop_id', $cooperative->id);

        if ($officerSearch) {
            $officersQuery->whereHas('member', function ($q) use ($officerSearch) {
                $q->where('first_name', 'like', "%{$officerSearch}%")
                    ->orWhere('last_name', 'like', "%{$officerSearch}%");
            });
        }

        if ($officerStatus === 'Archived') {
            $officersQuery->onlyTrashed();
        } elseif ($officerStatus) {
            $officersQuery->where('status', $officerStatus);
        }

        $officers = $officersQuery->latest()->paginate($officerPerPage)->withQueryString();

        $committeeSearch = $request->input('committees_search');
        $committeeStatus = $request->input('committees_status');
        $committeePerPage = (int) $request->input('committees_per_page', 15);
        $committeePerPage = max(1, min($committeePerPage, 500));

        $committeeQuery = CommitteeMember::with(['member', 'cooperative'])
            ->where('coop_id', $cooperative->id);

        if ($committeeSearch) {
            $committeeQuery->whereHas('member', function ($q) use ($committeeSearch) {
                $q->where('first_name', 'like', "%{$committeeSearch}%")
                    ->orWhere('last_name', 'like', "%{$committeeSearch}%");
            });
        }

        if ($committeeStatus) {
            $committeeQuery->where('status', $committeeStatus);
        }

        $committeeMembers = $committeeQuery->latest()->paginate($committeePerPage)->withQueryString();

        $cooperatives = Cooperative::select('id', 'name')
            ->where('id', $cooperative->id)
            ->orderBy('name')
            ->get();

        $loanTypes = LoanType::query()
            ->where('cooperative_id', $cooperative->id)
            ->orderBy('name')
            ->get(['id', 'cooperative_id', 'name', 'classification', 'description', 'is_active']);

        return Inertia::render('Cooperatives/Show', [
            'cooperative' => $cooperative,
            'members' => $members,
            'memberFilters' => [
                'search' => $memberSearch,
                'membership_status' => $memberStatus,
                'per_page' => $request->input('members_per_page'),
            ],
            'officers' => $officers,
            'officerFilters' => [
                'search' => $officerSearch,
                'coop_id' => $request->input('officers_coop_id', (string) $cooperative->id),
                'status' => $officerStatus,
                'per_page' => $request->input('officers_per_page'),
            ],
            'committeeMembers' => $committeeMembers,
            'committeeFilters' => [
                'search' => $committeeSearch,
                'coop_id' => $request->input('committees_coop_id', (string) $cooperative->id),
                'status' => $committeeStatus,
                'per_page' => $request->input('committees_per_page'),
            ],
            'cooperatives' => $cooperatives,
            'loanTypes' => $loanTypes,
            'loanTypePermissions' => [
                'can_create' => $user?->can('create finance-member-loans') ?? false,
                'can_edit' => $user?->can('update finance-member-loans') ?? false,
                'can_delete' => $user?->can('delete finance-member-loans') ?? false,
            ],
        ]);
    }

}
