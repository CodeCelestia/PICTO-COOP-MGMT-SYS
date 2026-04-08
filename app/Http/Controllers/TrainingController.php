<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
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

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = Training::with('cooperative');

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('facilitator', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('target_group')) {
            $query->where('target_group', $request->target_group);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $trainings = $query->latest()->paginate($perPage)->withQueryString();

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('Trainings/Index', [
            'trainings' => $trainings,
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'status', 'target_group', 'coop_id', 'per_page']),
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

        return Inertia::render('Trainings/Create', [
            'cooperatives' => $cooperativesQuery->get(),
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
                ? ['required', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['required', 'exists:cooperatives,id'],
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
            'status' => ['required', Rule::in(['Planned', 'Completed', 'Cancelled', 'Follow-Up Pending'])],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $validated['follow_up_needed'] = (bool) ($validated['follow_up_needed'] ?? false);
        if (!$validated['follow_up_needed']) {
            $validated['follow_up_date'] = null;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        Training::create($validated);

        return redirect()->route('trainings.index')
            ->with('success', 'Training record created successfully.');
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

        return Inertia::render('Trainings/Edit', [
            'training' => $training->load('cooperative'),
            'cooperatives' => $cooperativesQuery->get(),
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
            'status' => ['required', Rule::in(['Planned', 'Completed', 'Cancelled', 'Follow-Up Pending'])],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $validated['follow_up_needed'] = (bool) ($validated['follow_up_needed'] ?? false);
        if (!$validated['follow_up_needed']) {
            $validated['follow_up_date'] = null;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        $training->update($validated);

        return redirect()->route('trainings.index')
            ->with('success', 'Training record updated successfully.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($training->coop_id);

        $training->delete();

        return redirect()->route('trainings.index')
            ->with('success', 'Training record deleted successfully.');
    }
}
