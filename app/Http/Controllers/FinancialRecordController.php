<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\FinancialRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinancialRecordController extends Controller
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
        $query = FinancialRecord::with('cooperative');

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('period', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $query->where('coop_id', $request->coop_id);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $records = $query->latest()->paginate($perPage)->withQueryString();

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('FinancialRecords/Index', [
            'records' => $records,
            'cooperatives' => $cooperativesQuery->get(),
            'filters' => $request->only(['search', 'type', 'coop_id', 'per_page']),
        ]);
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Financial Records',
            'description' => 'Select a cooperative to view financial records.',
            'targetUrl' => '/financial-records',
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
        $isCoopContext = request()->routeIs('cooperatives.finance.financial-records.*');
        $coopContext = $isCoopContext ? request()->route('cooperative') : null;

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('FinancialRecords/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
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
            'period' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'date_recorded' => ['nullable', 'date'],
            'total_assets' => ['nullable', 'numeric', 'min:0'],
            'total_liabilities' => ['nullable', 'numeric', 'min:0'],
            'net_surplus' => ['nullable', 'numeric'],
            'capital_build_up' => ['nullable', 'numeric', 'min:0'],
            'external_assistance_received' => ['nullable', 'numeric', 'min:0'],
            'type_of_assistance' => ['nullable', Rule::in(['Grant', 'Loan', 'Training', 'Equipment', 'Technical Assistance', 'Other'])],
            'reference_doc' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        $validated['recorded_by'] = auth()->user()?->name;

        FinancialRecord::create($validated);

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'Financial record created successfully.');
        }

        if (request()->routeIs('cooperatives.finance.financial-records.*')) {
            $cooperative = request()->route('cooperative');
            return redirect()->route('cooperatives.finance.financial-records.index', ['cooperative' => $cooperative->id])->with('success', 'Financial record created successfully.');
        }

        return redirect()->route('financial-records.index')->with('success', 'Financial record created successfully.');
    }

    public function edit(FinancialRecord $financialRecord): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($financialRecord->coop_id);
        $isCoopContext = request()->routeIs('cooperatives.finance.financial-records.*');
        $coopContext = $isCoopContext ? request()->route('cooperative') : null;

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

        return Inertia::render('FinancialRecords/Edit', [
            'record' => $financialRecord->load('cooperative'),
            'cooperatives' => $cooperativesQuery->get(),
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
        ]);
    }

    public function update(Request $request, FinancialRecord $financialRecord): RedirectResponse
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
            'period' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'date_recorded' => ['nullable', 'date'],
            'total_assets' => ['nullable', 'numeric', 'min:0'],
            'total_liabilities' => ['nullable', 'numeric', 'min:0'],
            'net_surplus' => ['nullable', 'numeric'],
            'capital_build_up' => ['nullable', 'numeric', 'min:0'],
            'external_assistance_received' => ['nullable', 'numeric', 'min:0'],
            'type_of_assistance' => ['nullable', Rule::in(['Grant', 'Loan', 'Training', 'Equipment', 'Technical Assistance', 'Other'])],
            'reference_doc' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->isCoopAdmin() && $coopId) {
            $validated['coop_id'] = $coopId;
        }

        $this->enforceCoopScope((int) $validated['coop_id']);

        $validated['recorded_by'] = auth()->user()?->name;

        $financialRecord->update($validated);

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'Financial record updated successfully.');
        }

        if (request()->routeIs('cooperatives.finance.financial-records.*')) {
            $cooperative = request()->route('cooperative');
            return redirect()->route('cooperatives.finance.financial-records.index', ['cooperative' => $cooperative->id])->with('success', 'Financial record updated successfully.');
        }

        return redirect()->route('financial-records.index')->with('success', 'Financial record updated successfully.');
    }

    public function destroy(FinancialRecord $financialRecord): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($financialRecord->coop_id);

        $financialRecord->delete();

        if (request()->routeIs('cooperatives.finance.financial-records.*')) {
            $cooperative = request()->route('cooperative');
            return redirect()->route('cooperatives.finance.financial-records.index', ['cooperative' => $cooperative->id])->with('success', 'Financial record deleted successfully.');
        }

        return redirect()->route('financial-records.index')->with('success', 'Financial record deleted successfully.');
    }
}
