<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\Cooperative;
use App\Models\Officer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
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

        if ($request->input('status') === 'Archived') {
            $query->onlyTrashed();
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $activities = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'category', 'coop_id', 'per_page']),
        ]);
    }

    public function report(int $id)
    {
        $activity = Activity::withTrashed()
            ->with(['cooperative', 'responsibleOfficer.member'])
            ->findOrFail($id);

        $this->enforceCoopScope($activity->coop_id);

        $pdf = Pdf::loadView('reports.activity-report', [
            'activity' => $activity,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(Str::slug($activity->title, '-') . '-activity-report.pdf');
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Activities & Projects',
            'description' => 'Select a cooperative to view activities and projects.',
            'targetUrl' => '/activities',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
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
        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region')->orderBy('name');
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
                ? ['nullable', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['nullable', 'exists:cooperatives,id'],
            'coop_ids' => ['nullable', 'array'],
            'coop_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['integer', 'exists:cooperatives,id'],
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
            'outcomes_attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'remarks' => ['nullable', 'string'],
            'funding_sources' => ['nullable', 'array'],
            'funding_sources.*.funder_name' => ['required', 'string', 'max:255'],
            'funding_sources.*.funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'funding_sources.*.amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.amount_released' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.date_released' => ['nullable', 'date'],
            'funding_sources.*.status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'funding_sources.*.remarks' => ['nullable', 'string'],
            'funding_sources.*.attachments' => ['nullable', 'array', 'max:3'],
            'funding_sources.*.attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'funding_sources.*.attachments_removed' => ['nullable', 'array'],
            'funding_sources.*.attachments_removed.*' => ['string'],
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

        if (!empty($validated['responsible_officer_id'])) {
            if ($selectedCoopIds->count() > 1) {
                return back()->withErrors([
                    'responsible_officer_id' => 'Responsible officer can only be set when one cooperative is selected.',
                ])->withInput();
            }

            $officer = Officer::find($validated['responsible_officer_id']);
            if ($officer && $officer->coop_id !== $selectedCoopIds->first()) {
                return back()->withErrors(['responsible_officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        if ($request->hasFile('outcomes_attachment')) {
            $validated['outcomes_attachment_path'] = $request->file('outcomes_attachment')
                ->store('activity-outcomes-attachments', 'public');
        }

        $fundingSources = $validated['funding_sources'] ?? [];
        foreach ($fundingSources as $index => $source) {
            if ($request->hasFile("funding_sources.{$index}.attachments")) {
                $paths = [];
                $names = [];
                foreach ($request->file("funding_sources.{$index}.attachments") as $file) {
                    $paths[] = $file->store('funding-source-attachments', 'public');
                    $names[] = $file->getClientOriginalName();
                }
                $fundingSources[$index]['attachment_paths'] = $paths;
                $fundingSources[$index]['attachment_names'] = $names;
            }
        }
        unset($validated['funding_sources'], $validated['coop_ids']);

        $createdCount = 0;

        foreach ($selectedCoopIds as $selectedCoopId) {
            $this->enforceCoopScope((int) $selectedCoopId);

            $payload = $validated;
            $payload['coop_id'] = (int) $selectedCoopId;

            if ($selectedCoopIds->count() > 1) {
                $payload['responsible_officer_id'] = null;
            }

            $activity = Activity::create($payload);
            $createdCount++;

            foreach ($fundingSources as $source) {
                $activity->fundingSources()->create([
                    'coop_id' => $activity->coop_id,
                    'funder_name' => $source['funder_name'],
                    'funder_type' => $source['funder_type'],
                    'amount_allocated' => $source['amount_allocated'] ?? null,
                    'amount_released' => $source['amount_released'] ?? null,
                    'date_released' => $source['date_released'] ?? null,
                    'status' => $source['status'],
                    'remarks' => $source['remarks'] ?? null,
                    'attachment_paths' => $source['attachment_paths'] ?? null,
                    'attachment_names' => $source['attachment_names'] ?? null,
                ]);
            }
        }

        $successMessage = $createdCount > 1
            ? "Activities created successfully for {$createdCount} cooperatives."
            : 'Activity created successfully.';

        return redirect()->route('activities.index')
            ->with('success', $successMessage);
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
            'activity' => $activity->load(['cooperative', 'responsibleOfficer.member', 'fundingSources']),
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
            'outcomes_attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'outcomes_attachment_removed' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
            'funding_sources' => ['nullable', 'array'],
            'funding_sources.*.id' => ['nullable', 'integer'],
            'funding_sources.*.funder_name' => ['required', 'string', 'max:255'],
            'funding_sources.*.funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'funding_sources.*.amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.amount_released' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.date_released' => ['nullable', 'date'],
            'funding_sources.*.status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'funding_sources.*.remarks' => ['nullable', 'string'],
            'funding_sources.*.attachments' => ['nullable', 'array', 'max:3'],
            'funding_sources.*.attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'funding_sources.*.attachments_removed' => ['nullable', 'array'],
            'funding_sources.*.attachments_removed.*' => ['string'],
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

        if ($request->hasFile('outcomes_attachment')) {
            if (!empty($activity->outcomes_attachment_path)) {
                Storage::disk('public')->delete($activity->outcomes_attachment_path);
            }
            $validated['outcomes_attachment_path'] = $request->file('outcomes_attachment')
                ->store('activity-outcomes-attachments', 'public');
        } elseif (!empty($validated['outcomes_attachment_removed'])) {
            if (!empty($activity->outcomes_attachment_path)) {
                Storage::disk('public')->delete($activity->outcomes_attachment_path);
            }
            $validated['outcomes_attachment_path'] = null;
        }

        $fundingSources = $validated['funding_sources'] ?? [];
        foreach ($fundingSources as $index => $source) {
            if ($request->hasFile("funding_sources.{$index}.attachments")) {
                $paths = [];
                $names = [];
                foreach ($request->file("funding_sources.{$index}.attachments") as $file) {
                    $paths[] = $file->store('funding-source-attachments', 'public');
                    $names[] = $file->getClientOriginalName();
                }
                $fundingSources[$index]['attachment_paths'] = $paths;
                $fundingSources[$index]['attachment_names'] = $names;
            }
        }
        unset($validated['funding_sources']);

        $activity->update($validated);

        $incomingIds = collect($fundingSources)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        ActivityFundingSource::where('activity_id', $activity->id)
            ->whereNotIn('id', $incomingIds)
            ->delete();

        foreach ($fundingSources as $source) {
            $payload = [
                'coop_id' => $activity->coop_id,
                'funder_name' => $source['funder_name'],
                'funder_type' => $source['funder_type'],
                'amount_allocated' => $source['amount_allocated'] ?? null,
                'amount_released' => $source['amount_released'] ?? null,
                'date_released' => $source['date_released'] ?? null,
                'status' => $source['status'],
                'remarks' => $source['remarks'] ?? null,
            ];

            if (array_key_exists('attachment_paths', $source)) {
                $payload['attachment_paths'] = $source['attachment_paths'];
                $payload['attachment_names'] = $source['attachment_names'] ?? [];
            }

            if (!empty($source['id'])) {
                $fundingSource = ActivityFundingSource::where('id', (int) $source['id'])
                    ->where('activity_id', $activity->id)
                    ->first();

                if ($fundingSource) {
                    $attachmentPaths = $fundingSource->attachment_paths ?? [];
                    $attachmentNames = $fundingSource->attachment_names ?? [];

                    if (!empty($source['attachments_removed'])) {
                        foreach ($source['attachments_removed'] as $removedPath) {
                            $index = array_search($removedPath, $attachmentPaths, true);
                            if ($index !== false) {
                                Storage::disk('public')->delete($attachmentPaths[$index]);
                                array_splice($attachmentPaths, $index, 1);
                                array_splice($attachmentNames, $index, 1);
                            }
                        }
                    }

                    if (!empty($source['attachment_paths'])) {
                        $attachmentPaths = array_merge($attachmentPaths, $source['attachment_paths']);
                        $attachmentNames = array_merge($attachmentNames, $source['attachment_names'] ?? []);
                    }

                    $payload['attachment_paths'] = $attachmentPaths;
                    $payload['attachment_names'] = $attachmentNames;

                    $fundingSource->update($payload);
                }
            } else {
                if (!empty($source['attachment_paths'])) {
                    $payload['attachment_paths'] = $source['attachment_paths'];
                    $payload['attachment_names'] = $source['attachment_names'] ?? [];
                }

                $activity->fundingSources()->create($payload);
            }
        }

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

    public function restore(int $id): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $activity = Activity::withTrashed()->findOrFail($id);
        $this->enforceCoopScope($activity->coop_id);
        $activity->restore();

        return redirect()->route('activities.index')
            ->with('success', 'Activity restored successfully.');
    }
}
