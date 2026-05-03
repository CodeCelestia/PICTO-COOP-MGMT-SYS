<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\ActivityParticipant;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Officer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivityWithChanges;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    use LogsActivityWithChanges;

    private function resolveGroupedActivityIds(Activity $activity): Collection
    {
        return Activity::query()
            ->where('title', $activity->title)
            ->where('description', $activity->description)
            ->where('category', $activity->category)
            ->where('date_started', $activity->date_started)
            ->where('date_ended', $activity->date_ended)
            ->where('status', $activity->status)
            ->where('responsible_officer_id', $activity->responsible_officer_id)
            ->where('funding_source', $activity->funding_source)
            ->where('budget', $activity->budget)
            ->where('actual_expense', $activity->actual_expense)
            ->where('target_member_beneficiaries', $activity->target_member_beneficiaries)
            ->where('target_community_beneficiaries', $activity->target_community_beneficiaries)
            ->where('actual_member_beneficiaries', $activity->actual_member_beneficiaries)
            ->where('actual_community_beneficiaries', $activity->actual_community_beneficiaries)
            ->where('venue', $activity->venue)
            ->where('implementing_partner', $activity->implementing_partner)
            ->where('outcomes', $activity->outcomes)
            ->where('outcomes_attachment_path', $activity->outcomes_attachment_path)
            ->where('remarks', $activity->remarks)
            ->pluck('id');
    }

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

    private function normalizeDateInput(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    /**
     * Display a listing of activities.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $baseQuery = Activity::query();

        if (($this->isCoopAdmin() || $this->isOfficer()) && $user?->coop_id) {
            $baseQuery->where('coop_id', $user->coop_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('funding_source', 'like', "%{$search}%")
                    ->orWhere('implementing_partner', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $baseQuery->where('category', $request->category);
        }

        if ($request->filled('coop_id') && !$this->isCoopAdmin()) {
            $baseQuery->where('coop_id', $request->coop_id);
        }

        $groupColumns = [
            'title',
            'description',
            'category',
            'date_started',
            'date_ended',
            'status',
            'responsible_officer_id',
            'funding_source',
            'budget',
            'actual_expense',
            'target_member_beneficiaries',
            'target_community_beneficiaries',
            'actual_member_beneficiaries',
            'actual_community_beneficiaries',
            'venue',
            'implementing_partner',
            'outcomes',
            'outcomes_attachment_path',
            'remarks',
        ];

        $groupedQuery = (clone $baseQuery)
            ->selectRaw('MAX(id) as id, COUNT(DISTINCT coop_id) as cooperatives_count')
            ->groupBy($groupColumns);

        $query = Activity::with(['cooperative', 'responsibleOfficer.member'])
            ->joinSub($groupedQuery, 'activity_groups', function ($join) {
                $join->on('activities.id', '=', 'activity_groups.id');
            })
            ->addSelect('activities.*')
            ->addSelect(DB::raw('activity_groups.cooperatives_count as cooperatives_count'));

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 500));

        $activities = $query->orderByDesc('activities.created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'category', 'coop_id', 'per_page']),
        ]);
    }

    public function cooperativesParticipating(Activity $activity): Response
    {
        $this->enforceCoopScope($activity->coop_id);

        $linkedActivityIds = $this->resolveGroupedActivityIds($activity);

        $allCoopIds = Activity::query()
            ->whereIn('id', $linkedActivityIds)
            ->whereNotNull('coop_id')
            ->distinct()
            ->pluck('coop_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $cooperatives = Cooperative::query()
            ->select('id', 'name', 'classification', 'status')
            ->whereIn('id', $allCoopIds)
            ->orderBy('name')
            ->get();

        return Inertia::render('Activities/CooperativesParticipating', [
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
            ],
            'cooperatives' => $cooperatives,
        ]);
    }

    /**
     * Display detailed information for an activity.
     */
    public function show(int $id): Response
    {
        $activity = Activity::withTrashed()
            ->with(['cooperative', 'responsibleOfficer.member', 'fundingSources', 'participants'])
            ->findOrFail($id);

        $this->enforceCoopScope($activity->coop_id);

        $linkedActivityIds = $this->resolveGroupedActivityIds($activity);

        $groupedActivities = Activity::query()
            ->whereIn('id', $linkedActivityIds)
            ->with([
                'cooperative:id,name,registration_number,region,classification,status',
                'responsibleOfficer.member',
                'fundingSources',
                'participants',
            ])
            ->get();

        $primaryActivity = $groupedActivities->firstWhere('id', $activity->id) ?? $groupedActivities->first() ?? $activity;

        $cooperatives = $groupedActivities
            ->pluck('cooperative')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($coop) => [
                'id' => $coop->id,
                'name' => $coop->name,
                'registration_number' => $coop->registration_number,
                'region' => $coop->region,
                'classification' => $coop->classification,
                'status' => $coop->status,
            ]);

        $buildAttachment = function (?string $path, ?string $name = null, array $extra = []) {
            if (empty($path)) {
                return null;
            }

            $disk = Storage::disk('public');
            /** @var FilesystemAdapter $disk */
            $exists = $disk->exists($path);

            return array_merge([
                'path' => $path,
                'name' => $name ?: basename($path),
                'url' => $exists ? $disk->url($path) : null,
                'size' => $exists ? $disk->size($path) : null,
            ], $extra);
        };

        $outcomesAttachments = $groupedActivities
            ->flatMap(function ($item) use ($buildAttachment) {
                $storedAttachments = collect($item->outcomes_attachments ?? []);

                if ($storedAttachments->isEmpty() && !empty($item->outcomes_attachment_path)) {
                    $storedAttachments = collect([[
                        'filename' => basename($item->outcomes_attachment_path),
                        'path' => $item->outcomes_attachment_path,
                        'url' => Storage::disk('public')->url($item->outcomes_attachment_path),
                        'size' => Storage::disk('public')->exists($item->outcomes_attachment_path)
                            ? Storage::disk('public')->size($item->outcomes_attachment_path)
                            : null,
                    ]]);
                }

                return $storedAttachments->map(function (array $attachment) use ($buildAttachment, $item) {
                    return $buildAttachment($attachment['path'] ?? null, $attachment['filename'] ?? null, [
                        'source_activity_id' => $item->id,
                    ]);
                });
            })
            ->filter()
            ->unique('path')
            ->values();

        $fundingAttachments = $groupedActivities
            ->flatMap(function ($item) use ($buildAttachment) {
                return $item->fundingSources->flatMap(function ($source) {
                    $paths = $source->attachment_paths ?? [];
                    $names = $source->attachment_names ?? [];

                    return collect($paths)->map(function ($path, $index) use ($names, $source) {
                        return [
                            'path' => $path,
                            'name' => $names[$index] ?? basename($path),
                            'funding_source_id' => $source->id,
                            'funder_name' => $source->funder_name,
                        ];
                    });
                })->map(function (array $file) use ($buildAttachment, $item) {
                    return $buildAttachment($file['path'] ?? null, $file['name'] ?? null, [
                        'source_activity_id' => $item->id,
                        'funding_source_id' => $file['funding_source_id'] ?? null,
                        'funder_name' => $file['funder_name'] ?? null,
                    ]);
                });
            })
            ->filter()
            ->unique(fn ($file) => ($file['path'] ?? '').'|'.($file['name'] ?? ''))
            ->values();

        return Inertia::render('Activities/Show', [
            'activity' => [
                'id' => $primaryActivity->id,
                'title' => $primaryActivity->title,
                'description' => $primaryActivity->description,
                'category' => $primaryActivity->category,
                'status' => $primaryActivity->status,
                'date_started' => optional($primaryActivity->date_started)->toDateString(),
                'date_ended' => optional($primaryActivity->date_ended)->toDateString(),
                'venue' => $primaryActivity->venue,
                'implementing_partner' => $primaryActivity->implementing_partner,
                'funding_source' => $primaryActivity->funding_source,
                'budget' => $primaryActivity->budget,
                'actual_expense' => $primaryActivity->actual_expense,
                'target_member_beneficiaries' => $primaryActivity->target_member_beneficiaries,
                'actual_member_beneficiaries' => $primaryActivity->actual_member_beneficiaries,
                'target_community_beneficiaries' => $primaryActivity->target_community_beneficiaries,
                'actual_community_beneficiaries' => $primaryActivity->actual_community_beneficiaries,
                'outcomes' => $primaryActivity->outcomes,
                'remarks' => $primaryActivity->remarks,
                'responsible_officer' => $primaryActivity->responsibleOfficer?->member?->full_name,
                'cooperatives_count' => $cooperatives->count(),
            ],
            'cooperatives' => $cooperatives,
            'participantsCount' => $groupedActivities->sum(fn ($item) => $item->participants->count()),
            'outcomesAttachments' => $outcomesAttachments,
            'fundingAttachments' => $fundingAttachments,
            'attachments' => $outcomesAttachments
                ->map(fn (array $file) => array_merge($file, ['section' => 'outcomes']))
                ->concat($fundingAttachments->map(fn (array $file) => array_merge($file, ['section' => 'funding'])))
                ->values(),
            'imageAttachments' => collect($primaryActivity->image_attachments ?? [])
                ->map(function ($img, $index) {
                    return [
                        'id' => $img['id'] ?? $index,
                        'filename' => $img['filename'] ?? 'Image',
                        'url' => $img['url'] ?? '',
                        'size' => $img['size'] ?? 0,
                    ];
                })
                ->filter(fn ($img) => !empty($img['url']))
                ->values(),
        ]);
    }

    public function participantsByCooperative(Activity $activity, Cooperative $cooperative): Response
    {
        return $this->renderCooperativeParticipants($activity, $cooperative);
    }

    public function cooperativeParticipants(Cooperative $cooperative, Activity $activity): Response
    {
        return $this->renderCooperativeParticipants($activity, $cooperative);
    }

    public function saveCooperativeParticipants(Request $request, Cooperative $cooperative, Activity $activity): RedirectResponse
    {
        $targetActivityIds = $this->resolveScopedActivityIds($activity, $cooperative);

        $validated = $request->validate([
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ]);

        $memberIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validCount = Member::query()
            ->whereIn('id', $memberIds)
            ->where('coop_id', $cooperative->id)
            ->count();

        if ($validCount !== $memberIds->count()) {
            abort(422, 'Some members do not belong to this cooperative.');
        }

        DB::transaction(function () use ($targetActivityIds, $memberIds) {
            ActivityParticipant::query()
                ->whereIn('activity_id', $targetActivityIds)
                ->whereNotIn('member_id', $memberIds)
                ->delete();

            foreach ($targetActivityIds as $activityId) {
                foreach ($memberIds as $memberId) {
                    ActivityParticipant::withTrashed()->updateOrCreate(
                        [
                            'activity_id' => $activityId,
                            'member_id' => $memberId,
                        ],
                        [
                            'deleted_at' => null,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Participants updated successfully.');
    }

    private function renderCooperativeParticipants(Activity $activity, Cooperative $cooperative): Response
    {
        $targetActivityIds = $this->resolveScopedActivityIds($activity, $cooperative);

        $allMembers = $cooperative->members()
            ->where('membership_status', 'Active')
            ->select('id', 'first_name', 'last_name', 'coop_id', 'gender', 'date_joined', 'membership_status')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $selectedMemberIds = ActivityParticipant::query()
            ->whereIn('activity_id', $targetActivityIds)
            ->whereIn('member_id', $allMembers->pluck('id'))
            ->whereHas('member', fn ($query) => $query->where('membership_status', 'Active'))
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return Inertia::render('Activities/ParticipantsByCooperative', [
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
            ],
            'cooperative' => [
                'id' => $cooperative->id,
                'name' => $cooperative->name,
            ],
            'allMembers' => $allMembers,
            'selectedMemberIds' => $selectedMemberIds,
            'isCooperativeContext' => true,
        ]);
    }

    private function resolveScopedActivityIds(Activity $activity, Cooperative $cooperative): array
    {
        $this->enforceCoopScope($activity->coop_id);
        $this->enforceCoopScope($cooperative->id);

        $linkedActivityIds = $this->resolveGroupedActivityIds($activity);

        $targetActivityIds = Activity::query()
            ->whereIn('id', $linkedActivityIds)
            ->where('coop_id', $cooperative->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($targetActivityIds)) {
            abort(403, 'This activity does not belong to this cooperative.');
        }

        return $targetActivityIds;
    }

    public function report(int $id)
    {
        $activity = Activity::withTrashed()
            ->with(['cooperative', 'responsibleOfficer.member'])
            ->findOrFail($id);

        $this->enforceCoopScope($activity->coop_id);

        $pdf = Pdf::loadView('reports.activity-report', [
            'activity' => $activity,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(Str::slug($activity->title, '-') . '-activity-report.pdf');
    }

    public function select(): Response
    {
        return Inertia::render('Cooperatives/Select', [
            'title' => 'Activities & Projects',
            'description' => 'Select a cooperative to view activities and projects.',
            'targetUrl' => '/activities',
            'cooperatives' => Cooperative::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new activity.
     */
    public function create(Request $request): Response
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region', 'classification')->orderBy('name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
        }

        $cooperatives = $cooperativesQuery->get();
        $requestedCoopId = (int) $request->input('coop_id');
        $isCooperativeContext = $request->boolean('coop_context') && $requestedCoopId > 0;
        $contextCooperativeId = null;

        if ($isCooperativeContext && $cooperatives->contains('id', $requestedCoopId)) {
            $contextCooperativeId = $requestedCoopId;
        } else {
            $isCooperativeContext = false;
        }

        return Inertia::render('Activities/Create', [
            'cooperatives' => $cooperatives,
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
            'isCooperativeContext' => $isCooperativeContext,
            'contextCooperativeId' => $contextCooperativeId,
        ]);
    }

    /**
     * Store a newly created activity.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $user = auth()->user();
        $coopId = $user?->coop_id;

        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['nullable', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['nullable', 'exists:cooperatives,id'],
            'coop_ids' => ['nullable', 'array'],
            'coop_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['integer', 'exists:cooperatives,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'])],
            'date_started' => ['nullable', 'date'],
            'date_ended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'status' => ['required', Rule::in(['Planned', 'In Progress', 'Completed', 'Archived', 'Cancelled'])],
            'responsible_officer_id' => ['nullable', 'exists:officers,id'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_expense' => ['nullable', 'numeric', 'min:0'],
            'target_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'target_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'venue' => ['nullable', 'string', 'max:255'],
            'implementing_partner' => ['nullable', 'string', 'max:255'],
            'outcomes' => ['nullable', 'string'],
            'outcomes_attachment' => ['nullable', 'array', 'max:3'],
            'outcomes_attachment.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp'],
            'image_attachments' => ['nullable', 'array', 'max:3'],
            'image_attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp'],
            'remarks' => ['nullable', 'string'],
            'funding_sources' => ['nullable', 'array'],
            'funding_sources.*.funder_name' => ['required', 'string', 'max:255'],
            'funding_sources.*.funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'funding_sources.*.amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.amount_released' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.date_released' => ['nullable', 'date'],
            'funding_sources.*.status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'funding_sources.*.remarks' => ['nullable', 'string'],
            'funding_sources.*.attachments' => ['nullable', 'array', 'max:3'],
            'funding_sources.*.attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp'],
            'funding_sources.*.attachments_removed' => ['nullable', 'array'],
            'funding_sources.*.attachments_removed.*' => ['string'],
        ]);

        $selectedCoopIds = collect($validated['coop_ids'] ?? [])
            ->merge(!empty($validated['coop_id']) ? [$validated['coop_id']] : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($this->isCoopAdmin() && $coopId) {
            $selectedCoopIds = collect([(int) $coopId]);
        }

        if ($selectedCoopIds->isEmpty()) {
            return back()->withErrors([
                'coop_ids' => 'Please select at least one cooperative.',
            ])->withInput();
        }

        if (!empty($validated['responsible_officer_id'])) {
            if ($selectedCoopIds->count() > 1) {
                return back()->withErrors([
                    'responsible_officer_id' => 'Responsible officer can only be set when one cooperative is selected.',
                ])->withInput();
            }

            $officer = Officer::find($validated['responsible_officer_id']);
            if ($officer && $officer->coop_id !== $selectedCoopIds->first()) {
                return back()->withErrors(['responsible_officer_id' => 'Selected officer does not belong to this cooperative.']);
            }
        }

        if ($request->hasFile('outcomes_attachment')) {
            $outcomesAttachments = [];
            foreach ($request->file('outcomes_attachment') as $file) {
                $path = $file->store('activity-outcomes-attachments', 'public');
                $outcomesAttachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => '/storage/'.$path,
                    'size' => $file->getSize(),
                ];
            }

            $validated['outcomes_attachment_path'] = $outcomesAttachments[0]['path'] ?? null;
            $validated['outcomes_attachments'] = $outcomesAttachments;
        } else {
            $validated['outcomes_attachment_path'] = null;
            $validated['outcomes_attachments'] = null;
        }

        if ($request->hasFile('image_attachments')) {
            $imagePaths = [];
            $imageId = 1;
            foreach ($request->file('image_attachments') as $image) {
                $path = $image->store('activity-images', 'public');
                $imagePaths[] = [
                    'id' => $imageId++,
                    'filename' => $image->getClientOriginalName(),
                    'url' => '/storage/'.$path,
                    'size' => $image->getSize(),
                ];
            }
            $validated['image_attachments'] = $imagePaths;
        } else {
            $validated['image_attachments'] = null;
        }

        $fundingSources = $validated['funding_sources'] ?? [];
        foreach ($fundingSources as $index => $source) {
            if ($request->hasFile("funding_sources.{$index}.attachments")) {
                $paths = [];
                $names = [];
                foreach ($request->file("funding_sources.{$index}.attachments") as $file) {
                    $paths[] = $file->store('funding-source-attachments', 'public');
                    $names[] = $file->getClientOriginalName();
                }
                $fundingSources[$index]['attachment_paths'] = $paths;
                $fundingSources[$index]['attachment_names'] = $names;
            }
        }
        unset($validated['funding_sources'], $validated['coop_ids'], $validated['removed_outcomes_paths']);

        $createdCount = 0;

        DB::transaction(function () use ($selectedCoopIds, $validated, $fundingSources, &$createdCount) {
            foreach ($selectedCoopIds as $selectedCoopId) {
                $this->enforceCoopScope((int) $selectedCoopId);

                $payload = $validated;
                $payload['coop_id'] = (int) $selectedCoopId;

                if ($selectedCoopIds->count() > 1) {
                    $payload['responsible_officer_id'] = null;
                }

                $activity = Activity::create($payload);
                $createdCount++;

                foreach ($fundingSources as $source) {
                    $activity->fundingSources()->create([
                        'coop_id' => $activity->coop_id,
                        'funder_name' => $source['funder_name'],
                        'funder_type' => $source['funder_type'],
                        'amount_allocated' => $source['amount_allocated'] ?? null,
                        'amount_released' => $source['amount_released'] ?? null,
                        'date_released' => $source['date_released'] ?? null,
                        'status' => $source['status'],
                        'remarks' => $source['remarks'] ?? null,
                        'attachment_paths' => $source['attachment_paths'] ?? null,
                        'attachment_names' => $source['attachment_names'] ?? null,
                    ]);
                }

                $this->logDetailedActivity(
                    'created',
                    $activity,
                    [],
                    $activity->fresh()->getAttributes(),
                    'Activities'
                );
            }
        });

        $successMessage = $createdCount > 1
            ? "Activities created successfully for {$createdCount} cooperatives."
            : 'Activity created successfully.';

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', $successMessage);
        }

        return redirect()->route('activities.index')
            ->with('success', $successMessage);
    }

    /**
     * Show the form for editing an activity.
     */
    public function edit(Request $request, Activity $activity): Response
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $this->enforceCoopScope($activity->coop_id);

        $cooperativesQuery = Cooperative::select('id', 'name', 'registration_number', 'status', 'region', 'classification')->orderBy('name');
        $officersQuery = Officer::with('member:id,first_name,last_name')
            ->select('id', 'member_id', 'coop_id')
            ->orderBy('id');

        if ($this->isCoopAdmin() && $user?->coop_id) {
            $cooperativesQuery->where('id', $user->coop_id);
            $officersQuery->where('coop_id', $user->coop_id);
        }

        $cooperatives = $cooperativesQuery->get();
        $requestedCoopId = (int) $request->input('coop_id');
        $isCooperativeContext = $request->boolean('coop_context') && $requestedCoopId > 0;
        $contextCooperativeId = null;

        if ($isCooperativeContext && $cooperatives->contains('id', $requestedCoopId)) {
            $contextCooperativeId = $requestedCoopId;
        } else {
            $isCooperativeContext = false;
        }

        $linkedActivityIds = $this->resolveGroupedActivityIds($activity);
        $assignedCoopIds = Activity::query()
            ->whereIn('id', $linkedActivityIds)
            ->whereNotNull('coop_id')
            ->distinct()
            ->pluck('coop_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $cooperatives->contains('id', $id))
            ->values()
            ->all();

        $activity->load(['cooperative', 'responsibleOfficer.member', 'fundingSources']);

        return Inertia::render('Activities/Edit', [
            'activity' => array_merge($activity->toArray(), [
                'date_started' => optional($activity->date_started)->format('Y-m-d'),
                'date_ended' => optional($activity->date_ended)->format('Y-m-d'),
                'outcomes_attachments' => collect($activity->outcomes_attachments ?? [])
                    ->map(function ($attachment) {
                        return [
                            'id' => $attachment['id'] ?? null,
                            'filename' => $attachment['filename'] ?? basename($attachment['path'] ?? ''),
                            'url' => $attachment['url'] ?? Storage::disk('public')->url($attachment['path'] ?? ''),
                            'size' => $attachment['size'] ?? null,
                            'path' => $attachment['path'] ?? null,
                        ];
                    })
                    ->values()
                    ->all(),
                'funding_sources' => $activity->fundingSources->map(function ($source) {
                    return array_merge($source->toArray(), [
                        'date_released' => optional($source->date_released)->format('Y-m-d'),
                    ]);
                })->values()->all(),
            ]),
            'cooperatives' => $cooperatives,
            'officers' => $officersQuery->get()->map(function ($officer) {
                return [
                    'id' => $officer->id,
                    'name' => $officer->member?->full_name,
                    'coop_id' => $officer->coop_id,
                ];
            }),
            'isCooperativeContext' => $isCooperativeContext,
            'contextCooperativeId' => $contextCooperativeId,
            'assigned_coop_ids' => $assignedCoopIds,
        ]);
    }

    /**
     * Update an activity.
     */
    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin() && !$this->isOfficer()) {
            abort(403);
        }

        $linkedActivityIds = $this->resolveGroupedActivityIds($activity);
        $linkedActivities = Activity::query()->whereIn('id', $linkedActivityIds)->get()->keyBy('coop_id');

        $coopId = $user?->coop_id;
        $validated = $request->validate([
            'coop_id' => $this->isCoopAdmin() && $coopId
                ? ['nullable', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['nullable', 'exists:cooperatives,id'],
            'coop_ids' => ['nullable', 'array'],
            'coop_ids.*' => $this->isCoopAdmin() && $coopId
                ? ['integer', 'exists:cooperatives,id', Rule::in([$coopId])]
                : ['integer', 'exists:cooperatives,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'])],
            'date_started' => ['nullable', 'date'],
            'date_ended' => ['nullable', 'date', 'after_or_equal:date_started'],
            'status' => ['required', Rule::in(['Planned', 'In Progress', 'Completed', 'Archived', 'Cancelled'])],
            'responsible_officer_id' => ['nullable', 'exists:officers,id'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_expense' => ['nullable', 'numeric', 'min:0'],
            'target_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'target_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_member_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'actual_community_beneficiaries' => ['nullable', 'integer', 'min:0'],
            'venue' => ['nullable', 'string', 'max:255'],
            'implementing_partner' => ['nullable', 'string', 'max:255'],
            'outcomes' => ['nullable', 'string'],
            'outcomes_attachment' => ['nullable', 'array', 'max:3'],
            'outcomes_attachment.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp'],
            'outcomes_attachment_removed' => ['nullable', 'boolean'],
            'removed_outcomes_paths' => ['nullable', 'array'],
            'removed_outcomes_paths.*' => ['string'],
            'image_attachments' => ['nullable', 'array', 'max:3'],
            'image_attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp'],
            'removed_image_ids' => ['nullable', 'array'],
            'removed_image_ids.*' => ['integer'],
            'remarks' => ['nullable', 'string'],
            'funding_sources' => ['nullable', 'array'],
            'funding_sources.*.id' => ['nullable', 'integer'],
            'funding_sources.*.funder_name' => ['required', 'string', 'max:255'],
            'funding_sources.*.funder_type' => ['required', Rule::in(['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'])],
            'funding_sources.*.amount_allocated' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.amount_released' => ['nullable', 'numeric', 'min:0'],
            'funding_sources.*.date_released' => ['nullable', 'date'],
            'funding_sources.*.status' => ['required', Rule::in(['Released', 'Pending', 'Partially Released'])],
            'funding_sources.*.remarks' => ['nullable', 'string'],
            'funding_sources.*.attachments' => ['nullable', 'array', 'max:3'],
            'funding_sources.*.attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp'],
            'funding_sources.*.attachments_removed' => ['nullable', 'array'],
            'funding_sources.*.attachments_removed.*' => ['string'],
        ]);

        $selectedCoopIds = collect($validated['coop_ids'] ?? [])
            ->merge(!empty($validated['coop_id']) ? [$validated['coop_id']] : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($this->isCoopAdmin() && $coopId) {
            $selectedCoopIds = collect([(int) $coopId]);
        }

        if ($selectedCoopIds->isEmpty()) {
            return back()->withErrors([
                'coop_ids' => 'Please select at least one cooperative.',
            ])->withInput();
        }

        $selectedCoopIds->each(fn (int $selectedCoopId) => $this->enforceCoopScope($selectedCoopId));

        $validated['date_started'] = $this->normalizeDateInput($validated['date_started'] ?? null);
        $validated['date_ended'] = $this->normalizeDateInput($validated['date_ended'] ?? null);

        if ($selectedCoopIds->count() > 1) {
            $validated['responsible_officer_id'] = null;
        }

        if (!empty($validated['responsible_officer_id'])) {
            $officer = Officer::find($validated['responsible_officer_id']);
            if ($officer && ! $selectedCoopIds->contains((int) $officer->coop_id)) {
                return back()->withErrors(['responsible_officer_id' => 'Selected officer does not belong to any selected cooperative.']);
            }
        }

        if (!empty($validated['responsible_officer_id'])) {
            $validated['responsible_officer_id'] = (int) $validated['responsible_officer_id'];
        }

        $oldValues = $activity->getAttributes();

        $existingOutcomesAttachments = collect($activity->outcomes_attachments ?? []);
        if ($existingOutcomesAttachments->isEmpty() && !empty($activity->outcomes_attachment_path)) {
            $existingOutcomesAttachments = collect([[
                'filename' => basename($activity->outcomes_attachment_path),
                'path' => $activity->outcomes_attachment_path,
                'url' => Storage::disk('public')->url($activity->outcomes_attachment_path),
                'size' => Storage::disk('public')->exists($activity->outcomes_attachment_path)
                    ? Storage::disk('public')->size($activity->outcomes_attachment_path)
                    : null,
            ]]);
        }

        $removedOutcomesPaths = collect($validated['removed_outcomes_paths'] ?? [])
            ->filter()
            ->values();

        if ($removedOutcomesPaths->isNotEmpty()) {
            $existingOutcomesAttachments = $existingOutcomesAttachments
                ->reject(fn ($attachment) => $removedOutcomesPaths->contains($attachment['path'] ?? null))
                ->values();
        }

        if ($request->hasFile('outcomes_attachment')) {
            $newOutcomesAttachments = [];
            foreach ($request->file('outcomes_attachment') as $file) {
                $path = $file->store('activity-outcomes-attachments', 'public');
                $newOutcomesAttachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => '/storage/'.$path,
                    'size' => $file->getSize(),
                ];
            }

            $existingOutcomesAttachments = $existingOutcomesAttachments->concat($newOutcomesAttachments)->values();
        }

        $validated['outcomes_attachment_path'] = $existingOutcomesAttachments->first()['path'] ?? null;
        $validated['outcomes_attachments'] = $existingOutcomesAttachments->isNotEmpty()
            ? $existingOutcomesAttachments->values()->all()
            : null;

        $existingImages = $activity->image_attachments ?? [];
        $removedImageIds = $validated['removed_image_ids'] ?? [];
        
        if (!empty($removedImageIds)) {
            $remainingImages = array_filter($existingImages, function ($img) use ($removedImageIds) {
                return !in_array($img['id'] ?? null, $removedImageIds);
            });
            $existingImages = array_values($remainingImages);
        }

        if ($request->hasFile('image_attachments')) {
            $newImages = [];
            $maxId = 0;
            foreach ($existingImages as $img) {
                if (isset($img['id']) && $img['id'] > $maxId) {
                    $maxId = $img['id'];
                }
            }
            
            foreach ($request->file('image_attachments') as $image) {
                $path = $image->store('activity-images', 'public');
                $maxId++;
                $newImages[] = [
                    'id' => $maxId,
                    'filename' => $image->getClientOriginalName(),
                    'url' => '/storage/'.$path,
                    'size' => $image->getSize(),
                ];
            }
            $existingImages = array_merge($existingImages, $newImages);
        }

        $validated['image_attachments'] = !empty($existingImages) ? $existingImages : null;
        unset($validated['removed_image_ids']);

        $fundingSources = $validated['funding_sources'] ?? [];
        foreach ($fundingSources as $index => $source) {
            if ($request->hasFile("funding_sources.{$index}.attachments")) {
                $paths = [];
                $names = [];
                foreach ($request->file("funding_sources.{$index}.attachments") as $file) {
                    $paths[] = $file->store('funding-source-attachments', 'public');
                    $names[] = $file->getClientOriginalName();
                }
                $fundingSources[$index]['attachment_paths'] = $paths;
                $fundingSources[$index]['attachment_names'] = $names;
            }
        }

        unset($validated['funding_sources'], $validated['coop_ids']);

        DB::transaction(function () use ($activity, $validated, $selectedCoopIds, $fundingSources, $linkedActivities, $oldValues) {
            $updatedCurrentActivity = null;

            foreach ($linkedActivities as $linkedActivity) {
                if (! $selectedCoopIds->contains((int) $linkedActivity->coop_id)) {
                    $linkedActivity->fundingSources()->delete();
                    $linkedActivity->delete();
                }
            }

            foreach ($selectedCoopIds as $selectedCoopId) {
                $payload = $validated;
                $payload['coop_id'] = (int) $selectedCoopId;

                $linkedActivity = $linkedActivities->get((int) $selectedCoopId);
                if ($linkedActivity) {
                    if ($linkedActivity->is($activity)) {
                        $updatedCurrentActivity = $linkedActivity;
                    }

                    $linkedActivity->update($payload);
                    $linkedActivity->fundingSources()->delete();
                } else {
                    $linkedActivity = Activity::create($payload);
                }

                foreach ($fundingSources as $source) {
                    $linkedActivity->fundingSources()->create([
                        'coop_id' => $linkedActivity->coop_id,
                        'funder_name' => $source['funder_name'],
                        'funder_type' => $source['funder_type'],
                        'amount_allocated' => $source['amount_allocated'] ?? null,
                        'amount_released' => $source['amount_released'] ?? null,
                        'date_released' => $source['date_released'] ?? null,
                        'status' => $source['status'],
                        'remarks' => $source['remarks'] ?? null,
                        'attachment_paths' => $source['attachment_paths'] ?? null,
                        'attachment_names' => $source['attachment_names'] ?? [],
                    ]);
                }

                if ($linkedActivity->is($activity)) {
                    $updatedCurrentActivity = $linkedActivity;
                }
            }

            $this->logDetailedActivity(
                'updated',
                $updatedCurrentActivity ?? $activity,
                $oldValues,
                (($updatedCurrentActivity ?? $activity)->fresh()?->getAttributes()) ?? ($updatedCurrentActivity ?? $activity)->getAttributes(),
                'Activities'
            );
        });

        $returnTo = $this->resolveInternalReturnTo($request);
        if ($returnTo) {
            return redirect()->to($returnTo)
                ->with('success', 'Activity updated successfully.');
        }

        return redirect()->route('activities.index')
            ->with('success', 'Activity updated successfully.');
    }

    /**
     * Remove an activity.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        if (!$this->isProvincialAdmin() && !$this->isCoopAdmin()) {
            abort(403);
        }

        $this->enforceCoopScope($activity->coop_id);

        $oldValues = $activity->getAttributes();

        DB::transaction(function () use ($activity) {
            $activity->participants()->delete();
            $activity->fundingSources()->delete();
            $activity->delete();
        });

        $this->logDetailedActivity(
            'deleted',
            $activity,
            $oldValues,
            [],
            'Activities'
        );

        return redirect()->back()
            ->with('success', 'Activity deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $activity = Activity::withTrashed()->findOrFail($id);
        $this->enforceCoopScope($activity->coop_id);
        $activity->restore();

        return redirect()->route('activities.index')
            ->with('success', 'Activity restored successfully.');
    }
}
