<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Committee::query()->with(['office'])->withCount('committeeMembers');

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $committees = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('super-admin/Committees/Index', [
            'committees' => $committees,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['office_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('super-admin/Committees/Create', [
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:committees,code',
            'type' => 'required|in:board_of_directors,audit,credit,education,election,ethics,other',
            'description' => 'nullable|string',
            'term_years' => 'required|integer|min:1',
            'term_start' => 'nullable|date',
            'term_end' => 'nullable|date',
            'status' => 'required|in:active,inactive,dissolved',
            'responsibilities' => 'nullable|string',
        ]);

        $committee = Committee::create($validated);

        activity('committee_management')
            ->causedBy($request->user())
            ->performedOn($committee)
            ->log('super_admin.created_committee');

        return redirect()->route('super-admin.committees.show', $committee);
    }

    public function show(Committee $committee): Response
    {
        $committee->load(['office', 'committeeMembers.member.pds']);

        return Inertia::render('super-admin/Committees/Show', [
            'committee' => $committee,
        ]);
    }

    public function edit(Committee $committee): Response
    {
        return Inertia::render('super-admin/Committees/Edit', [
            'committee' => $committee,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Committee $committee): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:committees,code,'.$committee->id,
            'type' => 'required|in:board_of_directors,audit,credit,education,election,ethics,other',
            'description' => 'nullable|string',
            'term_years' => 'required|integer|min:1',
            'term_start' => 'nullable|date',
            'term_end' => 'nullable|date',
            'status' => 'required|in:active,inactive,dissolved',
            'responsibilities' => 'nullable|string',
        ]);

        $committee->update($validated);

        activity('committee_management')
            ->causedBy($request->user())
            ->performedOn($committee)
            ->log('super_admin.updated_committee');

        return redirect()->route('super-admin.committees.show', $committee);
    }

    public function destroy(Committee $committee): RedirectResponse
    {
        activity('committee_management')
            ->causedBy(request()->user())
            ->performedOn($committee)
            ->log('super_admin.deleted_committee');

        $committee->delete();

        return redirect()->route('super-admin.committees.index');
    }
}
