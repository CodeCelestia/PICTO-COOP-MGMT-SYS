<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Cooperative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccreditationController extends Controller
{
    public function index(Cooperative $cooperative)
    {
        if (!request()->user()?->can('update coop-master-profile')) {
            abort(403);
        }

        return response()->json([
            'data' => $cooperative->accreditations()
                ->orderByDesc('date_granted')
                ->get(['id', 'cooperative_id', 'level', 'date_granted', 'valid_until', 'issuing_body', 'remarks', 'created_at', 'updated_at']),
        ]);
    }

    public function store(Request $request, Cooperative $cooperative): RedirectResponse
    {
        if (!$request->user()?->can('update coop-master-profile')) {
            abort(403);
        }

        $validated = $request->validate([
            'level' => ['required', 'string', 'max:255'],
            'date_granted' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'issuing_body' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        Accreditation::create([
            'cooperative_id' => $cooperative->id,
            'level' => $validated['level'],
            'date_granted' => $validated['date_granted'],
            'valid_until' => $validated['valid_until'] ?? null,
            'issuing_body' => $validated['issuing_body'] ?? 'CDA',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Accreditation added successfully.');
    }

    public function update(Request $request, Cooperative $cooperative, Accreditation $accreditation): RedirectResponse
    {
        if (!$request->user()?->can('update coop-master-profile')) {
            abort(403);
        }

        if ((int) $accreditation->cooperative_id !== (int) $cooperative->id) {
            abort(404);
        }

        $validated = $request->validate([
            'level' => ['required', 'string', 'max:255'],
            'date_granted' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'issuing_body' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $accreditation->update([
            'level' => $validated['level'],
            'date_granted' => $validated['date_granted'],
            'valid_until' => $validated['valid_until'] ?? null,
            'issuing_body' => $validated['issuing_body'] ?? 'CDA',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Accreditation updated successfully.');
    }

    public function destroy(Request $request, Cooperative $cooperative, Accreditation $accreditation): RedirectResponse
    {
        if (!$request->user()?->can('update coop-master-profile')) {
            abort(403);
        }

        if ((int) $accreditation->cooperative_id !== (int) $cooperative->id) {
            abort(404);
        }

        $accreditation->delete();

        return back()->with('success', 'Accreditation deleted successfully.');
    }
}
