<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Cooperative;
use App\Models\PdsSubmission;
use App\Models\Role;
use App\Models\MemberSectorHistory;
use App\Models\MemberServiceAvailed;
use App\Models\ActivityParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Coop Admin') || $user->account_type === 'Coop Admin')
            : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Provincial Admin') || $user->account_type === 'Provincial Admin')
            : false;
    }

    private function isOfficer(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Officer') || $user->account_type === 'Officer')
            : false;
    }

    /**
     * Display a listing of members
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Member::with('cooperative');

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
        if ($request->input('membership_status') === 'Archived') {
            $query->onlyTrashed();
        } elseif ($request->filled('membership_status')) {
            $query->where('membership_status', $request->membership_status);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $members = $query->with(['cooperative', 'user'])->latest()->paginate($perPage)->withQueryString();

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
     * Show the form for creating a new member
     */
    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('Members/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'availableRoles' => Role::orderBy('level')->get(),
        ]);
    }

    /**
     * Display the specified member
     */
    public function show(Member $member): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer() && !$user->hasRole('Committee Member') && !$user->hasRole('Viewer')) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isOfficer() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $userAccount = User::with('roles')->where('member_id', $member->id)->first();

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

        $participants = $member->activityParticipants()
            ->with('activity')
            ->latest('date_joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'activity' => $participant->activity?->title,
                    'role' => $participant->role,
                    'date_joined' => optional($participant->date_joined)->toDateString(),
                    'is_beneficiary' => (bool) $participant->is_beneficiary,
                ];
            });

        return Inertia::render('Members/Show', [
            'member' => $member->load('cooperative'),
            'userAccount' => $userAccount ? [
                'email' => $userAccount->email,
                'roles' => $userAccount->roles->pluck('name')->toArray(),
            ] : null,
            'servicesAvailed' => $servicesAvailed,
            'activityParticipants' => $participants,
        ]);
    }

    /**
     * Store a newly created member in storage
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
            'email' => ['required', 'email', 'max:100', 'unique:users,email', 'unique:members,email'],
            'date_joined' => ['nullable', 'date'],
            'membership_type' => ['nullable', 'in:Regular,Associate,Honorary'],
            'membership_status' => ['nullable', 'in:Active,Suspended,Resigned,Deceased'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'educational_attainment' => ['nullable', 'in:Elementary,High School,Vocational,College,Post-Graduate,None'],
            'primary_livelihood' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'in:Farmer,Fishfolk,Employee,Entrepreneur,Youth,Women,Senior Citizen,PWD,Other'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        if (!isset($validated['share_capital']) || $validated['share_capital'] === '') {
            $validated['share_capital'] = 0;
        }

        if (empty($validated['date_joined'])) {
            $validated['date_joined'] = now()->toDateString();
        }

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        DB::transaction(function () use ($validated) {
            $member = Member::create($validated);

            $accountType = 'Member';
            if (!empty($validated['role_ids'])) {
                $firstRole = Role::find($validated['role_ids'][0]);
                if ($firstRole) {
                    $accountType = $firstRole->name;
                }
            }

            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'coop_id' => $validated['coop_id'],
                'member_id' => $member->id,
                'account_type' => $accountType,
                'account_status' => 'Active',
                'created_by' => auth()->user()->name,
                'password_changed_at' => now(),
            ]);

            if (!empty($validated['role_ids'])) {
                $roles = Role::whereIn('id', $validated['role_ids'])->get();
                $user->assignRole($roles);
            } else {
                $memberRole = Role::where('name', 'Member')->first();
                if ($memberRole) {
                    $user->assignRole($memberRole);
                }
            }
        });

        return redirect()->route('members.index')
            ->with('success', 'Member and user account created successfully.');
    }

    /**
     * Show the form for editing the specified member
     */
    public function edit(Member $member): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
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
            'availableRoles' => Role::orderBy('level')->get(),
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

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
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

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
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

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $coopId && $member->coop_id !== $coopId) {
            abort(403);
        }

        if ($this->isOfficer() && $coopId && $member->coop_id !== $coopId) {
            abort(403);
        }

        $userAccount = User::where('member_id', $member->id)->first();
        $updateAccount = $request->boolean('update_account');

        $candidateAccountEmail = $request->input('account_email');
        $candidateUser = null;
        if ($updateAccount && $candidateAccountEmail) {
            $candidateUser = User::where('email', $candidateAccountEmail)->first();
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
        ];

        if ($updateAccount) {
            $emailRule = Rule::unique('users', 'email');
            if ($userAccount) {
                $emailRule = $emailRule->ignore($userAccount->id);
            } elseif ($candidateUser && ($candidateUser->member_id === null || $candidateUser->member_id === $member->id)) {
                $emailRule = $emailRule->ignore($candidateUser->id);
            }

            $rules['account_email'] = ['required', 'email', 'max:100', $emailRule];
            $rules['role_ids'] = ['nullable', 'array'];
            $rules['role_ids.*'] = ['exists:roles,id'];
            $rules['account_password'] = $userAccount
                ? ['nullable', 'confirmed', Password::defaults()]
                : ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

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

        $previousSector = $member->sector;
        $previousLivelihood = $member->primary_livelihood;
        $newSector = $memberData['sector'] ?? null;
        $newLivelihood = $memberData['primary_livelihood'] ?? null;
        $sectorChanged = $newSector !== $previousSector;
        $livelihoodChanged = $newLivelihood !== $previousLivelihood;

        DB::transaction(function () use ($member, $memberData, $validated, $updateAccount, $userAccount, $candidateUser, $sectorChanged, $livelihoodChanged, $previousSector, $previousLivelihood, $newSector, $newLivelihood) {
            $member->update($memberData);

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

            if (!$updateAccount) {
                return;
            }

            $accountType = 'Member';
            if (!empty($validated['role_ids'])) {
                $firstRole = Role::find($validated['role_ids'][0]);
                if ($firstRole) {
                    $accountType = $firstRole->name;
                }
            }

            $accountUser = $userAccount;
            if (!$accountUser && $candidateUser && ($candidateUser->member_id === null || $candidateUser->member_id === $member->id)) {
                $accountUser = $candidateUser;
            }

            $user = $accountUser ?: User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['account_email'],
                'password' => Hash::make($validated['account_password']),
                'coop_id' => $member->coop_id,
                'member_id' => $member->id,
                'account_type' => $accountType,
                'account_status' => 'Active',
                'created_by' => auth()->user()->name,
                'password_changed_at' => now(),
            ]);

            if ($accountUser) {
                $user->update([
                    'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                    'email' => $validated['account_email'],
                    'coop_id' => $member->coop_id,
                    'member_id' => $member->id,
                    'account_type' => $accountType,
                ]);

                if (!empty($validated['account_password'])) {
                    $user->update([
                        'password' => Hash::make($validated['account_password']),
                        'password_changed_at' => now(),
                    ]);
                }
            }

            if (!empty($validated['role_ids'])) {
                $roles = Role::whereIn('id', $validated['role_ids'])->get();
                $user->syncRoles($roles);
            } else {
                $memberRole = Role::where('name', 'Member')->first();
                if ($memberRole) {
                    $user->syncRoles([$memberRole]);
                }
            }
        });

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified member from storage
     */
    public function destroy(Member $member): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $member = Member::withTrashed()->findOrFail($id);

        if ($this->isCoopAdmin() && $user?->coop_id && $member->coop_id !== $user->coop_id) {
            abort(403);
        }

        $member->restore();

        return redirect()->route('members.index')
            ->with('success', 'Member restored successfully.');
    }
}

