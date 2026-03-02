<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::query()->with(['office', 'organizer'])->withCount('activityParticipants');

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $activities = $query->latest('activity_date')->paginate(20)->withQueryString();

        return Inertia::render('super-admin/Activities/Index', [
            'activities' => $activities,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['office_id', 'status', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('super-admin/Activities/Create', [
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:training,seminar,meeting,general_assembly,social,project,other',
            'activity_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'expected_participants' => 'nullable|integer|min:0',
            'status' => 'required|in:planned,ongoing,completed,cancelled',
            'objectives' => 'nullable|string',
        ]);

        $validated['organized_by'] = $request->user()->id;

        $activity = Activity::create($validated);

        activity('activity_management')
            ->causedBy($request->user())
            ->performedOn($activity)
            ->log('super_admin.created_activity');

        return redirect()->route('super-admin.activities.show', $activity);
    }

    public function show(Activity $activity): Response
    {
        $activity->load(['office', 'organizer', 'activityParticipants.member.pds']);

        return Inertia::render('super-admin/Activities/Show', [
            'activity' => $activity,
        ]);
    }

    public function edit(Activity $activity): Response
    {
        return Inertia::render('super-admin/Activities/Edit', [
            'activity' => $activity,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:training,seminar,meeting,general_assembly,social,project,other',
            'activity_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'expected_participants' => 'nullable|integer|min:0',
            'actual_participants' => 'nullable|integer|min:0',
            'status' => 'required|in:planned,ongoing,completed,cancelled',
            'objectives' => 'nullable|string',
            'outcomes' => 'nullable|string',
        ]);

        $activity->update($validated);

        activity('activity_management')
            ->causedBy($request->user())
            ->performedOn($activity)
            ->log('super_admin.updated_activity');

        return redirect()->route('super-admin.activities.show', $activity);
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        activity('activity_management')
            ->causedBy(request()->user())
            ->performedOn($activity)
            ->log('super_admin.deleted_activity');

        $activity->delete();

        return redirect()->route('super-admin.activities.index');
    }
}
