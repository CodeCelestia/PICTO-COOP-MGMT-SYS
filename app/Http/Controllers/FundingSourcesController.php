<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Cooperative;
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
        $cooperative = null;

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

        if ($request->filled('coop_id')) {
            $query->where('coop_id', (int) $request->input('coop_id'));
            $cooperative = \App\Models\Cooperative::select(['id', 'name'])->find($request->integer('coop_id'));
        }

        $fundingSources = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $cooperativesQuery = Cooperative::query()
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
                'types' => function ($typesQuery) {
                    $typesQuery->select('cooperative_types.id', 'cooperative_types.name');
                },
                'accreditations' => function ($accreditationQuery) {
                    $accreditationQuery->orderByDesc('date_granted');
                },
            ])
            ->withCount('members')
            ->orderBy('name');

        if ($request->filled('coop_search')) {
            $coopSearch = (string) $request->input('coop_search');
            $cooperativesQuery->where(function ($builder) use ($coopSearch) {
                $builder->where('name', 'like', "%{$coopSearch}%")
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
        $activitiesQuery = Activity::query()
            ->select('id', 'title', 'coop_id')
            ->orderBy('title');

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $activitiesQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Finance/FundingSources/Index', [
            'fundingSources' => $fundingSources,
            'activities' => $activitiesQuery->get(),
            'cooperative' => $cooperative,
            'cooperatives' => $cooperatives,
            'filters' => $request->only([
                'search',
                'status',
                'funder_type',
                'activity_id',
                'coop_id',
                'per_page',
                'coop_search',
                'coop_status',
                'coop_type',
                'coop_classification',
            ]),
            'permissions' => [
                'can_create' => $user?->can('create finance-funding-sources') ?? false,
                'can_edit' => $user?->can('update finance-funding-sources') ?? false,
                'can_delete' => $user?->can('delete finance-funding-sources') ?? false,
                'can_approve' => $user?->can('approve finance-funding-sources') ?? false,
            ],
        ]);
    }

    public function show(Request $request, Cooperative $cooperative, ActivityFundingSource $fundingSource)
    {
        $user = request()->user();

        if ($cooperative->id !== $fundingSource->coop_id) {
            abort(404);
        }

        if (request()->filled('coop_id') && (int) request()->input('coop_id') !== $fundingSource->coop_id) {
            abort(403);
        }

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
