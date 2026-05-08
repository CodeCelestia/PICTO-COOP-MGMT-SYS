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

    private function resolveCooperative($param): ?\App\Models\Cooperative
    {
        if ($param instanceof \App\Models\Cooperative) {
            return $param;
        }
        if ($param === 'my') {
            return \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
        }
        if (is_numeric($param)) {
            return \App\Models\Cooperative::find($param);
        }
        return null;
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

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);

        if (!$cooperative && $request->filled('coop_id')) {
            $cooperative = \App\Models\Cooperative::find($request->coop_id);
        }

        $isCoopContext = $cooperative !== null;

        return Inertia::render('ExternalSupports/Index', [
            'supports' => $supports,
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
            'filters' => $request->only(['search', 'support_type', 'status', 'coop_id', 'per_page']),
            'cooperative' => $cooperative,
            'isCoopContext' => $isCoopContext,
        ]);
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'External Supports',
            'description' => 'Select a cooperative to view external supports.',
            'targetUrl' => '/external-supports',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): Response
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

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);
        $isCoopContext = $cooperative !== null;

        return Inertia::render('ExternalSupports/Create', [
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
            'cooperative' => $cooperative,
            'isCoopContext' => $isCoopContext,
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

        $externalSupport = ExternalSupport::create($validated);

        $typeMap = [
            'Grant'                => 'Grant',
            'Loan'                 => 'Loan',
            'Equipment'            => 'Support',
            'Training'             => 'Support',
            'Technical Assistance' => 'Support',
            'Other'                => 'Support',
        ];

        $financialRecord = \App\Models\FinancialRecord::create([
            'coop_id'                      => $externalSupport->coop_id,
            'period'                       => now()->format('Y-m'),
            'type'                         => $typeMap[$externalSupport->support_type] ?? 'Support',
            'amount'                       => $externalSupport->amount,
            'source'                       => $externalSupport->provider_name,
            'purpose'                      => 'External support received: ' . $externalSupport->support_type,
            'date_recorded'                => $externalSupport->date_granted ?? now()->toDateString(),
            'external_assistance_received' => $externalSupport->amount,
            'type_of_assistance'           => $externalSupport->support_type,
            'reference_doc'                => 'external_support_' . $externalSupport->id,
            'recorded_by'                  => auth()->user()?->name,
            'origin'                       => 'external_support',
        ]);

        $externalSupport->update(['financial_record_id' => $financialRecord->id]);

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);

        if (!$cooperative && $request->filled('coop_id')) {
            $cooperative = \App\Models\Cooperative::find($request->coop_id);
        }

        if ($cooperative && $request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance")
                ->with('success', 'External support record added successfully.');
        }

        if ($cooperative) {
            return redirect()
                ->to("/finance/external-supports?coop_id={$cooperative->id}")
                ->with('success', 'External support record added successfully.');
        }

        return redirect()
            ->to('/finance/external-supports')
            ->with('success', 'External support record added successfully.');
    }

    public function edit(ExternalSupport $externalSupport, Request $request): Response
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

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);
        $isCoopContext = $cooperative !== null;

        return Inertia::render('ExternalSupports/Edit', [
            'support' => $externalSupport->load(['cooperative', 'financialRecord']),
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
            'cooperative' => $cooperative,
            'isCoopContext' => $isCoopContext,
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

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);

        if (!$cooperative && $request->filled('coop_id')) {
            $cooperative = \App\Models\Cooperative::find($request->coop_id);
        }

        if ($cooperative && $request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance")
                ->with('success', 'External support record updated successfully.');
        }

        if ($cooperative) {
            return redirect()
                ->to("/finance/external-supports?coop_id={$cooperative->id}")
                ->with('success', 'External support record updated successfully.');
        }

        return redirect()
            ->to('/finance/external-supports')
            ->with('success', 'External support record updated successfully.');
    }

    public function destroy(ExternalSupport $externalSupport): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($externalSupport->coop_id);

        if ($externalSupport->financial_record_id) {
            \App\Models\FinancialRecord::where('id', $externalSupport->financial_record_id)
                ->delete();
        }

        $externalSupport->delete();

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);

        if (!$cooperative && $request->filled('coop_id')) {
            $cooperative = \App\Models\Cooperative::find($request->coop_id);
        }

        if ($cooperative && $request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance")
                ->with('success', 'External support record deleted successfully.');
        }

        if ($cooperative) {
            return redirect()
                ->to("/finance/external-supports?coop_id={$cooperative->id}")
                ->with('success', 'External support record deleted successfully.');
        }

        return redirect()
            ->to('/finance/external-supports')
            ->with('success', 'External support record deleted successfully.');
    }
}
