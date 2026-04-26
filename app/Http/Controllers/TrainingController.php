<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivityWithChanges;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    use LogsActivityWithChanges;

    private function resolveGroupedTrainingIds(Training $training): Collection
    {
        return Training::query()
            ->where('title', $training->title)
            ->where('date_conducted', $training->date_conducted)
            ->where('facilitator', $training->facilitator)
            ->where('skills_targeted', $training->skills_targeted)
            ->where('venue', $training->venue)
            ->where('target_group', $training->target_group)
            ->where('no_of_participants', $training->no_of_participants)
            ->where('follow_up_needed', $training->follow_up_needed)
            ->where('follow_up_date', $training->follow_up_date)
            ->where('follow_up_remarks', $training->follow_up_remarks)
            ->where('status', $training->status)
            ->pluck('id');
    }

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

    private function normalizeDateInput(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function buildMembersQuery()
    {
        $user = auth()->user();

        $membersQuery = Member::with(['cooperative:id,name', 'roles:id,name'])
            ->select('id', 'first_name', 'last_name', 'coop_id', 'membership_status')
            ->orderBy('last_name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return $membersQuery;
    }

    private function mapMembers($members)
    {
        return $members->map(function (Member $member) {
            return [
                'id' => $member->id,
                'name' => $member->full_name,
                'coop_id' => $member->coop_id,
                'coop_name' => $member->cooperative?->name ?? '',
                'status' => $member->membership_status ?? 'Active',
                'role' => $member->roles->pluck('name')->filter()->join(', ') ?: 'Member',
            ];
        });
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $baseQuery = Training::query();

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $baseQuery->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('facilitator', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->status);
        }

        if ($request->filled('target_group')) {
            $baseQuery->where('target_group', $request->target_group);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $baseQuery->where('coop_id', $request->coop_id);
        }

        $groupColumns = [
            'title',
            'date_conducted',
            'facilitator',
            'skills_targeted',
            'venue',
            'target_group',
            'no_of_participants',
            'follow_up_needed',
            'follow_up_date',
            'follow_up_remarks',
            'status',
        ];

        $groupedQuery = (clone $baseQuery)
            ->selectRaw('MAX(id) as id, COUNT(DISTINCT coop_id) as cooperatives_count')
            ->groupBy($groupColumns);

        $query = Training::with('cooperative')
            ->joinSub($groupedQuery, 'training_groups', function ($join) {
                $join->on('trainings.id', '=', 'training_groups.id');
            })
            ->addSelect('trainings.*')
            ->addSelect(DB::raw('training_groups.cooperatives_count as cooperatives_count'));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 500));

        $trainings = $query->orderByDesc('trainings.created_at')->paginate($perPage)->withQueryString();

        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region')->orderBy('name');
        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('Trainings/Index', [
            'trainings' => $trainings,
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'status', 'target_group', 'coop_id', 'per_page']),
        ]);
    }

    public function cooperativesParticipating(Training $training): Response
    {
        $this->enforceCoopScope($training->coop_id);

        $linkedTrainingIds = $this->resolveGroupedTrainingIds($training);

        $allCoopIds = Training::query()
            ->whereIn('id', $linkedTrainingIds)
            ->whereNotNull('coop_id')
            ->distinct()
            ->pluck('coop_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $cooperatives = Cooperative::query()
            ->select('id', 'name', 'classification', 'status')
            ->whereIn('id', $allCoopIds)
            ->orderBy('name')
            ->get();

        return Inertia::render('Trainings/CooperativesParticipating', [
            'training' => [
                'id' => $training->id,
                'title' => $training->title,
            ],
            'cooperatives' => $cooperatives,
        ]);
    }

    public function participantsByCooperative(Training $training, Cooperative $cooperative): Response
    {
        $this->enforceCoopScope($training->coop_id);

        $linkedTrainingIds = $this->resolveGroupedTrainingIds($training);

        $isParticipating = Training::query()
            ->whereIn('id', $linkedTrainingIds)
            ->where('coop_id', $cooperative->id)
            ->exists();

        if (! $isParticipating) {
            abort(404);
        }

        $participants = TrainingParticipant::query()
            ->with(['member:id,first_name,last_name,email,phone,coop_id'])
            ->whereIn('training_id', $linkedTrainingIds)
            ->whereHas('member', fn ($query) => $query->where('coop_id', $cooperative->id))
            ->latest()
            ->get();

        return Inertia::render('Trainings/ParticipantsByCooperative', [
            'training' => [
                'id' => $training->id,
                'title' => $training->title,
            ],
            'cooperative' => [
                'id' => $cooperative->id,
                'name' => $cooperative->name,
            ],
            'participants' => $participants,
        ]);
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Trainings',
            'description' => 'Select a cooperative to view trainings.',
            'targetUrl' => '/trainings',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region', 'classification')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        $cooperatives = $cooperativesQuery->get();
        $requestedCoopId = (int) $request->input('coop_id');
        $isCooperativeContext = $request->boolean('coop_context') && $requestedCoopId > 0;
        $contextCooperativeId = null;

        if ($isCooperativeContext && $cooperatives->contains('id', $requestedCoopId)) {
            $contextCooperativeId = $requestedCoopId;
        } else {
            $isCooperativeContext = false;
        }

        return Inertia::render('Trainings/Create', [
            'cooperatives' => $cooperatives,
            'isCooperativeContext' => $isCooperativeContext,
            'contextCooperativeId' => $contextCooperativeId,
        ]);
    }

    public function membersByCooperatives(Request $request): JsonResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validated = $request->validate([
            'cooperative_ids' => ['required', 'array', 'min:1'],
            'cooperative_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([(int) $coopId])]
                : ['integer', 'exists:cooperatives,id'],
        ]);

        $selectedCoopIds = collect($validated['cooperative_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($this->isCoopAdmin() && $coopId) {
            $selectedCoopIds = collect([(int) $coopId]);
        }

        $cooperatives = Cooperative::query()
            ->select('id', 'name')
            ->whereIn('id', $selectedCoopIds)
            ->orderBy('name')
            ->get();

        $membersByCoop = Member::query()
            ->select('id', 'first_name', 'last_name', 'coop_id')
            ->whereIn('coop_id', $cooperatives->pluck('id'))
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get()
            ->groupBy('coop_id');

        return response()->json([
            'cooperatives' => $cooperatives->map(function (Cooperative $cooperative) use ($membersByCoop) {
                return [
                    'id' => $cooperative->id,
                    'name' => $cooperative->name,
                    'members' => $membersByCoop
                        ->get($cooperative->id, collect())
                        ->map(fn (Member $member) => [
                            'id' => $member->id,
                            'first_name' => $member->first_name,
                            'last_name' => $member->last_name,
                            'coop_id' => $member->coop_id,
                        ])
                        ->values(),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['nullable', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['nullable', 'exists:cooperatives,id'],
            'coop_ids' => ['nullable', 'array'],
            'coop_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['integer', 'exists:cooperatives,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'title' => ['required', 'string', 'max:255'],
            'date_conducted' => ['nullable', 'date'],
            'facilitator' => ['nullable', 'string', 'max:255'],
            'skills_targeted' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'target_group' => ['required', Rule::in(['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'])],
            'no_of_participants' => ['nullable', 'integer', 'min:0'],
            'follow_up_needed' => ['nullable', 'boolean'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_remarks' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'])],
        ]);

        $selectedCoopIds = collect($validated['coop_ids'] ?? [])
            ->merge(!empty($validated['coop_id']) ? [$validated['coop_id']] : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($this->isCoopAdmin() && $coopId) {
            $selectedCoopIds = collect([(int) $coopId]);
        }

        if ($selectedCoopIds->isEmpty()) {
            return back()->withErrors([
                'coop_ids' => 'Please select at least one cooperative.',
            ])->withInput();
        }

        $validated['date_conducted'] = $this->normalizeDateInput($validated['date_conducted'] ?? null);
        $validated['follow_up_date'] = $this->normalizeDateInput($validated['follow_up_date'] ?? null);

        $validated['follow_up_needed'] = (bool) ($validated['follow_up_needed'] ?? false);
        if (!$validated['follow_up_needed']) {
            $validated['follow_up_date'] = null;
        }

        $memberIds = collect($validated['member_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $members = Member::whereIn('id', $memberIds)->get(['id', 'coop_id']);
        $membersByCoop = $members->groupBy('coop_id')->map(fn ($group) => $group->pluck('id')->values());

        $invalidMemberIds = $memberIds->diff($members->pluck('id'));
        if ($invalidMemberIds->isNotEmpty()) {
            return back()->withErrors([
                'member_ids' => 'One or more selected members are invalid.',
            ])->withInput();
        }

        $validSelectedMemberCount = Member::query()
            ->whereIn('coop_id', $selectedCoopIds->all())
            ->whereIn('id', $memberIds)
            ->count();

        if ($validSelectedMemberCount !== $memberIds->count()) {
            return back()->withErrors([
                'member_ids' => 'Selected members must belong to one of the selected cooperatives.',
            ])->withInput();
        }

        $selectedMemberIds = $memberIds;

        unset($validated['coop_ids'], $validated['member_ids']);

        $createdCount = 0;

        DB::transaction(function () use ($validated, $selectedCoopIds, $membersByCoop, &$createdCount) {
            foreach ($selectedCoopIds as $selectedCoopId) {
                $this->enforceCoopScope((int) $selectedCoopId);

                $payload = $validated;
                $payload['coop_id'] = (int) $selectedCoopId;

                $training = Training::create($payload);
                $createdCount++;

                foreach ($membersByCoop->get((int) $selectedCoopId, collect()) as $memberId) {
                    TrainingParticipant::firstOrCreate([
                        'training_id' => $training->id,
                        'member_id' => $memberId,
                    ]);
                }

                $this->logDetailedActivity(
                    'created',
                    $training,
                    [],
                    $training->fresh()->getAttributes(),
                    'Trainings'
                );
            }
        });

        $successMessage = $createdCount > 1
            ? "Training records created successfully for {$createdCount} cooperatives."
            : 'Training record created successfully.';

        return redirect()->route('trainings.index')
            ->with('success', $successMessage);
    }

    public function edit(Request $request, Training $training): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($training->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region', 'classification')->orderBy('name');
        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        $cooperatives = $cooperativesQuery->get();
        $requestedCoopId = (int) $request->input('coop_id');
        $isCooperativeContext = $request->boolean('coop_context') && $requestedCoopId > 0;
        $contextCooperativeId = null;

        if ($isCooperativeContext && $cooperatives->contains('id', $requestedCoopId)) {
            $contextCooperativeId = $requestedCoopId;
        } else {
            $isCooperativeContext = false;
        }

        $linkedTrainingIds = $this->resolveGroupedTrainingIds($training);
        $assignedCoopIds = Training::query()
            ->whereIn('id', $linkedTrainingIds)
            ->whereNotNull('coop_id')
            ->distinct()
            ->pluck('coop_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $cooperatives->contains('id', $id))
            ->values()
            ->all();

        $training->load('cooperative', 'participants.member.roles', 'participants.member.cooperative');
        $selectedMemberIds = TrainingParticipant::query()
            ->whereIn('training_id', $linkedTrainingIds)
            ->pluck('member_id')
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Trainings/Edit', [
            'training' => array_merge($training->toArray(), [
                'date_conducted' => optional($training->date_conducted)->format('Y-m-d'),
                'follow_up_date' => optional($training->follow_up_date)->format('Y-m-d'),
            ]),
            'cooperatives' => $cooperatives,
            'selected_member_ids' => $selectedMemberIds,
            'isCooperativeContext' => $isCooperativeContext,
            'contextCooperativeId' => $contextCooperativeId,
            'assigned_coop_ids' => $assignedCoopIds,
        ]);
    }

    public function update(Request $request, Training $training): RedirectResponse
    {
        $user = auth()->user();
        $coopId = $user?->coop_id;

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['nullable', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['nullable', 'exists:cooperatives,id'],
            'coop_ids' => ['nullable', 'array'],
            'coop_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['integer', 'exists:cooperatives,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'title' => ['required', 'string', 'max:255'],
            'date_conducted' => ['nullable', 'date'],
            'facilitator' => ['nullable', 'string', 'max:255'],
            'skills_targeted' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'target_group' => ['required', Rule::in(['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'])],
            'no_of_participants' => ['nullable', 'integer', 'min:0'],
            'follow_up_needed' => ['nullable', 'boolean'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_remarks' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'])],
        ]);

        $linkedTrainingIds = $this->resolveGroupedTrainingIds($training);
        $linkedTrainings = Training::query()->whereIn('id', $linkedTrainingIds)->get()->keyBy('coop_id');

        $selectedCoopIds = collect($validated['coop_ids'] ?? [])
            ->merge(!empty($validated['coop_id']) ? [$validated['coop_id']] : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($this->isCoopAdmin() && $coopId) {
            $selectedCoopIds = collect([(int) $coopId]);
        }

        if ($selectedCoopIds->isEmpty()) {
            return back()->withErrors([
                'coop_ids' => 'Please select at least one cooperative.',
            ])->withInput();
        }

        $validated['date_conducted'] = $this->normalizeDateInput($validated['date_conducted'] ?? null);
        $validated['follow_up_date'] = $this->normalizeDateInput($validated['follow_up_date'] ?? null);
        $validated['follow_up_needed'] = (bool) ($validated['follow_up_needed'] ?? false);
        if (!$validated['follow_up_needed']) {
            $validated['follow_up_date'] = null;
        }

        $selectedMemberIds = collect($validated['member_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $members = Member::whereIn('id', $selectedMemberIds)
            ->get(['id', 'coop_id']);

        $invalidMemberIds = $selectedMemberIds->diff($members->pluck('id'));
        if ($invalidMemberIds->isNotEmpty()) {
            return back()->withErrors([
                'member_ids' => 'One or more selected members are invalid.',
            ])->withInput();
        }

        $validSelectedMemberCount = Member::query()
            ->whereIn('coop_id', $selectedCoopIds->all())
            ->whereIn('id', $selectedMemberIds)
            ->count();

        if ($validSelectedMemberCount !== $selectedMemberIds->count()) {
            return back()->withErrors([
                'member_ids' => 'Selected members must belong to one of the selected cooperatives.',
            ])->withInput();
        }

        $membersByCoop = $members->groupBy('coop_id')->map(fn ($group) => $group->pluck('id')->values());

        $oldValues = $training->getAttributes();
        $sharedPayload = Arr::only($validated, [
            'title',
            'date_conducted',
            'facilitator',
            'skills_targeted',
            'venue',
            'target_group',
            'no_of_participants',
            'follow_up_needed',
            'follow_up_date',
            'follow_up_remarks',
            'status',
        ]);

        unset($validated['member_ids'], $validated['coop_ids']);

        DB::transaction(function () use ($training, $selectedCoopIds, $membersByCoop, $sharedPayload, $linkedTrainings, $oldValues) {
            $updatedCurrentTraining = null;

            foreach ($linkedTrainings as $linkedTraining) {
                if (! $selectedCoopIds->contains((int) $linkedTraining->coop_id)) {
                    $linkedTraining->participants()->delete();
                    $linkedTraining->delete();
                }
            }

            foreach ($selectedCoopIds as $selectedCoopId) {
                $this->enforceCoopScope((int) $selectedCoopId);

                $payload = $sharedPayload;
                $payload['coop_id'] = (int) $selectedCoopId;

                $linkedTraining = $linkedTrainings->get((int) $selectedCoopId);
                if ($linkedTraining) {
                    if ($linkedTraining->is($training)) {
                        $updatedCurrentTraining = $linkedTraining;
                    }

                    $linkedTraining->update($payload);
                    $linkedTraining->participants()->delete();
                } else {
                    $linkedTraining = Training::create($payload);
                }

                foreach ($membersByCoop->get((int) $selectedCoopId, collect()) as $memberId) {
                    TrainingParticipant::firstOrCreate([
                        'training_id' => $linkedTraining->id,
                        'member_id' => $memberId,
                    ]);
                }

                if ($linkedTraining->is($training)) {
                    $updatedCurrentTraining = $linkedTraining;
                }
            }

            $this->logDetailedActivity(
                'updated',
                $updatedCurrentTraining ?? $training,
                $oldValues,
                (($updatedCurrentTraining ?? $training)->fresh()?->getAttributes()) ?? ($updatedCurrentTraining ?? $training)->getAttributes(),
                'Trainings'
            );
        });

        return redirect()->route('trainings.index')
            ->with('success', 'Training record updated successfully.');
    }

    public function show(Training $training): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($training->coop_id);

        $linkedTrainingIds = $this->resolveGroupedTrainingIds($training);

        $trainingRecords = Training::query()
            ->with('cooperative')
            ->whereIn('id', $linkedTrainingIds)
            ->orderBy('coop_id')
            ->get();

        $primaryTraining = $trainingRecords->firstWhere('id', $training->id) ?? $trainingRecords->first() ?? $training;
        $primaryTraining->loadMissing('cooperative');

        $cooperativeIds = $trainingRecords
            ->pluck('coop_id')
            ->filter()
            ->unique()
            ->values();

        $cooperatives = Cooperative::query()
            ->select('id', 'name', 'registration_number', 'status', 'region', 'classification')
            ->whereIn('id', $cooperativeIds)
            ->orderBy('name')
            ->get();

        $participants = TrainingParticipant::query()
            ->with(['member.cooperative'])
            ->whereIn('training_id', $linkedTrainingIds)
            ->get()
            ->sortBy(function (TrainingParticipant $participant) {
                return sprintf(
                    '%s %s',
                    strtolower($participant->member?->last_name ?? ''),
                    strtolower($participant->member?->first_name ?? '')
                );
            })
            ->values();

        $participantsByCooperative = $participants
            ->groupBy(fn (TrainingParticipant $participant) => (int) ($participant->member?->coop_id ?? 0))
            ->map(function (Collection $group, int $cooperativeId) use ($cooperatives) {
                $cooperative = $cooperatives->firstWhere('id', $cooperativeId);

                return [
                    'id' => $cooperative?->id ?? $cooperativeId,
                    'name' => $cooperative?->name ?? 'No cooperative',
                    'registration_number' => $cooperative?->registration_number,
                    'status' => $cooperative?->status,
                    'region' => $cooperative?->region,
                    'classification' => $cooperative?->classification,
                    'participants_count' => $group->count(),
                    'participants' => $group->map(function (TrainingParticipant $participant) {
                        return [
                            'id' => $participant->id,
                            'member_id' => $participant->member_id,
                            'full_name' => $participant->member?->full_name ?? 'Unknown member',
                            'first_name' => $participant->member?->first_name,
                            'last_name' => $participant->member?->last_name,
                            'member_code' => null,
                            'outcome' => $participant->outcome,
                            'certificate_no' => $participant->certificate_no,
                            'certificate_date' => optional($participant->certificate_date)->toDateString(),
                            'remarks' => $participant->remarks,
                        ];
                    })->values(),
                ];
            })
            ->sortBy('name')
            ->values();

        $cooperativesWithParticipants = $cooperatives->map(function (Cooperative $cooperative) use ($participantsByCooperative) {
            $group = $participantsByCooperative->firstWhere('id', $cooperative->id);

            return [
                'id' => $cooperative->id,
                'name' => $cooperative->name,
                'registration_number' => $cooperative->registration_number,
                'status' => $cooperative->status,
                'region' => $cooperative->region,
                'classification' => $cooperative->classification,
                'participants_count' => $group['participants_count'] ?? 0,
                'participants' => $group['participants'] ?? [],
            ];
        })->values();

        return Inertia::render('Trainings/Show', [
            'training' => [
                'id' => $primaryTraining->id,
                'coop_id' => $primaryTraining->coop_id,
                'title' => $primaryTraining->title,
                'date_conducted' => optional($primaryTraining->date_conducted)->toDateString(),
                'facilitator' => $primaryTraining->facilitator,
                'skills_targeted' => $primaryTraining->skills_targeted,
                'venue' => $primaryTraining->venue,
                'target_group' => $primaryTraining->target_group,
                'no_of_participants' => $primaryTraining->no_of_participants,
                'follow_up_needed' => (bool) $primaryTraining->follow_up_needed,
                'follow_up_date' => optional($primaryTraining->follow_up_date)->toDateString(),
                'follow_up_remarks' => $primaryTraining->follow_up_remarks,
                'status' => $primaryTraining->status,
                'cooperative' => $primaryTraining->cooperative ? [
                    'id' => $primaryTraining->cooperative->id,
                    'name' => $primaryTraining->cooperative->name,
                    'registration_number' => $primaryTraining->cooperative->registration_number,
                    'status' => $primaryTraining->cooperative->status,
                    'region' => $primaryTraining->cooperative->region,
                    'classification' => $primaryTraining->cooperative->classification,
                ] : null,
            ],
            'cooperatives' => $cooperativesWithParticipants,
            'participantsByCooperative' => $participantsByCooperative,
            'participantCount' => $participants->count(),
            'total_participants' => $participants->count(),
            'linkedTrainingCount' => $trainingRecords->count(),
            'attachments' => [],
        ]);
    }

    public function destroy(Training $training): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($training->coop_id);

        $oldValues = $training->getAttributes();
        $training->delete();

        $this->logDetailedActivity(
            'deleted',
            $training,
            $oldValues,
            [],
            'Trainings'
        );

        return redirect()->route('trainings.index')
            ->with('success', 'Training record deleted successfully.');
    }
}
