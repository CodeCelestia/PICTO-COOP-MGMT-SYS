<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\SkillInventory;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SkillInventoryController extends Controller
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

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = SkillInventory::with(['member', 'training', 'cooperative']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('skill_name', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('proficiency_level')) {
            $query->where('proficiency_level', $request->proficiency_level);
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', $request->training_id);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $skills = $query->latest()->paginate(15)->withQueryString();

        $trainingsQuery = Training::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('SkillInventories/Index', [
            'skills' => $skills,
            'trainings' => $trainingsQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'proficiency_level', 'training_id', 'coop_id']),
        ]);
    }

    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $trainingsQuery = Training::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('SkillInventories/Create', [
            'trainings' => $trainingsQuery->get(),
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

    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'skill_name' => ['required', 'string', 'max:255'],
            'proficiency_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'training_id' => ['required', 'exists:trainings,id'],
            'date_acquired' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->enforceCoopScope((int) $validated['coop_id']);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $training = Training::find($validated['training_id']);
        if ($training && $training->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['training_id' => 'Selected training does not belong to this cooperative.']);
        }

        $validated['last_updated'] = now();

        SkillInventory::create($validated);

        return redirect()->route('skill-inventories.index')
            ->with('success', 'Skill inventory record added successfully.');
    }

    public function edit(SkillInventory $skillInventory): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($skillInventory->coop_id);

        $trainingsQuery = Training::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('SkillInventories/Edit', [
            'skill' => $skillInventory->load(['member', 'training', 'cooperative']),
            'trainings' => $trainingsQuery->get(),
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

    public function update(Request $request, SkillInventory $skillInventory): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'skill_name' => ['required', 'string', 'max:255'],
            'proficiency_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'training_id' => ['required', 'exists:trainings,id'],
            'date_acquired' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->enforceCoopScope((int) $validated['coop_id']);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        $training = Training::find($validated['training_id']);
        if ($training && $training->coop_id !== (int) $validated['coop_id']) {
            return back()->withErrors(['training_id' => 'Selected training does not belong to this cooperative.']);
        }

        $validated['last_updated'] = now();

        $skillInventory->update($validated);

        return redirect()->route('skill-inventories.index')
            ->with('success', 'Skill inventory record updated successfully.');
    }

    public function destroy(SkillInventory $skillInventory): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($skillInventory->coop_id);

        $skillInventory->delete();

        return redirect()->route('skill-inventories.index')
            ->with('success', 'Skill inventory record deleted successfully.');
    }
}
