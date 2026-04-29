<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialRecordsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = FinancialRecord::query()->with('cooperative:id,name');
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

        if ($request->filled('coop_id')) {
            $query->where('coop_id', (int) $request->input('coop_id'));
            $cooperative = \App\Models\Cooperative::select(['id', 'name'])->find($request->integer('coop_id'));
        }

        $records = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Load cooperatives for global (non-coop) context drill-down
        $cooperatives = collect();
        if (!$request->filled('coop_id')) {
            $cooperativesQuery = \App\Models\Cooperative::query()
                ->select(['id', 'name', 'status'])
                ->where('status', 'Active')
                ->orderBy('name');

            if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
                $cooperativesQuery->where('id', $user->coop_id);
            }

            $cooperatives = $cooperativesQuery->get();
        }

        return Inertia::render('Finance/FinancialRecords/Index', [
            'records' => $records,
            'cooperative' => $cooperative,
            'cooperatives' => $cooperatives,
            'filters' => $request->only(['search', 'type']),
            'permissions' => [
                'can_create' => $user?->can('create finance-ledger-entries') ?? false,
                'can_edit' => $user?->can('update finance-ledger-entries') ?? false,
                'can_delete' => $user?->can('delete finance-ledger-entries') ?? false,
            ],
        ]);
    }

    public function show(FinancialRecord $financialRecord)
    {
        $user = request()->user();

        if (request()->filled('coop_id') && (int) request()->input('coop_id') !== $financialRecord->coop_id) {
            abort(403);
        }

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $financialRecord->coop_id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Finance/FinancialRecords/Show', [
            'record' => $financialRecord->load('cooperative:id,name'),
            'permissions' => [
                'can_edit' => $user?->can('update finance-ledger-entries') ?? false,
            ],
        ]);
    }
}
