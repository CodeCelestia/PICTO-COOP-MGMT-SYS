<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $perPage = (int) $request->input('per_page', 15);
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
            'members' => $this->mapMembers($this->buildMembersQuery()->get()),
            'isCooperativeContext' => $isCooperativeContext,
            'contextCooperativeId' => $contextCooperativeId,
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
        $membersByCoop = $members->groupBy('coop_id')->map(fn ($group) => $group->pluck('id'));

        $invalidMemberIds = $memberIds->diff($members->pluck('id'));
        if ($invalidMemberIds->isNotEmpty()) {
            return back()->withErrors([
                'member_ids' => 'One or more selected members are invalid.',
            ])->withInput();
        }

        $selectedMemberIds = $memberIds;

        unset($validated['coop_ids'], $validated['member_ids']);

        $createdCount = 0;

        foreach ($selectedCoopIds as $selectedCoopId) {
            $this->enforceCoopScope((int) $selectedCoopId);

            $payload = $validated;
            $payload['coop_id'] = (int) $selectedCoopId;

            $training = Training::create($payload);
            $createdCount++;

            foreach ($membersByCoop->get($selectedCoopId, collect()) as $memberId) {
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

        $successMessage = $createdCount > 1
            ? "Training records created successfully for {$createdCount} cooperatives."
            : 'Training record created successfully.';

        return redirect()->route('trainings.index')
            ->with('success', $successMessage);
    }

    public function edit(Training $training): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($training->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        $training->load('cooperative', 'participants.member.roles', 'participants.member.cooperative');
        $selectedMemberIds = $training->participants->pluck('member_id')->toArray();

        return Inertia::render('Trainings/Edit', [
            'training' => $training,
            'cooperatives' => $cooperativesQuery->get(),
            'members' => $this->mapMembers($this->buildMembersQuery()->get()),
            'selected_member_ids' => $selectedMemberIds,
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
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
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

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $oldValues = $training->getAttributes();
        $validated['follow_up_needed'] = (bool) ($validated['follow_up_needed'] ?? false);
        if (!$validated['follow_up_needed']) {
            $validated['follow_up_date'] = null;
        }

        $selectedMemberIds = collect($validated['member_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $memberIdsForCoop = Member::whereIn('id', $selectedMemberIds)
            ->where('coop_id', $training->coop_id)
            ->pluck('id');

        if ($selectedMemberIds->diff($memberIdsForCoop)->isNotEmpty()) {
            return back()->withErrors([
                'member_ids' => 'Selected members must belong to this cooperative.',
            ])->withInput();
        }

        unset($validated['member_ids']);

        $this->enforceCoopScope((int) $validated['coop_id']);

        $training->update($validated);

        $this->logDetailedActivity(
            'updated',
            $training,
            $oldValues,
            $training->fresh()->getAttributes(),
            'Trainings'
        );

        $existingMemberIds = $training->participants()->pluck('member_id');
        $memberIdsToAdd = $memberIdsForCoop->diff($existingMemberIds);
        $memberIdsToRemove = $existingMemberIds->diff($memberIdsForCoop);

        foreach ($memberIdsToAdd as $memberId) {
            TrainingParticipant::firstOrCreate([
                'training_id' => $training->id,
                'member_id' => $memberId,
            ]);
        }

        if ($memberIdsToRemove->isNotEmpty()) {
            $training->participants()->whereIn('member_id', $memberIdsToRemove)->delete();
        }

        return redirect()->route('trainings.index')
            ->with('success', 'Training record updated successfully.');
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
