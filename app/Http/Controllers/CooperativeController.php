<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\CooperativeStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CooperativeController extends Controller
{
    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Provincial Admin') || $user->account_type === 'Provincial Admin')
            : false;
    }

    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Coop Admin') || $user->account_type === 'Coop Admin')
            : false;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Cooperative::query();

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $query->where('id', $user->coop_id);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Coop Type filter
        if ($request->filled('coop_type')) {
            $query->where('coop_type', $request->coop_type);
        }

        // Province filter
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        $cooperatives = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Cooperatives/Index', [
            'cooperatives' => $cooperatives,
            'filters' => $request->only(['search', 'status', 'coop_type', 'province']),
        ]);
    }

    public function create()
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        return Inertia::render('Cooperatives/Create');
    }

    public function store(Request $request)
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives',
            'coop_type' => 'required|string|max:255',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive,Dissolved,Suspended',
            'accreditation_status' => 'nullable|string|max:255',
            'accreditation_date' => 'nullable|date',
        ]);

        $cooperative = Cooperative::create($validated);

        CooperativeStatusHistory::create([
            'coop_id' => $cooperative->id,
            'previous_status' => null,
            'new_status' => $cooperative->status,
            'change_reason' => 'Initial registration',
            'changed_by' => auth()->user()?->name ?? 'System',
            'changed_at' => now(),
            'remarks' => 'Initial cooperative status set during creation.',
        ]);

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative created successfully.');
    }

    public function edit(Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Cooperatives/Edit', [
            'cooperative' => $cooperative,
            'statusHistory' => $cooperative->statusHistory()
                ->latest('changed_at')
                ->get()
                ->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'previous_status' => $history->previous_status,
                        'new_status' => $history->new_status,
                        'change_reason' => $history->change_reason,
                        'changed_by' => $history->changed_by,
                        'changed_at' => optional($history->changed_at)->toDateTimeString(),
                        'remarks' => $history->remarks,
                    ];
                }),
        ]);
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if ($this->isCoopAdmin() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives,registration_number,' . $cooperative->id,
            'coop_type' => 'required|string|max:255',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive,Dissolved,Suspended',
            'accreditation_status' => 'nullable|string|max:255',
            'accreditation_date' => 'nullable|date',
            'change_reason' => 'nullable|string|max:500',
            'status_remarks' => 'nullable|string|max:500',
        ]);

        if ($request->input('status') !== $cooperative->status) {
            $validator->sometimes('change_reason', ['required', 'string', 'max:500'], fn () => true);
        }

        $validated = $validator->validate();

        $previousStatus = $cooperative->status;
        $newStatus = $validated['status'];
        $changeReason = $validated['change_reason'] ?? null;
        $statusRemarks = $validated['status_remarks'] ?? null;
        unset($validated['change_reason'], $validated['status_remarks']);

        $cooperative->update($validated);

        if ($previousStatus !== $newStatus) {
            CooperativeStatusHistory::create([
                'coop_id' => $cooperative->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'change_reason' => $changeReason,
                'changed_by' => auth()->user()?->name ?? 'System',
                'changed_at' => now(),
                'remarks' => $statusRemarks,
            ]);
        }

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative updated successfully.');
    }

    public function destroy(Cooperative $cooperative)
    {
        if ($this->isCoopAdmin()) {
            abort(403);
        }

        $cooperative->delete();

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative deleted successfully.');
    }
}
