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

        $records = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Finance/FinancialRecords/Index', [
            'records' => $records,
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
        return redirect()->route('financial-records.edit', $financialRecord);
    }
}
