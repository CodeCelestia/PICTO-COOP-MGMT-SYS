<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\ExternalSupport;
use App\Models\FinancialRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExternalSupportController extends Controller
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
        $query = ExternalSupport::with(['cooperative', 'financialRecord']);

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('provider_name', 'like', "%{$search}%");
        }

        if ($request->filled('support_type')) {
            $query->where('support_type', $request->support_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $supports = $query->latest()->paginate($perPage)->withQueryString();

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id')
            ->orderBy('period', 'desc');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $financialQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('ExternalSupports/Index', [
            'supports' => $supports,
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
            'filters' => $request->only(['search', 'support_type', 'status', 'coop_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id')
            ->orderBy('period', 'desc');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $financialQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('ExternalSupports/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'financial_record_id' => ['nullable', 'exists:financial_records,id'],
            'support_type' => ['required', Rule::in(['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date_granted' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date', 'after_or_equal:date_granted'],
            'status' => ['required', Rule::in(['Ongoing', 'Completed', 'Pending'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->enforceCoopScope((int) $validated['coop_id']);

        if (!empty($validated['financial_record_id'])) {
            $record = FinancialRecord::find($validated['financial_record_id']);
            if ($record && $record->coop_id !== (int) $validated['coop_id']) {
                return back()->withErrors(['financial_record_id' => 'Selected financial record does not match the cooperative.']);
            }
        }

        ExternalSupport::create($validated);

        return redirect()->route('external-supports.index')
            ->with('success', 'External support record added successfully.');
    }

    public function edit(ExternalSupport $externalSupport): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($externalSupport->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id')
            ->orderBy('period', 'desc');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $financialQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('ExternalSupports/Edit', [
            'support' => $externalSupport->load(['cooperative', 'financialRecord']),
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
        ]);
    }

    public function update(Request $request, ExternalSupport $externalSupport): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'coop_id' => ['required', 'exists:cooperatives,id'],
            'financial_record_id' => ['nullable', 'exists:financial_records,id'],
            'support_type' => ['required', Rule::in(['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date_granted' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date', 'after_or_equal:date_granted'],
            'status' => ['required', Rule::in(['Ongoing', 'Completed', 'Pending'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->enforceCoopScope((int) $validated['coop_id']);

        if (!empty($validated['financial_record_id'])) {
            $record = FinancialRecord::find($validated['financial_record_id']);
            if ($record && $record->coop_id !== (int) $validated['coop_id']) {
                return back()->withErrors(['financial_record_id' => 'Selected financial record does not match the cooperative.']);
            }
        }

        $externalSupport->update($validated);

        return redirect()->route('external-supports.index')
            ->with('success', 'External support record updated successfully.');
    }

    public function destroy(ExternalSupport $externalSupport): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($externalSupport->coop_id);

        $externalSupport->delete();

        return redirect()->route('external-supports.index')
            ->with('success', 'External support record deleted successfully.');
    }
}
