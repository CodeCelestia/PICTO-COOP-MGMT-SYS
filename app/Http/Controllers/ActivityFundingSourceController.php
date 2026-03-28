<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\Cooperative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityFundingSourceController extends Controller
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
     * Display a listing of funding sources.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = ActivityFundingSource::with(['activity', 'cooperative']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('funder_name', 'like', "%{$search}%")
                    ->orWhereHas('activity', function ($activityQuery) use ($search) {
                        $activityQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('funder_type')) {
            $query->where('funder_type', $request->funder_type);
        }

        if ($request->filled('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $fundingSources = $query->latest()->paginate($perPage)->withQueryString();

        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityFundingSources/Index', [
            'fundingSources' => $fundingSources,
            'activities' => $activitiesQuery->get(),
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'status', 'funder_type', 'activity_id', 'coop_id', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new funding source.
     */
    public function create(Request $request): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityFundingSources/Create', [
            'activities' => $activitiesQuery->get(),
            'cooperatives' => $cooperativesQuery->get(),
        ]);
    }

    /**
     * Store a newly created funding source.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'funder_name' => ['required', 'string', 'max:255'],
            'funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'amount_released' => ['nullable', 'numeric', 'min:0'],
            'date_released' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $activity = Activity::find($validated['activity_id']);
        if (!$activity) {
            return back()->withErrors(['activity_id' => 'Selected activity not found.']);
        }

        $this->enforceCoopScope($activity->coop_id);

        if ((int) $validated['coop_id'] !== (int) $activity->coop_id) {
            return back()->withErrors(['coop_id' => 'Selected cooperative does not match the activity.']);
        }

        ActivityFundingSource::create($validated);

        return redirect()->route('activity-funding-sources.index')
            ->with('success', 'Funding source added successfully.');
    }

    /**
     * Show the form for editing a funding source.
     */
    public function edit(ActivityFundingSource $activityFundingSource): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($activityFundingSource->coop_id);

        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityFundingSources/Edit', [
            'fundingSource' => $activityFundingSource->load(['activity', 'cooperative']),
            'activities' => $activitiesQuery->get(),
            'cooperatives' => $cooperativesQuery->get(),
        ]);
    }

    /**
     * Update a funding source.
     */
    public function update(Request $request, ActivityFundingSource $activityFundingSource): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'funder_name' => ['required', 'string', 'max:255'],
            'funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'amount_released' => ['nullable', 'numeric', 'min:0'],
            'date_released' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $activity = Activity::find($validated['activity_id']);
        if (!$activity) {
            return back()->withErrors(['activity_id' => 'Selected activity not found.']);
        }

        $this->enforceCoopScope($activity->coop_id);

        if ((int) $validated['coop_id'] !== (int) $activity->coop_id) {
            return back()->withErrors(['coop_id' => 'Selected cooperative does not match the activity.']);
        }

        $activityFundingSource->update($validated);

        return redirect()->route('activity-funding-sources.index')
            ->with('success', 'Funding source updated successfully.');
    }

    /**
     * Remove a funding source.
     */
    public function destroy(ActivityFundingSource $activityFundingSource): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($activityFundingSource->coop_id);

        $activityFundingSource->delete();

        return redirect()->route('activity-funding-sources.index')
            ->with('success', 'Funding source deleted successfully.');
    }
}
