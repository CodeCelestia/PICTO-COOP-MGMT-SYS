<?php

namespace App\Http\Controllers;

use App\Models\LoanType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coopId = $this->resolveCooperativeId($request);

        $loanTypes = LoanType::query()
            ->where('cooperative_id', $coopId)
            ->orderBy('name')
            ->get(['id', 'cooperative_id', 'name', 'description', 'is_active', 'created_at', 'updated_at']);

        return response()->json(['data' => $loanTypes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $coopId = $this->resolveCooperativeId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        LoanType::create([
            'cooperative_id' => $coopId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        return back()->with('success', 'Loan type created successfully.');
    }

    public function update(Request $request, LoanType $loanType): RedirectResponse
    {
        $this->authorizeLoanTypeAccess($request, $loanType);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $loanType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        return back()->with('success', 'Loan type updated successfully.');
    }

    public function destroy(Request $request, LoanType $loanType): RedirectResponse
    {
        $this->authorizeLoanTypeAccess($request, $loanType);

        $loanType->delete();

        return back()->with('success', 'Loan type deleted successfully.');
    }

    private function resolveCooperativeId(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->can('view-all-cooperatives')) {
            $coopId = (int) $request->input('cooperative_id');
            if ($coopId <= 0) {
                abort(422, 'cooperative_id is required.');
            }

            return $coopId;
        }

        if (! $user->coop_id) {
            abort(403);
        }

        return (int) $user->coop_id;
    }

    private function authorizeLoanTypeAccess(Request $request, LoanType $loanType): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->can('view-all-cooperatives')) {
            if ($request->filled('cooperative_id') && (int) $request->input('cooperative_id') !== (int) $loanType->cooperative_id) {
                abort(403);
            }

            return;
        }

        if (! $user->coop_id || (int) $loanType->cooperative_id !== (int) $user->coop_id) {
            abort(403);
        }
    }
}
