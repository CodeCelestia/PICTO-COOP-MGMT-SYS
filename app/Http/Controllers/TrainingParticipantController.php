<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Officer;
use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainingParticipantController extends Controller
{
    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Coop Admin') : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Provincial Admin') : false;
    }

    private function isOfficer(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasRole('Officer') : false;
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
        $query = TrainingParticipant::with(['training.cooperative', 'member', 'officer.member']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->whereHas('training', function ($q) use ($user) {
                $q->where('coop_id', $user->coop_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('training', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', $request->training_id);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->whereHas('training', function ($q) use ($request) {
                $q->where('coop_id', $request->coop_id);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $participants = $query->latest()->paginate($perPage)->withQueryString();

        $trainingsQuery = Training::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('TrainingParticipants/Index', [
            'participants' => $participants,
            'trainings' => $trainingsQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'training_id', 'coop_id', 'per_page']),
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
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('TrainingParticipants/Create', [
            'trainings' => $trainingsQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
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
            'training_id' => ['required', 'exists:trainings,id'],
            'member_id' => ['required', 'exists:members,id'],
            'officer_id' => ['nullable', 'exists:officers,id'],
            'outcome' => ['nullable', Rule::in(['Passed', 'Failed', 'Incomplete', 'No Assessment'])],
            'certificate_no' => ['nullable', 'string', 'max:255'],
            'certificate_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $training = Training::find($validated['training_id']);
        if (!$training) {
            return back()->withErrors(['training_id' => 'Selected training not found.']);
        }

        $this->enforceCoopScope($training->coop_id);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $training->coop_id) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        if (!empty($validated['officer_id'])) {
            $officer = Officer::find($validated['officer_id']);
            if ($officer && $officer->coop_id !== (int) $training->coop_id) {
                return back()->withErrors(['officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        TrainingParticipant::create($validated);

        return redirect()->route('training-participants.index')
            ->with('success', 'Training participant added successfully.');
    }

    public function edit(TrainingParticipant $trainingParticipant): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $trainingParticipant->load(['training', 'member', 'officer.member']);
        $this->enforceCoopScope($trainingParticipant->training->coop_id);

        $trainingsQuery = Training::select('id', 'title', 'coop_id')->orderBy('title');
        $membersQuery = Member::select('id', 'first_name', 'last_name', 'coop_id')->orderBy('last_name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $trainingsQuery->where('coop_id', $user->coop_id);
            $membersQuery->where('coop_id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('TrainingParticipants/Edit', [
            'participant' => $trainingParticipant,
            'trainings' => $trainingsQuery->get(),
            'members' => $membersQuery->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'coop_id' => $member->coop_id,
                ];
            }),
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
            'cooperatives' => $cooperativesQuery->get(),
        ]);
    }

    public function update(Request $request, TrainingParticipant $trainingParticipant): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'member_id' => ['required', 'exists:members,id'],
            'officer_id' => ['nullable', 'exists:officers,id'],
            'outcome' => ['nullable', Rule::in(['Passed', 'Failed', 'Incomplete', 'No Assessment'])],
            'certificate_no' => ['nullable', 'string', 'max:255'],
            'certificate_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $training = Training::find($validated['training_id']);
        if (!$training) {
            return back()->withErrors(['training_id' => 'Selected training not found.']);
        }

        $this->enforceCoopScope($training->coop_id);

        $member = Member::find($validated['member_id']);
        if ($member && $member->coop_id !== (int) $training->coop_id) {
            return back()->withErrors(['member_id' => 'Selected member does not belong to this cooperative.']);
        }

        if (!empty($validated['officer_id'])) {
            $officer = Officer::find($validated['officer_id']);
            if ($officer && $officer->coop_id !== (int) $training->coop_id) {
                return back()->withErrors(['officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        $trainingParticipant->update($validated);

        return redirect()->route('training-participants.index')
            ->with('success', 'Training participant updated successfully.');
    }

    public function destroy(TrainingParticipant $trainingParticipant): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $trainingParticipant->load('training');
        $this->enforceCoopScope($trainingParticipant->training->coop_id);

        $trainingParticipant->delete();

        return redirect()->route('training-participants.index')
            ->with('success', 'Training participant removed successfully.');
    }
}
