<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\Cooperative;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityFundingSourceController extends Controller
{
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

    private function resolveIndexRoute(Request $request): string
    {
        return $request->routeIs('finance.funding-sources.*')
            ? 'finance.funding-sources.index'
            : 'activity-funding-sources.index';
    }

    private function resolveIndexRedirect(Request $request, string $message): RedirectResponse
    {
        if ($request->routeIs('cooperatives.finance.funding-sources.*')) {
            $cooperative = $request->route('cooperative');
            return redirect()->route('cooperatives.finance.funding-sources.index', ['cooperative' => $cooperative->id])->with('success', $message);
        }

        if ($request->routeIs('finance.funding-sources.*')) {
            return redirect()->route('finance.funding-sources.index')->with('success', $message);
        }

        return redirect()->route('activity-funding-sources.index')->with('success', $message);
    }

    private function syncActivityFundingSourceSummary(?int $activityId): void
    {
        if (!$activityId) {
            return;
        }

        $firstFunder = ActivityFundingSource::where('activity_id', $activityId)
            ->orderBy('id')
            ->value('funder_name');

        Activity::where('id', $activityId)->update([
            'funding_source' => $firstFunder,
        ]);
    }

    private function applyCategoryContextToRemarks(array &$validated): void
    {
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        if (($validated['category'] ?? null) === 'project' && !empty($validated['project_name'])) {
            $remarks = trim("Project: {$validated['project_name']}\n{$remarks}");
        }

        if (($validated['category'] ?? null) === 'member_concern' && !empty($validated['member_id'])) {
            $member = Member::find($validated['member_id']);
            $memberLabel = $member ? $member->full_name : "Member #{$validated['member_id']}";
            $remarks = trim("Member Concern: {$memberLabel}\n{$remarks}");
        }

        $validated['remarks'] = $remarks !== '' ? $remarks : null;
        unset($validated['project_name'], $validated['member_id']);
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

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Funding Sources',
            'description' => 'Select a cooperative to view activity funding sources.',
            'targetUrl' => '/activity-funding-sources',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new funding source.
     */
    public function create(Request $request): Response
    {
        if (!$request->user()?->can('create finance-funding-sources')) {
            abort(403, 'You do not have permission to create funding sources.');
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $isCoopContext = $request->routeIs('cooperatives.finance.funding-sources.*');
        $coopContext = $isCoopContext ? $request->route('cooperative') : null;
        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityFundingSources/Create', [
            'activities' => $activitiesQuery->get(),
            'members' => $membersQuery->get()->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
                'coop_id' => $member->coop_id,
            ]),
            'cooperatives' => $cooperativesQuery->get(),
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
        ]);
    }

    /**
     * Store a newly created funding source.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()?->can('create finance-funding-sources')) {
            abort(403, 'You do not have permission to create funding sources.');
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['nullable', 'exists:activities,id', Rule::requiredIf($request->input('category') === 'activity')],
            'category' => ['required', Rule::in(['activity', 'project', 'member_concern'])],
            'project_name' => [Rule::requiredIf($request->input('category') === 'project'), 'nullable', 'string', 'max:255'],
            'member_id' => [Rule::requiredIf($request->input('category') === 'member_concern'), 'nullable', 'exists:members,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'funder_name' => ['required', 'string', 'max:255'],
            'funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'amount_released' => ['nullable', 'numeric', 'min:0'],
            'date_released' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'remarks' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_removed' => ['nullable', 'array'],
            'attachments_removed.*' => ['string'],
        ]);

        if (($validated['category'] ?? null) !== 'activity') {
            $validated['activity_id'] = null;
        }

        $activity = null;
        if (!empty($validated['activity_id'])) {
            $activity = Activity::find($validated['activity_id']);
            if (!$activity) {
                return back()->withErrors(['activity_id' => 'Selected activity not found.']);
            }

            $this->enforceCoopScope($activity->coop_id);

            if ((int) $validated['coop_id'] !== (int) $activity->coop_id) {
                return back()->withErrors(['coop_id' => 'Selected cooperative does not match the activity.']);
            }
        }

        if (($validated['category'] ?? null) === 'member_concern' && !empty($validated['member_id'])) {
            $member = Member::find($validated['member_id']);
            if (!$member) {
                return back()->withErrors(['member_id' => 'Selected member not found.']);
            }
            $this->enforceCoopScope($member->coop_id);
            if ((int) $validated['coop_id'] !== (int) $member->coop_id) {
                return back()->withErrors(['member_id' => 'Selected member does not belong to the selected cooperative.']);
            }
        }

        $attachmentPaths = [];
        $attachmentNames = [];

        if (!empty($validated['attachments_removed'])) {
            foreach ($validated['attachments_removed'] as $removedPath) {
                $index = array_search($removedPath, $attachmentPaths, true);
                if ($index !== false) {
                    Storage::disk('public')->delete($attachmentPaths[$index]);
                    array_splice($attachmentPaths, $index, 1);
                    array_splice($attachmentNames, $index, 1);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('funding-source-attachments', 'public');
                $attachmentNames[] = $file->getClientOriginalName();
            }
        }

        $validated['attachment_paths'] = $attachmentPaths;
        $validated['attachment_names'] = $attachmentNames;

        $this->applyCategoryContextToRemarks($validated);

        ActivityFundingSource::create($validated);
        $this->syncActivityFundingSourceSummary($activity?->id);

        return $this->resolveIndexRedirect($request, 'Funding source added successfully.');
    }

    /**
     * Show the form for editing a funding source.
     */
    public function edit(ActivityFundingSource $activityFundingSource): Response
    {
        $user = auth()->user();

        if (!$user?->can('update finance-funding-sources')) {
            abort(403, 'You do not have permission to update funding sources.');
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($activityFundingSource->coop_id);
        $isCoopContext = request()->routeIs('cooperatives.finance.funding-sources.*');
        $coopContext = $isCoopContext ? request()->route('cooperative') : null;

        $activitiesQuery = Activity::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('ActivityFundingSources/Edit', [
            'fundingSource' => $activityFundingSource->load(['activity', 'cooperative']),
            'activities' => $activitiesQuery->get(),
            'members' => $membersQuery->get()->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
                'coop_id' => $member->coop_id,
            ]),
            'cooperatives' => $cooperativesQuery->get(),
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
        ]);
    }

    /**
     * Update a funding source.
     */
    public function update(Request $request, ActivityFundingSource $activityFundingSource): RedirectResponse
    {
        $user = auth()->user();

        if (!$request->user()?->can('update finance-funding-sources')) {
            abort(403, 'You do not have permission to update funding sources.');
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_id' => ['nullable', 'exists:activities,id', Rule::requiredIf($request->input('category') === 'activity')],
            'category' => ['required', Rule::in(['activity', 'project', 'member_concern'])],
            'project_name' => [Rule::requiredIf($request->input('category') === 'project'), 'nullable', 'string', 'max:255'],
            'member_id' => [Rule::requiredIf($request->input('category') === 'member_concern'), 'nullable', 'exists:members,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'funder_name' => ['required', 'string', 'max:255'],
            'funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'amount_released' => ['nullable', 'numeric', 'min:0'],
            'date_released' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'remarks' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'attachments_removed' => ['nullable', 'array'],
            'attachments_removed.*' => ['string'],
        ]);

        if (($validated['category'] ?? null) !== 'activity') {
            $validated['activity_id'] = null;
        }

        $activity = null;
        if (!empty($validated['activity_id'])) {
            $activity = Activity::find($validated['activity_id']);
            if (!$activity) {
                return back()->withErrors(['activity_id' => 'Selected activity not found.']);
            }

            $this->enforceCoopScope($activity->coop_id);

            if ((int) $validated['coop_id'] !== (int) $activity->coop_id) {
                return back()->withErrors(['coop_id' => 'Selected cooperative does not match the activity.']);
            }
        }

        if (($validated['category'] ?? null) === 'member_concern' && !empty($validated['member_id'])) {
            $member = Member::find($validated['member_id']);
            if (!$member) {
                return back()->withErrors(['member_id' => 'Selected member not found.']);
            }
            $this->enforceCoopScope($member->coop_id);
            if ((int) $validated['coop_id'] !== (int) $member->coop_id) {
                return back()->withErrors(['member_id' => 'Selected member does not belong to the selected cooperative.']);
            }
        }

        $attachmentPaths = $activityFundingSource->attachment_paths ?? [];
        $attachmentNames = $activityFundingSource->attachment_names ?? [];

        if (!empty($validated['attachments_removed'])) {
            foreach ($validated['attachments_removed'] as $removedPath) {
                $index = array_search($removedPath, $attachmentPaths, true);
                if ($index !== false) {
                    Storage::disk('public')->delete($attachmentPaths[$index]);
                    array_splice($attachmentPaths, $index, 1);
                    array_splice($attachmentNames, $index, 1);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('funding-source-attachments', 'public');
                $attachmentNames[] = $file->getClientOriginalName();
            }
        }

        $validated['attachment_paths'] = $attachmentPaths;
        $validated['attachment_names'] = $attachmentNames;

        $this->applyCategoryContextToRemarks($validated);

        $previousActivityId = $activityFundingSource->activity_id ? (int) $activityFundingSource->activity_id : null;

        $activityFundingSource->update($validated);

        $newActivityId = !empty($validated['activity_id']) ? (int) $validated['activity_id'] : null;
        $this->syncActivityFundingSourceSummary($previousActivityId);
        if ($newActivityId !== $previousActivityId) {
            $this->syncActivityFundingSourceSummary($newActivityId);
        }

        return $this->resolveIndexRedirect(request(), 'Funding source updated successfully.');
    }

    /**
     * Remove a funding source.
     */
    public function destroy(Request $request, ActivityFundingSource $activityFundingSource): RedirectResponse
    {
        if (!$request->user()?->can('delete finance-funding-sources')) {
            abort(403, 'You do not have permission to delete funding sources.');
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($activityFundingSource->coop_id);

        $activityId = $activityFundingSource->activity_id ? (int) $activityFundingSource->activity_id : null;

        $activityFundingSource->delete();
        $this->syncActivityFundingSourceSummary($activityId);

        return redirect()->back()
            ->with('success', 'Funding source deleted successfully.');
    }
}
