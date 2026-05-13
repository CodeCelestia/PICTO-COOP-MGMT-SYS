<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\ExternalSupport;
use App\Models\FinancialRecord;
use Illuminate\Http\JsonResponse;
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

    private function resolveContextCoopId(Request $request, ?ExternalSupport $externalSupport = null): ?int
    {
        if ($externalSupport) {
            return (int) $externalSupport->coop_id;
        }

        $cooperative = $this->resolveCooperative($request->route('cooperative'));
        if ($cooperative) {
            return (int) $cooperative->id;
        }

        $user = auth()->user();
        if ($user?->coop_id) {
            return (int) $user->coop_id;
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

        $cooperativesQuery = Cooperative::select([
                'id',
                'name',
                'registration_number',
                'status',
                'classification',
                'city_municipality',
                'province',
                'date_established',
            ])
            ->with([
                'types' => function ($q) {
                    $q->select('cooperative_types.id', 'cooperative_types.name');
                },
                'accreditations' => function ($q) {
                    $q->orderByDesc('date_granted');
                },
            ])
            ->withCount('members')
            ->orderBy('name');
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id', 'amount', 'date_recorded', 'source', 'purpose')
            ->orderBy('period', 'desc');

        if ($request->filled('coop_search')) {
            $coopSearch = (string) $request->input('coop_search');
            $cooperativesQuery->where(function ($searchQuery) use ($coopSearch) {
                $searchQuery->where('name', 'like', "%{$coopSearch}%")
                    ->orWhere('registration_number', 'like', "%{$coopSearch}%");
            });
        }

        if ($request->filled('coop_status')) {
            $cooperativesQuery->where('status', $request->string('coop_status'));
        }

        if ($request->filled('coop_type')) {
            $cooperativesQuery->whereHas('types', function ($builder) use ($request) {
                $builder->where('name', $request->string('coop_type'));
            });
        }

        if ($request->filled('coop_classification')) {
            $cooperativesQuery->where('classification', $request->string('coop_classification'));
        }

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

        $cooperatives = $cooperativesQuery->get()->transform(function ($cooperative) {
            $latestAccreditation = $cooperative->accreditations()
                ->orderByDesc('date_granted')
                ->first(['id', 'cooperative_id', 'level', 'date_granted']);

            $cooperative->setAttribute('latest_accreditation', $latestAccreditation ? [
                'id' => $latestAccreditation->id,
                'cooperative_id' => $latestAccreditation->cooperative_id,
                'level' => $latestAccreditation->level,
                'date_granted' => optional($latestAccreditation->date_granted)->toDateString(),
            ] : null);

            $cooperative->setAttribute('date_established', optional($cooperative->date_established)->toDateString());

            return $cooperative;
        });

        return Inertia::render('Finance/ExternalSupports/Index', [
            'supports' => $supports,
            'cooperatives' => $cooperatives,
            'financialRecords' => $financialQuery->get(),
            'filters' => $request->only(['search', 'support_type', 'status', 'coop_id', 'per_page', 'coop_search', 'coop_status', 'coop_type', 'coop_classification']),
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
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id', 'amount', 'date_recorded', 'source', 'purpose')
            ->orderBy('period', 'desc');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $financialQuery->where('coop_id', $user->coop_id);
        }

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);
        $isCoopContext = $cooperative !== null;

        return Inertia::render('Finance/ExternalSupports/Create', [
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

        $resolvedCoopId = $this->resolveContextCoopId($request);
        if (!$resolvedCoopId) {
            return back()->withErrors(['coop_id' => 'Unable to determine cooperative context for this record.']);
        }

        $validated = $request->validate([
            'financial_record_id' => ['nullable', 'exists:financial_records,id'],
            'support_type' => ['required', Rule::in(['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date_granted' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date', 'after_or_equal:date_granted'],
            'status' => ['required', Rule::in(['Ongoing', 'Completed', 'Pending'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['coop_id'] = $resolvedCoopId;

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

        $safeReturnTo = $this->resolveInternalReturnTo($request);
        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'External support record added successfully.');
        }

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);

        if (!$cooperative && $request->filled('coop_id')) {
            $cooperative = \App\Models\Cooperative::find($request->coop_id);
        }

        if ($cooperative && $request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance&subtab=external-supports")
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

    public function edit(Request $request, Cooperative $cooperative, ExternalSupport $externalSupport): Response
    {
        $user = auth()->user();

        if ($cooperative->id !== $externalSupport->coop_id) {
            abort(404);
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($externalSupport->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name')->orderBy('name');
        $financialQuery = FinancialRecord::select('id', 'period', 'type', 'coop_id', 'amount', 'date_recorded', 'source', 'purpose')
            ->orderBy('period', 'desc');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $financialQuery->where('coop_id', $user->coop_id);
        }

        $cooperativeParam = $request->route('cooperative');
        $cooperative = $this->resolveCooperative($cooperativeParam);
        $isCoopContext = $cooperative !== null;

        return Inertia::render('Finance/ExternalSupports/Edit', [
            'support' => $externalSupport->load(['cooperative', 'financialRecord']),
            'cooperatives' => $cooperativesQuery->get(),
            'financialRecords' => $financialQuery->get(),
            'cooperative' => $cooperative,
            'isCoopContext' => $isCoopContext,
        ]);
    }

    public function update(Request $request, Cooperative $cooperative, ExternalSupport $externalSupport): RedirectResponse
    {
        $user = auth()->user();

        if ($cooperative->id !== $externalSupport->coop_id) {
            abort(404);
        }

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $validated = $request->validate([
            'financial_record_id' => ['nullable', 'exists:financial_records,id'],
            'support_type' => ['required', Rule::in(['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date_granted' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date', 'after_or_equal:date_granted'],
            'status' => ['required', Rule::in(['Ongoing', 'Completed', 'Pending'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['coop_id'] = (int) $externalSupport->coop_id;

        $this->enforceCoopScope((int) $validated['coop_id']);

        if (!empty($validated['financial_record_id'])) {
            $record = FinancialRecord::find($validated['financial_record_id']);
            if ($record && $record->coop_id !== (int) $validated['coop_id']) {
                return back()->withErrors(['financial_record_id' => 'Selected financial record does not match the cooperative.']);
            }
        }

        $externalSupport->update($validated);

        $safeReturnTo = $this->resolveInternalReturnTo($request);
        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'External support record updated successfully.');
        }

        if ($request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance&subtab=external-supports")
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

    public function destroy(Request $request, Cooperative $cooperative, ExternalSupport $externalSupport): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        if ($cooperative->id !== $externalSupport->coop_id) {
            abort(404);
        }

        $this->enforceCoopScope($externalSupport->coop_id);

        if ($externalSupport->financial_record_id) {
            \App\Models\FinancialRecord::where('id', $externalSupport->financial_record_id)
                ->delete();
        }

        $externalSupport->delete();

        if ($request->routeIs('cooperatives.finance.external-supports.*')) {
            return redirect()
                ->to("/cooperatives/{$cooperative->id}?tab=finance&subtab=external-supports")
                ->with('success', 'External support record deleted successfully.');
        }

        return redirect()
            ->to('/finance/external-supports')
            ->with('success', 'External support record deleted successfully.');
    }

    public function financialRecords(Request $request, Cooperative $cooperative): JsonResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope((int) $cooperative->id);

        $excludeIds = array_values(array_filter(array_map('intval', (array) $request->input('exclude_ids', []))));
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(5, min($perPage, 20));

        $query = FinancialRecord::query()
            ->select('id', 'coop_id', 'period', 'type', 'amount', 'date_recorded', 'source', 'purpose')
            ->where('coop_id', $cooperative->id)
            ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('period', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereDate('date_recorded', $search);
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_recorded', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date_recorded', '<=', $request->input('date_to')))
            ->orderByDesc('date_recorded')
            ->orderByDesc('id');

        $records = $query->paginate($perPage)->through(function (FinancialRecord $record) {
            return [
                'id' => $record->id,
                'coop_id' => $record->coop_id,
                'title' => $record->purpose ?: $record->source ?: "Record #{$record->id}",
                'period' => $record->period,
                'type' => $record->type,
                'amount' => $record->amount,
                'date_recorded' => optional($record->date_recorded)->toDateString(),
                'source' => $record->source,
                'purpose' => $record->purpose,
            ];
        });

        return response()->json($records);
    }
}
