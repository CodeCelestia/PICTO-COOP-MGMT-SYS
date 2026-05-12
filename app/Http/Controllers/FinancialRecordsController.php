<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\FinancialRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialRecordsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = FinancialRecord::query()->with('cooperative:id,name,status,classification');
        $cooperative = null;

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('period', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('coop_status')) {
            $query->whereHas('cooperative', function ($builder) use ($request) {
                $builder->where('status', $request->string('coop_status'));
            });
        }

        if ($request->filled('coop_search')) {
            $coopSearch = (string) $request->input('coop_search');
            $query->whereHas('cooperative', function ($builder) use ($coopSearch) {
                $builder->where(function ($searchQuery) use ($coopSearch) {
                    $searchQuery->where('name', 'like', "%{$coopSearch}%")
                        ->orWhere('registration_number', 'like', "%{$coopSearch}%");
                });
            });
        }

        if ($request->filled('coop_classification')) {
            $query->whereHas('cooperative', function ($builder) use ($request) {
                $builder->where('classification', $request->string('coop_classification'));
            });
        }

        if ($request->filled('coop_type')) {
            $query->whereHas('cooperative.types', function ($builder) use ($request) {
                $builder->where('name', $request->string('coop_type'));
            });
        }

        if ($request->filled('coop_id')) {
            $query->where('coop_id', (int) $request->input('coop_id'));
            $cooperative = \App\Models\Cooperative::select(['id', 'name', 'status', 'classification'])->find($request->integer('coop_id'));
        }

        $records = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $cooperativesQuery = \App\Models\Cooperative::query()
            ->select([
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
                'types' => function ($query) {
                    $query->select('cooperative_types.id', 'cooperative_types.name');
                },
                'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                },
            ])
            ->withCount('members')
            ->orderBy('name');

        if ($request->filled('coop_status')) {
            $cooperativesQuery->where('status', $request->string('coop_status'));
        }

        if ($request->filled('coop_search')) {
            $coopSearch = (string) $request->input('coop_search');
            $cooperativesQuery->where(function ($searchQuery) use ($coopSearch) {
                $searchQuery->where('name', 'like', "%{$coopSearch}%")
                    ->orWhere('registration_number', 'like', "%{$coopSearch}%");
            });
        }

        if ($request->filled('coop_classification')) {
            $cooperativesQuery->where('classification', $request->string('coop_classification'));
        }

        if ($request->filled('coop_type')) {
            $cooperativesQuery->whereHas('types', function ($builder) use ($request) {
                $builder->where('name', $request->string('coop_type'));
            });
        }

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
        }

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

        return Inertia::render('Finance/FinancialRecords/Index', [
            'records' => $records,
            'cooperative' => $cooperative,
            'cooperatives' => $cooperatives,
            'filters' => $request->only(['search', 'type', 'coop_search', 'coop_status', 'coop_type', 'coop_classification', 'coop_id']),
            'permissions' => [
                'can_create' => $user?->can('create finance-ledger-entries') ?? false,
                'can_edit' => $user?->can('update finance-ledger-entries') ?? false,
                'can_delete' => $user?->can('delete finance-ledger-entries') ?? false,
            ],
        ]);
    }

    public function show(Request $request, Cooperative $cooperative, FinancialRecord $financialRecord)
    {
        $user = request()->user();

        if ($cooperative->id !== $financialRecord->coop_id) {
            abort(404);
        }

        if (request()->filled('coop_id') && (int) request()->input('coop_id') !== $financialRecord->coop_id) {
            abort(403);
        }

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $financialRecord->coop_id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Finance/FinancialRecords/Show', [
            'record' => $financialRecord->load('cooperative:id,name,status,classification'),
            'permissions' => [
                'can_edit' => $user?->can('update finance-ledger-entries') ?? false,
            ],
        ]);
    }
}
