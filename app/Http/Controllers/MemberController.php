<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Activity;
use App\Models\Training;
use App\Models\Cooperative;
use App\Models\PdsSubmission;
use App\Models\Role;
use App\Models\MemberSectorHistory;
use App\Models\MemberServiceAvailed;
use App\Models\ActivityParticipant;
use App\Models\User;
use App\Traits\LogsActivityWithChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    use LogsActivityWithChanges;

    private function cooperativeSelectionData(Request $request): array
    {
        $query = Cooperative::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('coop_type')) {
            $query->where('coop_type', $request->coop_type);
        }

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

        return [
            'cooperatives' => $query->with('types')->orderBy('name')->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'coop_type', 'region', 'province', 'municipality', 'per_page']),
        ];
    }

    private function canViewAllCooperatives(): bool
    {
        $user = auth()->user();

        return $user ? $user->can('view-all-cooperatives') : false;
    }

    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->coop_id && ! $this->canViewAllCooperatives())
            : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $this->canViewAllCooperatives() : false;
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Super Admin') : false;
    }

    private function isOfficer(): bool
    {
        $user = auth()->user();

        return $user
            ? (! $this->canViewAllCooperatives() && $user->can('read officers-&-committees'))
            : false;
    }

    private function resolveAccountType(?Role $role): string
    {
        return $role?->name ?? 'Member';
    }

    /**
     * Resolve member roles that should be mirrored to the linked user account.
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function resolveMemberAccountRoles(Member $member)
    {
        $assignableRoleIds = $this->assignableMemberRoleIds();

        $roles = $member->roles()
            ->whereIn('roles.id', $assignableRoleIds)
            ->get();

        if ($roles->isNotEmpty()) {
            return $roles;
        }

        $defaultRole = Role::where('name', 'Member')->first();

        return $defaultRole ? collect([$defaultRole]) : collect();
    }

    private function syncUserRolesFromMember(User $user, Member $member): void
    {
        $roles = $this->resolveMemberAccountRoles($member);

        if ($roles->isNotEmpty()) {
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        $primaryRole = $roles->sortBy('level')->first();

        $user->update([
            'account_type' => $this->resolveAccountType($primaryRole),
        ]);
    }

    /**
     * Roles reserved for higher-level system users and not assignable in member account flow.
     *
     * @return array<int, string>
     */
    private function reservedSystemRoleNames(): array
    {
        return ['Super Admin', 'Provincial Admin', 'Coop Admin'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    private function assignableMemberRoles()
    {
        return Role::whereNotIn('name', $this->reservedSystemRoleNames())
            ->orderBy('level')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function assignableMemberRoleIds(): array
    {
        return Role::whereNotIn('name', $this->reservedSystemRoleNames())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function sanitizeInternalReturnPath(?string $path): ?string
    {
        if (!is_string($path) || $path === '') {
            return null;
        }

        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path;
    }

    private function resolveCooperativeContextRedirect(Request $request, ?int $fallbackCoopId = null): string
    {
        $safeReturnPath = $this->sanitizeInternalReturnPath($request->input('return_to'));
        if ($safeReturnPath) {
            return $safeReturnPath;
        }

        $coopId = (int) ($request->input('coop_id') ?: $fallbackCoopId ?: 0);
        if ($coopId > 0) {
            return "/members/management/{$coopId}?tab=members";
        }

        return route('dashboard');
    }

    private function shouldUseCooperativeContextRedirect(Request $request): bool
    {
        return $request->input('context') === 'cooperative'
            || $request->filled('return_to');
    }

    /**
     * Display a listing of members
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Member::with('cooperative')
            ->withCount([
                'officers as active_officers_count' => function ($q) {
                    $q->where('status', 'Active');
                },
            ]);

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by cooperative
        if ($request->filled('coop_id')) {
            if (!$this->isCoopAdmin()) {
                $query->where('coop_id', $request->coop_id);
            }
        }

        // Filter by sector
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        // Filter by membership status
        if ($request->filled('membership_status')) {
            $query->where('membership_status', $request->membership_status);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $members = $query->with(['cooperative', 'user', 'roles'])->latest()->paginate($perPage)->withQueryString();

        $userIds = $members->pluck('user.id')->filter()->all();
        $latestPdsByUserId = PdsSubmission::whereIn('user_id', $userIds)
            ->latest('updated_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $members->getCollection()->transform(function ($member) use ($latestPdsByUserId) {
            $pdsStatus = 'None';

            if ($member->user?->id && isset($latestPdsByUserId[$member->user->id])) {
                $pdsStatus = ucfirst($latestPdsByUserId[$member->user->id]->status);
            }

            $member->pds_status = $pdsStatus;

            return $member;
        });

        return Inertia::render('Members/Index', [
            'members' => $members,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'membership_status', 'per_page']),
        ]);
    }

    /**
     * Display the cooperative members management view.
     */
    public function management(Request $request, ?Cooperative $cooperative = null): Response|RedirectResponse
    {
        $user = auth()->user();
        $canViewAll = $this->canViewAllCooperatives();

        if ($this->isCoopAdmin()) {
            if (!$user?->coop_id) {
                abort(403);
            }

            $coopId = $user->coop_id;
        } else {
            if (!$canViewAll) {
                abort(403);
            }

            if (!$cooperative) {
                return Inertia::render('Members/ManagementSelect', $this->cooperativeSelectionData($request));
            }

            $coopId = $cooperative->id;
        }

        $memberSearch = $request->input('members_search');
        $memberStatus = $request->input('members_membership_status');
        $memberType = $request->input('members_membership_type');
        $memberPerPage = (int) $request->input('members_per_page', 10);
        $memberPerPage = max(1, min($memberPerPage, 500));

        $membersQuery = Member::with(['cooperative', 'user', 'roles'])
            ->withCount([
                'officers as active_officers_count' => function ($q) {
                    $q->where('status', 'Active');
                },
            ])
            ->where('coop_id', $coopId);

        if ($memberSearch) {
            $membersQuery->where(function ($q) use ($memberSearch) {
                $q->where('first_name', 'like', "%{$memberSearch}%")
                    ->orWhere('last_name', 'like', "%{$memberSearch}%")
                    ->orWhere('email', 'like', "%{$memberSearch}%");
            });
        }

        if ($memberStatus) {
            $membersQuery->where('membership_status', $memberStatus);
        }

        if ($memberType) {
            $membersQuery->where('membership_type', $memberType);
        }

        $members = $membersQuery->orderBy('last_name')->orderBy('first_name')->paginate($memberPerPage)->withQueryString();

        $membershipTypes = Member::query()
            ->where('coop_id', $coopId)
            ->whereNotNull('membership_type')
            ->distinct()
            ->orderBy('membership_type')
            ->pluck('membership_type')
            ->values();

        $services = MemberServiceAvailed::with(['member:id,first_name,last_name'])
            ->where('coop_id', $coopId)
            ->latest('date_availed')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'service_detail' => $service->service_detail,
                    'date_availed' => optional($service->date_availed)->toDateString(),
                    'amount' => $service->amount,
                    'status' => $service->status,
                    'reference_no' => $service->reference_no,
                    'remarks' => $service->remarks,
                    'recorded_by' => $service->recorded_by,
                    'member' => $service->member ? [
                        'id' => $service->member->id,
                        'full_name' => $service->member->full_name,
                    ] : null,
                ];
            });

        $activitySearch = $request->input('activities_search');
        $activityStatus = $request->input('activities_status');
        $activityCategory = $request->input('activities_category');
        $activityPerPage = (int) $request->input('activities_per_page', 15);
        $activityPerPage = max(1, min($activityPerPage, 500));

        $activitiesQuery = Activity::with(['cooperative', 'responsibleOfficer.member'])
            ->where('coop_id', $coopId);

        if ($activitySearch) {
            $activitiesQuery->where(function ($q) use ($activitySearch) {
                $q->where('title', 'like', "%{$activitySearch}%")
                    ->orWhere('description', 'like', "%{$activitySearch}%")
                    ->orWhere('funding_source', 'like', "%{$activitySearch}%")
                    ->orWhere('implementing_partner', 'like', "%{$activitySearch}%");
            });
        }

        if ($activityStatus) {
            $activitiesQuery->where('status', $activityStatus);
        }

        if ($activityCategory) {
            $activitiesQuery->where('category', $activityCategory);
        }

        $activities = $activitiesQuery->latest()->paginate($activityPerPage)->withQueryString();

        $trainingSearch = $request->input('trainings_search');
        $trainingStatus = $request->input('trainings_status');
        $trainingTargetGroup = $request->input('trainings_target_group');
        $trainingPerPage = (int) $request->input('trainings_per_page', 15);
        $trainingPerPage = max(1, min($trainingPerPage, 500));

        $trainingsQuery = Training::with('cooperative')
            ->where('coop_id', $coopId);

        if ($trainingSearch) {
            $trainingsQuery->where(function ($q) use ($trainingSearch) {
                $q->where('title', 'like', "%{$trainingSearch}%")
                    ->orWhere('facilitator', 'like', "%{$trainingSearch}%")
                    ->orWhere('venue', 'like', "%{$trainingSearch}%");
            });
        }

        if ($trainingStatus) {
            $trainingsQuery->where('status', $trainingStatus);
        }

        if ($trainingTargetGroup) {
            $trainingsQuery->where('target_group', $trainingTargetGroup);
        }

        $trainings = $trainingsQuery->latest()->paginate($trainingPerPage)->withQueryString();

        $cooperatives = Cooperative::select('id', 'name')
            ->where('id', $coopId)
            ->orderBy('name')
            ->get();

        return Inertia::render('Members/Management', [
            'members' => $members,
            'memberFilters' => [
                'search' => $memberSearch,
                'membership_status' => $memberStatus,
                'membership_type' => $memberType,
                'per_page' => $request->input('members_per_page'),
            ],
            'membershipTypes' => $membershipTypes,
            'services' => $services,
            'activities' => $activities,
            'activityFilters' => [
                'search' => $activitySearch,
                'status' => $activityStatus,
                'category' => $activityCategory,
                'coop_id' => (string) $coopId,
                'per_page' => $request->input('activities_per_page'),
            ],
            'trainings' => $trainings,
            'trainingFilters' => [
                'search' => $trainingSearch,
                'status' => $trainingStatus,
                'target_group' => $trainingTargetGroup,
                'coop_id' => (string) $coopId,
                'per_page' => $request->input('trainings_per_page'),
            ],
            'cooperatives' => $cooperatives,
        ]);
    }

    /**
     * Show cooperative picker for members management.
     */
    public function managementSelect(): RedirectResponse
    {
        if (!$this->canViewAllCooperatives()) {
            abort(403);
        }

        return redirect()->route('members.management');
    }

    /**
     * Show the form for creating a new member
     */
    public function create(): Response
    {
        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('Members/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'availableRoles' => $this->assignableMemberRoles(),
            'canCreateUserAccounts' => (bool) auth()->user()?->can('create user-accounts'),
        ]);
    }

    /**
     * Display the specified member
     */
    public function show(Member $member): Response
    {
        $user = auth()->user();

        if (!$user?->can('read members-profile')) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isOfficer() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $userAccount = User::with('roles')->where('member_id', $member->id)->first();

        $services = $member->servicesAvailed()
            ->latest('date_availed')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'service_detail' => $service->service_detail,
                    'date_availed' => optional($service->date_availed)->toDateString(),
                    'amount' => $service->amount,
                    'status' => $service->status,
                    'reference_no' => $service->reference_no,
                    'remarks' => $service->remarks,
                    'recorded_by' => $service->recorded_by,
                ];
            });

        $activities = $member->activityParticipants()
            ->with('activity')
            ->latest('date_joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'title' => $participant->activity?->title,
                    'category' => $participant->activity?->category,
                    'date_started' => optional($participant->activity?->date_started)->toDateString(),
                    'date_ended' => optional($participant->activity?->date_ended)->toDateString(),
                    'role' => $participant->role,
                    'is_beneficiary' => (bool) $participant->is_beneficiary,
                ];
            });

        $trainings = $member->trainingParticipants()
            ->with('training')
            ->latest('certificate_date')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'title' => $participant->training?->title,
                    'category' => $participant->training?->target_group ?? $participant->training?->skills_targeted,
                    'date_from' => optional($participant->training?->date_conducted)->toDateString(),
                    'date_to' => optional($participant->training?->date_conducted)->toDateString(),
                    'venue' => $participant->training?->venue,
                    'status' => $participant->outcome,
                ];
            });

        return Inertia::render('Members/Show', [
            'member' => $member->load(['cooperative', 'roles'])->loadCount([
                'officers as active_officers_count' => function ($q) {
                    $q->where('status', 'Active');
                },
            ]),
            'userAccount' => $userAccount ? [
                'email' => $userAccount->email,
                'roles' => $userAccount->roles->pluck('name')->toArray(),
            ] : null,
            'services' => $services,
            'activities' => $activities,
            'trainings' => $trainings,
        ]);
    }

    /**
     * Store a newly created member in storage
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if (!$request->user()?->can('create members-profile')) {
            abort(403, 'You do not have permission to create members.');
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'address' => ['required', 'string'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city_municipality' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100', 'unique:members,email'],
            'date_joined' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'in:Regular,Associate,Honorary'],
            'membership_status' => ['nullable', 'in:Active,Suspended,Resigned,Deceased'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'educational_attainment' => ['nullable', 'in:Elementary,High School,Vocational,College,Post-Graduate,None'],
            'primary_livelihood' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'in:Farmer,Fishfolk,Employee,Entrepreneur,Youth,Women,Senior Citizen,PWD,Other'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::in($this->assignableMemberRoleIds())],
        ]);

        if (!empty($validated['role_ids']) && !$request->user()?->can('assign roles')) {
            abort(403, 'You do not have permission to assign roles.');
        }

        if (!isset($validated['share_capital']) || $validated['share_capital'] === '') {
            $validated['share_capital'] = 0;
        }

        if (empty($validated['date_joined'])) {
            $validated['date_joined'] = now()->toDateString();
        }

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $member = Member::create($validated);
        $member->roles()->sync($validated['role_ids'] ?? []);

        $this->logDetailedActivity(
            'created',
            $member,
            [],
            $member->fresh()->getAttributes(),
            'Members'
        );

        if ($this->shouldUseCooperativeContextRedirect($request)) {
            return redirect()->to($this->resolveCooperativeContextRedirect($request, (int) $member->coop_id))
                ->with('success', 'Member created successfully.');
        }

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    /**
     * Show the form for editing the specified member
     */
    public function edit(Member $member): Response
    {
        $user = auth()->user();

        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isOfficer() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $userAccount = User::with('roles')
            ->where('member_id', $member->id)
            ->first();

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        $servicesAvailed = $member->servicesAvailed()
            ->latest('date_availed')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'service_detail' => $service->service_detail,
                    'date_availed' => optional($service->date_availed)->toDateString(),
                    'amount' => $service->amount,
                    'status' => $service->status,
                    'reference_no' => $service->reference_no,
                    'remarks' => $service->remarks,
                    'recorded_by' => $service->recorded_by,
                ];
            });

        $sectorHistory = $member->sectorHistory()
            ->latest('changed_at')
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'previous_sector' => $history->previous_sector,
                    'new_sector' => $history->new_sector,
                    'previous_livelihood' => $history->previous_livelihood,
                    'new_livelihood' => $history->new_livelihood,
                    'change_reason' => $history->change_reason,
                    'changed_by' => $history->changed_by,
                    'changed_at' => optional($history->changed_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('Members/Edit', [
            'member' => $member->load('cooperative'),
            'cooperatives' => $cooperativesQuery->get(),
            'availableRoles' => $this->assignableMemberRoles(),
            'userAccount' => $userAccount ? [
                'id' => $userAccount->id,
                'email' => $userAccount->email,
                'roles' => $userAccount->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'level' => $role->level,
                    ];
                }),
            ] : null,
            'servicesAvailed' => $servicesAvailed,
            'sectorHistory' => $sectorHistory,
        ]);
    }

    /**
     * Show the services availed for a member.
     */
    public function servicesAvailed(Member $member): Response
    {
        $user = auth()->user();

        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isOfficer() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $services = MemberServiceAvailed::query()
            ->where('member_id', $member->id)
            ->latest('date_availed')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'service_detail' => $service->service_detail,
                    'date_availed' => optional($service->date_availed)->toDateString(),
                    'amount' => $service->amount,
                    'status' => $service->status,
                    'reference_no' => $service->reference_no,
                    'remarks' => $service->remarks,
                    'recorded_by' => $service->recorded_by,
                ];
            });

        return Inertia::render('Members/ServicesAvailed', [
            'member' => $member->load('cooperative'),
            'services' => $services,
        ]);
    }

    /**
     * Show activity participation for a member.
     */
    public function activityParticipants(Member $member): Response
    {
        $user = auth()->user();

        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isOfficer() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $participants = ActivityParticipant::with(['activity.cooperative'])
            ->where('member_id', $member->id)
            ->latest('date_joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'activity' => [
                        'id' => $participant->activity?->id,
                        'title' => $participant->activity?->title,
                        'category' => $participant->activity?->category,
                        'status' => $participant->activity?->status,
                        'cooperative' => $participant->activity?->cooperative?->name,
                    ],
                    'role' => $participant->role,
                    'date_joined' => optional($participant->date_joined)->toDateString(),
                    'is_beneficiary' => (bool) $participant->is_beneficiary,
                    'remarks' => $participant->remarks,
                ];
            });

        return Inertia::render('Members/ActivityParticipants', [
            'member' => $member->load('cooperative'),
            'participants' => $participants,
        ]);
    }

    /**
     * Update the specified member in storage
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $user = auth()->user();
        $coopId = $user?->coop_id;

        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        if (!$request->user()?->can('update members-profile')) {
            abort(403, 'You do not have permission to update members.');
        }

        if ($this->isCoopAdmin() && $coopId && $member->coop_id !== $coopId) {
            abort(403);
        }

        if ($this->isOfficer() && $coopId && $member->coop_id !== $coopId) {
            abort(403);
        }

        $rules = [
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'address' => ['nullable', 'string'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city_municipality' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('members', 'email')->ignore($member->id)],
            'date_joined' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'in:Regular,Associate,Honorary'],
            'membership_status' => ['nullable', 'in:Active,Suspended,Resigned,Deceased'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'educational_attainment' => ['nullable', 'in:Elementary,High School,Vocational,College,Post-Graduate,None'],
            'primary_livelihood' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'in:Farmer,Fishfolk,Employee,Entrepreneur,Youth,Women,Senior Citizen,PWD,Other'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::in($this->assignableMemberRoleIds())],
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['role_ids']) && !$request->user()?->can('assign roles')) {
            abort(403, 'You do not have permission to assign roles.');
        }

        if (!isset($validated['share_capital']) || $validated['share_capital'] === '') {
            $validated['share_capital'] = 0;
        }

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $memberData = collect($validated)->only([
            'coop_id',
            'first_name',
            'last_name',
            'birth_date',
            'gender',
            'address',
            'region',
            'province',
            'city_municipality',
            'barangay',
            'phone',
            'email',
            'date_joined',
            'membership_type',
            'membership_status',
            'share_capital',
            'educational_attainment',
            'primary_livelihood',
            'sector',
        ])->toArray();

        $oldValues = $member->getAttributes();
        $previousSector = $member->sector;
        $previousLivelihood = $member->primary_livelihood;
        $newSector = $memberData['sector'] ?? null;
        $newLivelihood = $memberData['primary_livelihood'] ?? null;
        $sectorChanged = $newSector !== $previousSector;
        $livelihoodChanged = $newLivelihood !== $previousLivelihood;

        DB::transaction(function () use ($member, $memberData, $validated, $sectorChanged, $livelihoodChanged, $previousSector, $previousLivelihood, $newSector, $newLivelihood, $oldValues) {
            $member->update($memberData);
            $member->roles()->sync($validated['role_ids'] ?? []);

            $this->logDetailedActivity(
                'updated',
                $member,
                $oldValues,
                $member->fresh()->getAttributes(),
                'Members'
            );

            $userAccount = $member->user;
            if ($userAccount) {
                $this->syncUserRolesFromMember($userAccount, $member);
            }

            if ($sectorChanged || $livelihoodChanged) {
                MemberSectorHistory::create([
                    'member_id' => $member->id,
                    'previous_sector' => $previousSector,
                    'new_sector' => $newSector ?? $previousSector ?? 'Unspecified',
                    'previous_livelihood' => $previousLivelihood,
                    'new_livelihood' => $newLivelihood,
                    'changed_by' => auth()->user()?->name,
                    'changed_at' => now(),
                ]);
            }
        });

        if ($this->shouldUseCooperativeContextRedirect($request)) {
            return redirect()->to($this->resolveCooperativeContextRedirect($request, (int) $member->coop_id))
                ->with('success', 'Member updated successfully.');
        }

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified member from storage
     */
    public function destroy(Member $member): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isSuperAdmin() && !$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if (!$user?->can('delete members-profile')) {
            abort(403, 'You do not have permission to delete members.');
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $oldValues = $member->getAttributes();
        $member->delete();

        $this->logDetailedActivity(
            'deleted',
            $member,
            $oldValues,
            [],
            'Members'
        );

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    /**
     * Create and link a user account for a member.
     */
    public function createAccount(Request $request, Member $member): RedirectResponse
    {
        $user = $request->user();

        if (!$user?->can('create user-accounts')) {
            abort(403, 'You do not have permission to create accounts.');
        }

        if ($this->isCoopAdmin() && $user->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if (User::where('member_id', $member->id)->exists()) {
            return redirect()->back()->withErrors([
                'email' => 'This member already has a linked account.',
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $memberRoles = $this->resolveMemberAccountRoles($member);
        $primaryRole = $memberRoles->sortBy('level')->first();

        $account = User::create([
            'name' => $member->full_name,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'coop_id' => $member->coop_id,
            'member_id' => $member->id,
            'account_type' => $this->resolveAccountType($primaryRole),
            'account_status' => 'Active',
            'created_by' => $user->name,
            'password_changed_at' => now(),
        ]);

        $this->syncUserRolesFromMember($account, $member);

        return redirect()->back()->with('success', 'Member account created successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $user = auth()->user();
        $member = Member::withTrashed()->findOrFail($id);

        if ($user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $member->restore();

        return redirect()->route('members.index')
            ->with('success', 'Member restored successfully.');
    }
}

