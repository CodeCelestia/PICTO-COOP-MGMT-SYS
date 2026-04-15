<?php

namespace App\Http\Controllers;

use App\Models\ActivityFundingSource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FundingSourcesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = ActivityFundingSource::query()->with(['activity:id,title', 'cooperative:id,name']);

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('funder_name', 'like', "%{$search}%")
                    ->orWhereHas('activity', function ($activityQuery) use ($search) {
                        $activityQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('funder_type')) {
            $query->where('funder_type', $request->string('funder_type'));
        }

        $fundingSources = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Finance/FundingSources/Index', [
            'fundingSources' => $fundingSources,
            'filters' => $request->only(['search', 'status', 'funder_type']),
            'permissions' => [
                'can_create' => $user?->can('create finance-funding-sources') ?? false,
                'can_edit' => $user?->can('update finance-funding-sources') ?? false,
                'can_delete' => $user?->can('delete finance-funding-sources') ?? false,
                'can_approve' => $user?->can('approve finance-funding-sources') ?? false,
            ],
        ]);
    }

    public function show(ActivityFundingSource $fundingSource)
    {
        $user = request()->user();

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $fundingSource->coop_id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Finance/FundingSources/Show', [
            'fundingSource' => $fundingSource->load(['activity:id,title', 'cooperative:id,name']),
            'permissions' => [
                'can_edit' => $user?->can('update finance-funding-sources') ?? false,
            ],
        ]);
    }
}
