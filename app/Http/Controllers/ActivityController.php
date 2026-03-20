<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Cooperative;
use App\Models\Officer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
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
     * Display a listing of activities.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Activity::with(['cooperative', 'responsibleOfficer.member']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('funding_source', 'like', "%{$search}%")
                    ->orWhere('implementing_partner', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $activities = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'category', 'coop_id']),
        ]);
    }

    /**
     * Show the form for creating a new activity.
     */
    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Activities/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created activity.
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'])],
            'date_started' => ['nullable', 'date'],
            'date_ended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'status' => ['required', Rule::in(['Planned', 'In Progress', 'Completed', 'Cancelled'])],
            'responsible_officer_id' => ['nullable', 'exists:officers,id'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_expense' => ['nullable', 'numeric', 'min:0'],
            'target_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'target_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'implementing_partner' => ['nullable', 'string', 'max:255'],
            'outcomes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        if (!empty($validated['responsible_officer_id'])) {
            $officer = Officer::find($validated['responsible_officer_id']);
            if ($officer && $officer->coop_id !== (int) $validated['coop_id']) {
                return back()->withErrors(['responsible_officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        Activity::create($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Activity created successfully.');
    }

    /**
     * Show the form for editing an activity.
     */
    public function edit(Activity $activity): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($activity->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Activities/Edit', [
            'activity' => $activity->load(['cooperative', 'responsibleOfficer.member']),
            'cooperatives' => $cooperativesQuery->get(),
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
        ]);
    }

    /**
     * Update an activity.
     */
    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($activity->coop_id);

        $coopId = $user?->coop_id;
        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'])],
            'date_started' => ['nullable', 'date'],
            'date_ended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'status' => ['required', Rule::in(['Planned', 'In Progress', 'Completed', 'Cancelled'])],
            'responsible_officer_id' => ['nullable', 'exists:officers,id'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_expense' => ['nullable', 'numeric', 'min:0'],
            'target_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'target_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'implementing_partner' => ['nullable', 'string', 'max:255'],
            'outcomes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        if (!empty($validated['responsible_officer_id'])) {
            $officer = Officer::find($validated['responsible_officer_id']);
            if ($officer && $officer->coop_id !== (int) $validated['coop_id']) {
                return back()->withErrors(['responsible_officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        $activity->update($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Activity updated successfully.');
    }

    /**
     * Remove an activity.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($activity->coop_id);

        $activity->delete();

        return redirect()->route('activities.index')
            ->with('success', 'Activity deleted successfully.');
    }
}
