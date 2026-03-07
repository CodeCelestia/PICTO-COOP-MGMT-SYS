<?php

namespace App\Http\Controllers\SdnAdmin;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SdnPdsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $pds = PersonalDataSheet::whereHas('office', fn ($q) => $q->where('sdn_id', $user->sdn_id))
            ->with(['office:id,name', 'user:id,name,email,status'])
            ->when($request->search, fn ($q) => $q->where(function ($inner) use ($request) {
                $inner->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->when($request->office_id, fn ($q) => $q->where('office_id', $request->office_id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $offices = \App\Models\Office::where('sdn_id', $user->sdn_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('sdn-admin/PDS/Index', [
            'pdsRecords' => $pds,
            'offices'    => $offices,
            'filters'    => $request->only(['search', 'office_id']),
        ]);
    }

    public function show(Request $request, PersonalDataSheet $pds): Response
    {
        $this->authorize('view', $pds);

        return Inertia::render('sdn-admin/PDS/Show', [
            'pds' => $pds->load(['office:id,name', 'user:id,name,email']),
        ]);
    }

    public function edit(Request $request, PersonalDataSheet $pds): Response
    {
        $this->authorize('update', $pds);

        return Inertia::render('sdn-admin/PDS/Edit', [
            'pds' => $pds->load('office:id,name'),
        ]);
    }

    public function update(Request $request, PersonalDataSheet $pds)
    {
        $this->authorize('update', $pds);

        $validated = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'middle_name'   => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender'        => 'required|string|max:20',
            'email'         => 'required|email|max:255',
            'phone_number'  => 'nullable|string|max:20',
        ]);

        $pds->update($validated);

        activity('pds')
            ->causedBy($request->user())
            ->performedOn($pds)
            ->withProperties(['sdn_id' => $request->user()->sdn_id])
            ->log('pds.updated');

        return redirect()->route('sdn-admin.pds.index')
            ->with('success', 'PDS record updated successfully.');
    }

    public function destroy(Request $request, PersonalDataSheet $pds)
    {
        $this->authorize('delete', $pds);

        $pds->delete();

        activity('pds')
            ->causedBy($request->user())
            ->log('pds.deleted');

        return redirect()->route('sdn-admin.pds.index')
            ->with('success', 'PDS record deleted.');
    }
}
