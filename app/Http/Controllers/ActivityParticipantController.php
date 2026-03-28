<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Cooperative;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityParticipantController extends Controller
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

    private function enforceCoopScope(int $coopId): void
    {
        $user = auth()->user();

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id && $coopId !== $user->coop_id) {
            abort(403);
        }
    }

    /**
     * Display a listing of activity participants.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = ActivityParticipant::with(['activity.cooperative', 'member']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->whereHas('activity', function ($q) use ($user) {
                $q->where('coop_id', $user->coop_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('activity', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->whereHas('activity', function ($q) use ($request) {
                $q->where('coop_id', $request->coop_id);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $participants = $query->latest()->paginate($perPage)->withQueryString();

        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityParticipants/Index', [
            'participants' => $participants,
            'activities' => $activitiesQuery->get(),
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'activity_id', 'coop_id', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new activity participant.
     */
    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityParticipants/Create', [
            'activities' => $activitiesQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'cooperatives' => $cooperativesQuery->get(),
        ]);
    }

    /**
     * Store a newly created participant.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'member_id' => ['required', 'exists:members,id'],
            'role' => ['nullable', 'string', 'max:100'],
            'date_joined' => ['nullable', 'date'],
            'is_beneficiary' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $activity = Activity::find($validated['activity_id']);
        if (!$activity) {
            return back()->withErrors(['activity_id' => 'Selected activity not found.']);
        }

        $this->enforceCoopScope($activity->coop_id);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $activity->coop_id) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $validated['is_beneficiary'] = (bool) ($validated['is_beneficiary'] ?? false);

        ActivityParticipant::create($validated);

        return redirect()->route('activity-participants.index')
            ->with('success', 'Activity participant added successfully.');
    }

    /**
     * Show the form for editing a participant.
     */
    public function edit(ActivityParticipant $activityParticipant): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $activityParticipant->load(['activity', 'member']);
        $this->enforceCoopScope($activityParticipant->activity->coop_id);

        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityParticipants/Edit', [
            'participant' => $activityParticipant,
            'activities' => $activitiesQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'cooperatives' => $cooperativesQuery->get(),
        ]);
    }

    /**
     * Update a participant.
     */
    public function update(Request $request, ActivityParticipant $activityParticipant): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'member_id' => ['required', 'exists:members,id'],
            'role' => ['nullable', 'string', 'max:100'],
            'date_joined' => ['nullable', 'date'],
            'is_beneficiary' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $activity = Activity::find($validated['activity_id']);
        if (!$activity) {
            return back()->withErrors(['activity_id' => 'Selected activity not found.']);
        }

        $this->enforceCoopScope($activity->coop_id);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $activity->coop_id) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $validated['is_beneficiary'] = (bool) ($validated['is_beneficiary'] ?? false);

        $activityParticipant->update($validated);

        return redirect()->route('activity-participants.index')
            ->with('success', 'Activity participant updated successfully.');
    }

    /**
     * Remove a participant.
     */
    public function destroy(ActivityParticipant $activityParticipant): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $activityParticipant->load('activity');
        $this->enforceCoopScope($activityParticipant->activity->coop_id);

        $activityParticipant->delete();

        return redirect()->route('activity-participants.index')
            ->with('success', 'Activity participant removed successfully.');
    }
}
