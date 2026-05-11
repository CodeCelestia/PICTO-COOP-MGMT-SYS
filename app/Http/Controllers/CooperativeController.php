<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\CooperativeType;
use App\Models\CooperativeStatusHistory;
use App\Models\Member;
use App\Models\Officer;
use App\Models\CommitteeMember;
use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\Training;
use App\Models\LoanType;
use App\Models\FinancialRecord;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Traits\LogsActivityWithChanges;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CooperativeController extends Controller
{
    use LogsActivityWithChanges;

    private function canViewAllCooperatives(): bool
    {
        $user = auth()->user();

        return $user ? $user->can('view-all-cooperatives') : false;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Cooperative::query();

        if (!$this->canViewAllCooperatives() && $user?->coop_id) {
            $query->where('id', $user->coop_id);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Coop Type filter
        if ($request->filled('coop_type')) {
            $query->whereHas('types', function ($typeQuery) use ($request) {
                $typeQuery->where('name', $request->coop_type);
            });
        }

        // Geographical filters (Region -> Province -> Municipality)
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        if ($request->filled('municipality')) {
            $query->where('city_municipality', $request->municipality);
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 500));

        $cooperatives = $query->with([
            'types',
            'accreditations' => function ($query) {
                $query->orderByDesc('date_granted');
            },
        ])->withCount([
            'members',
            'members as active_members_count' => function ($memberQuery) {
                $memberQuery->where('membership_status', 'Active');
            },
            'members as male_members_count' => function ($memberQuery) {
                $memberQuery->where('gender', 'Male');
            },
            'members as female_members_count' => function ($memberQuery) {
                $memberQuery->where('gender', 'Female');
            },
        ])->orderBy('name')->paginate($perPage)->withQueryString();

        $cooperatives->getCollection()->transform(function ($cooperative) {
            $latestAccreditation = $cooperative->accreditations()
                ->orderByDesc('date_granted')
                ->first(['id', 'cooperative_id', 'level', 'date_granted']);

            $cooperative->setAttribute('latest_accreditation', $latestAccreditation ? [
                'id' => $latestAccreditation->id,
                'cooperative_id' => $latestAccreditation->cooperative_id,
                'level' => $latestAccreditation->level,
                'date_granted' => optional($latestAccreditation->date_granted)->toDateString(),
            ] : null);

            return $cooperative;
        });

        return Inertia::render('Cooperatives/Index', [
            'cooperatives' => $cooperatives,
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'coop_type', 'region', 'province', 'municipality', 'per_page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Cooperatives/Create', [
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('create coop-master-profile')) {
            abort(403, 'You do not have permission to create cooperative profiles.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives',
            'type_ids' => 'required|array|min:1',
            'type_ids.*' => 'integer|exists:cooperative_types,id',
            'classification' => 'nullable|in:micro,small,medium,large,billion',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Pending,Inactive,Dissolved,Suspended',
            'requirements' => 'nullable|array',
            'requirements.coc_certificate.checked' => 'nullable|boolean',
            'requirements.coc_certificate.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.coc_certificate.description' => 'nullable|string|max:500',
            'requirements.prs_certification.checked' => 'nullable|boolean',
            'requirements.prs_certification.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.prs_certification.description' => 'nullable|string|max:500',
            'requirements.certificate_of_registration.checked' => 'nullable|boolean',
            'requirements.certificate_of_registration.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.certificate_of_registration.description' => 'nullable|string|max:500',
            'accreditations' => 'nullable|array',
            'accreditations.*.level' => 'required_with:accreditations|string|max:255',
            'accreditations.*.date_granted' => 'required_with:accreditations|date',
            'accreditations.*.valid_until' => 'nullable|date',
            'accreditations.*.issuing_body' => 'nullable|string|max:255',
            'accreditations.*.remarks' => 'nullable|string|max:500',
        ]);

        // Custom validation: if a requirement is checked, a file must be attached
        $validator->after(function ($validator) use ($request) {
            foreach (['coc_certificate', 'prs_certification', 'certificate_of_registration'] as $key) {
                $checked = filter_var($request->input("requirements.{$key}.checked"), FILTER_VALIDATE_BOOLEAN);
                $hasFile = $request->hasFile("requirements.{$key}.file");

                if ($checked && !$hasFile) {
                    $validator->errors()->add("requirements.{$key}.file", 'Please upload a file for this requirement.');
                }
            }
        });

        $validated = $validator->validate();

        $requirements = $validated['requirements'] ?? [];
        $allRequirementsCompleted = collect(['coc_certificate', 'prs_certification', 'certificate_of_registration'])
            ->every(fn (string $key) => !empty($requirements[$key]['checked'] ?? false));

        if (! $allRequirementsCompleted) {
            $validated['status'] = 'Pending';
        }

        $typeIds = $validated['type_ids'];
        unset($validated['type_ids']);

        if (is_string($request->input('date_established')) && $request->input('date_established') !== '') {
            $validated['date_established'] = substr($request->input('date_established'), 0, 10);
        }

        $accreditations = $validated['accreditations'] ?? [];
        unset($validated['accreditations']);

        foreach ($accreditations as &$accreditation) {
            if (!is_array($accreditation)) {
                continue;
            }

            foreach (['date_granted', 'valid_until', 'accreditation_date'] as $dateField) {
                if (!empty($accreditation[$dateField])) {
                    $accreditation[$dateField] = substr((string) $accreditation[$dateField], 0, 10);
                }
            }
        }
        unset($accreditation);

        $oldValues = [];
        $cooperative = Cooperative::create($validated);
        $cooperative->types()->sync($typeIds);

        foreach ($accreditations as $accreditation) {
            $cooperative->accreditations()->create([
                'level' => $accreditation['level'],
                'date_granted' => $accreditation['date_granted'],
                'valid_until' => $accreditation['valid_until'] ?? null,
                'issuing_body' => $accreditation['issuing_body'] ?? 'CDA',
                'remarks' => $accreditation['remarks'] ?? null,
            ]);
        }

        $requirementMetadata = [];
        foreach (['coc_certificate', 'prs_certification', 'certificate_of_registration'] as $key) {
            $checked = !empty($requirements[$key]['checked'] ?? false);
            $description = $requirements[$key]['description'] ?? null;
            $filePath = null;
            $originalName = null;

            if ($request->hasFile("requirements.{$key}.file")) {
                $file = $request->file("requirements.{$key}.file");
                $filePath = $file->storeAs(
                    "cooperative-requirements/{$cooperative->id}",
                    sprintf('%s_%s.%s', $cooperative->id, $key, $file->getClientOriginalExtension()),
                    'public'
                );
                $originalName = $file->getClientOriginalName();
            }

            $requirementMetadata[$key] = [
                'checked' => $checked,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'description' => $description,
            ];
        }

        $cooperative->update(['requirements' => $requirementMetadata]);

        CooperativeStatusHistory::create([
            'coop_id' => $cooperative->id,
            'previous_status' => null,
            'new_status' => $cooperative->status,
            'change_reason' => 'Initial registration',
            'changed_by' => auth()->user()?->name ?? 'System',
            'changed_at' => now(),
            'remarks' => 'Initial cooperative status set during creation.',
        ]);

        $this->logDetailedActivity(
            'created',
            $cooperative,
            [],
            $cooperative->fresh()->getAttributes(),
            'Cooperatives'
        );

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative created successfully.');
    }

    public function edit(Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$this->canViewAllCooperatives() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        return Inertia::render('Cooperatives/Edit', [
            'cooperative' => $cooperative->load([
                'types',
                'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                },
            ]),
            'cooperativeTypes' => CooperativeType::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'statusHistory' => $cooperative->statusHistory()
                ->latest('changed_at')
                ->get()
                ->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'previous_status' => $history->previous_status,
                        'new_status' => $history->new_status,
                        'change_reason' => $history->change_reason,
                        'changed_by' => $history->changed_by,
                        'changed_at' => optional($history->changed_at)->toDateTimeString(),
                        'remarks' => $history->remarks,
                    ];
                }),
        ]);
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $user = auth()->user();

        if (!$request->user()?->can('update coop-master-profile')) {
            abort(403, 'You do not have permission to update cooperative profiles.');
        }

        if (!$this->canViewAllCooperatives() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        $existingRequirements = $cooperative->requirements ?? [];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255|unique:cooperatives,registration_number,' . $cooperative->id,
            'type_ids' => 'required|array|min:1',
            'type_ids.*' => 'integer|exists:cooperative_types,id',
            'classification' => 'nullable|in:micro,small,medium,large,billion',
            'date_established' => 'required|date',
            'address' => 'required|string',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Pending,Inactive,Dissolved,Suspended',
            'requirements' => 'nullable|array',
            'requirements.coc_certificate.checked' => 'nullable|boolean',
            'requirements.coc_certificate.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.coc_certificate.description' => 'nullable|string|max:500',
            'requirements.prs_certification.checked' => 'nullable|boolean',
            'requirements.prs_certification.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.prs_certification.description' => 'nullable|string|max:500',
            'requirements.certificate_of_registration.checked' => 'nullable|boolean',
            'requirements.certificate_of_registration.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirements.certificate_of_registration.description' => 'nullable|string|max:500',
            'accreditations' => 'nullable|array',
            'accreditations.*.id' => 'nullable|integer|exists:accreditations,id',
            'accreditations.*.level' => 'required_with:accreditations|string|max:255',
            'accreditations.*.date_granted' => 'required_with:accreditations|date',
            'accreditations.*.valid_until' => 'nullable|date',
            'accreditations.*.issuing_body' => 'nullable|string|max:255',
            'accreditations.*.remarks' => 'nullable|string|max:500',
            'change_reason' => 'nullable|string|max:500',
            'status_remarks' => 'nullable|string|max:500',
        ]);

        $submittedRequirements = $request->input('requirements', []);
        $autoStatus = collect(['coc_certificate', 'prs_certification', 'certificate_of_registration'])
            ->every(fn (string $key) => !empty($submittedRequirements[$key]['checked'] ?? false))
            ? 'Active'
            : 'Pending';

        $submittedStatus = $request->input('status', $cooperative->status);
        $statusChangeIsManual = $submittedStatus !== $cooperative->status && $submittedStatus !== $autoStatus;

        if ($statusChangeIsManual) {
            $validator->sometimes('change_reason', ['required', 'string', 'max:500'], fn () => true);
        }

        $validator->after(function ($validator) use ($request, $existingRequirements) {
            foreach (['coc_certificate', 'prs_certification', 'certificate_of_registration'] as $key) {
                $checked = filter_var($request->input("requirements.{$key}.checked"), FILTER_VALIDATE_BOOLEAN);
                $hasFile = $request->hasFile("requirements.{$key}.file");
                $hasExistingFile = !empty($existingRequirements[$key]['file_path'] ?? null);

                // Frontend may send stored_file metadata for existing attachments.
                $storedFile = $request->input("requirements.{$key}.stored_file");
                $hasProvidedStoredFile = false;
                if (is_array($storedFile) && !empty($storedFile['file_path'] ?? null)) {
                    $hasProvidedStoredFile = true;
                } elseif (is_string($storedFile) && trim($storedFile) !== '') {
                    // defensive: if stored_file was serialized as a string for any reason
                    $hasProvidedStoredFile = true;
                }

                if ($checked && !$hasFile && !$hasExistingFile && !$hasProvidedStoredFile) {
                    $validator->errors()->add("requirements.{$key}.file", 'Please upload a file for the selected requirement.');
                }
            }
        });

        $validated = $validator->validate();

        $requirements = $validated['requirements'] ?? [];
        $allRequirementsCompleted = collect(['coc_certificate', 'prs_certification', 'certificate_of_registration'])
            ->every(fn (string $key) => !empty($requirements[$key]['checked'] ?? false));

        $validated['status'] = $allRequirementsCompleted ? 'Active' : 'Pending';

        $typeIds = $validated['type_ids'];
        unset($validated['type_ids']);

        $existingRequirements = $cooperative->requirements ?? [];
        $requirementMetadata = [];

        foreach (['coc_certificate', 'prs_certification', 'certificate_of_registration'] as $key) {
            $checked = !empty($requirements[$key]['checked'] ?? false);
            $description = $requirements[$key]['description'] ?? $existingRequirements[$key]['description'] ?? null;
            $filePath = $existingRequirements[$key]['file_path'] ?? null;
            $originalName = $existingRequirements[$key]['original_name'] ?? null;

            if ($request->hasFile("requirements.{$key}.file")) {
                $file = $request->file("requirements.{$key}.file");
                $filePath = $file->storeAs(
                    "cooperative-requirements/{$cooperative->id}",
                    sprintf('%s_%s.%s', $cooperative->id, $key, $file->getClientOriginalExtension()),
                    'public'
                );
                $originalName = $file->getClientOriginalName();
            }

            if (! $checked) {
                $filePath = null;
                $originalName = null;
            }

            $requirementMetadata[$key] = [
                'checked' => $checked,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'description' => $description,
            ];
        }

        $validated['requirements'] = $requirementMetadata;

        $oldValues = $cooperative->getAttributes();
        $previousStatus = $cooperative->status;
        $newStatus = $validated['status'];
        $changeReason = $validated['change_reason'] ?? null;
        $statusRemarks = $validated['status_remarks'] ?? null;
        unset($validated['change_reason'], $validated['status_remarks']);

        if (is_string($request->input('date_established')) && $request->input('date_established') !== '') {
            $validated['date_established'] = substr($request->input('date_established'), 0, 10);
        }

        $cooperative->update($validated);
        $cooperative->types()->sync($typeIds);

        $accreditations = $request->input('accreditations', []);
        if (!empty($accreditations) && is_array($accreditations)) {
            $normalizedAccreditations = [];
            foreach ($accreditations as $acc) {
                if (!is_array($acc)) {
                    continue;
                }

                if (empty($acc['level']) && empty($acc['date_granted'])) {
                    continue;
                }

                foreach (['date_granted', 'valid_until', 'accreditation_date'] as $dateField) {
                    if (!empty($acc[$dateField])) {
                        $acc[$dateField] = substr((string) $acc[$dateField], 0, 10);
                    }
                }

                $normalizedAccreditations[] = $acc;
            }

            $existingIds = $cooperative->accreditations()->pluck('id')->toArray();
            $submittedIds = array_filter(array_map(fn ($item) => $item['id'] ?? null, $normalizedAccreditations));

            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                $cooperative->accreditations()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($normalizedAccreditations as $accreditation) {
                if (!empty($accreditation['id'])) {
                    $existing = $cooperative->accreditations()->withTrashed()->find($accreditation['id']);
                    if ($existing) {
                        $existing->update([
                            'level' => $accreditation['level'],
                            'date_granted' => $accreditation['date_granted'],
                            'valid_until' => $accreditation['valid_until'] ?? null,
                            'issuing_body' => $accreditation['issuing_body'] ?? 'CDA',
                            'remarks' => $accreditation['remarks'] ?? null,
                        ]);

                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                    }

                    continue;
                }

                $cooperative->accreditations()->create([
                    'level' => $accreditation['level'],
                    'date_granted' => $accreditation['date_granted'],
                    'valid_until' => $accreditation['valid_until'] ?? null,
                    'issuing_body' => $accreditation['issuing_body'] ?? 'CDA',
                    'remarks' => $accreditation['remarks'] ?? null,
                ]);
            }
        }

        $requirementMetadata = [];
        foreach (['coc_certificate', 'prs_certification', 'certificate_of_registration'] as $key) {
            if ($request->hasFile("requirements.{$key}.file")) {
                $file = $request->file("requirements.{$key}.file");
                $path = $file->storeAs(
                    "cooperative-requirements/{$cooperative->id}",
                    sprintf('%s_%s.%s', $cooperative->id, $key, $file->getClientOriginalExtension()),
                    'public'
                );

                $requirementMetadata[$key] = [
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'description' => $requirements[$key]['description'] ?? null,
                ];
            }
        }

        if (!empty($requirementMetadata)) {
            Storage::disk('public')->put(
                "cooperative-requirements/{$cooperative->id}/requirements.json",
                json_encode($requirementMetadata)
            );
        }

        if ($previousStatus !== $newStatus) {
            CooperativeStatusHistory::create([
                'coop_id' => $cooperative->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'change_reason' => $changeReason,
                'changed_by' => auth()->user()?->name ?? 'System',
                'changed_at' => now(),
                'remarks' => $statusRemarks,
            ]);
        }

        $this->logDetailedActivity(
            'updated',
            $cooperative,
            $oldValues,
            $cooperative->fresh()->getAttributes(),
            'Cooperatives'
        );

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative updated successfully.');
    }

    public function destroy(Cooperative $cooperative)
    {
        if (!auth()->user()?->can('delete coop-master-profile')) {
            abort(403, 'You do not have permission to delete cooperative profiles.');
        }

        $oldValues = $cooperative->getAttributes();
        $cooperative->delete();

        $this->logDetailedActivity(
            'deleted',
            $cooperative,
            $oldValues,
            [],
            'Cooperatives'
        );

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative deleted successfully.');
    }

    public function restore(int $id)
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Provincial Admin'])) {
            abort(403, 'Only Super Admin and Provincial Admin can restore records.');
        }

        $cooperative = Cooperative::withTrashed()->findOrFail($id);
        $cooperative->restore();

        return redirect()->route('cooperatives.index')
            ->with('success', 'Cooperative restored successfully.');
    }

    public function report(int $id)
    {
        $cooperative = Cooperative::withTrashed()
            ->with(['types'])
            ->withCount('members')
            ->findOrFail($id);

        $user = auth()->user();
        if (! $this->canViewAllCooperatives() && $user?->coop_id && $cooperative->id !== $user->coop_id) {
            abort(403);
        }

        $latestAccreditation = $cooperative->accreditations()
            ->orderByDesc('date_granted')
            ->first(['level', 'date_granted']);

        $pdf = Pdf::loadView('reports.cooperative-report', [
            'cooperative' => $cooperative,
            'latestAccreditation' => $latestAccreditation,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(Str::slug($cooperative->name, '-') . '-cooperative-report.pdf');
    }

    public function show(Request $request, ?Cooperative $cooperative = null)
    {
        $user = auth()->user();
        $canViewAll = $this->canViewAllCooperatives();

        if ($canViewAll) {
            if ($cooperative) {
                $cooperative = $cooperative->load(['types', 'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                }]);
            } elseif ($user?->coop_id) {
                $cooperative = Cooperative::with(['types', 'accreditations' => function ($query) {
                    $query->orderByDesc('date_granted');
                }])->findOrFail($user->coop_id);
            } else {
                return redirect()->route('cooperatives.index');
            }
        } else {
            if (!$user?->coop_id) {
                abort(404);
            }

            $cooperative = Cooperative::with(['types', 'accreditations' => function ($query) {
                $query->orderByDesc('date_granted');
            }])->findOrFail($user->coop_id);
        }

        $chairperson = $cooperative->currentChairperson();
        $generalManager = $cooperative->currentGeneralManager();

        $memberSearch = $request->input('members_search');
        $memberStatus = $request->input('members_membership_status');
        $memberType = $request->input('members_membership_type');
        $memberPerPage = (int) $request->input('members_per_page', 15);
        $memberPerPage = max(1, min($memberPerPage, 500));

        $membersQuery = Member::with(['cooperative', 'user'])
            ->withCount([
                'officers as active_officers_count' => function ($q) {
                    $q->where('status', 'Active');
                },
            ])
            ->where('coop_id', $cooperative->id);

        if ($memberSearch) {
            $membersQuery->where(function ($q) use ($memberSearch) {
                $q->where('first_name', 'like', "%{$memberSearch}%")
                    ->orWhere('last_name', 'like', "%{$memberSearch}%")
                    ->orWhere('email', 'like', "%{$memberSearch}%");
            });
        }

        if ($memberStatus) {
            $membersQuery->where('membership_status', $memberStatus);
        }

        if ($memberType) {
            $membersQuery->where('membership_type', $memberType);
        }

        $members = $membersQuery->orderBy('last_name')->orderBy('first_name')->paginate($memberPerPage)->withQueryString();

        $membershipTypes = Member::query()
            ->where('coop_id', $cooperative->id)
            ->whereNotNull('membership_type')
            ->distinct()
            ->orderBy('membership_type')
            ->pluck('membership_type')
            ->values();

        $officerSearch = $request->input('officers_search');
        $officerStatus = $request->input('officers_status');
        $officerPerPage = (int) $request->input('officers_per_page', 10);
        $officerPerPage = max(1, min($officerPerPage, 500));

        $officersQuery = Officer::with(['member', 'cooperative'])
            ->where('coop_id', $cooperative->id);

        if ($officerSearch) {
            $officersQuery->whereHas('member', function ($q) use ($officerSearch) {
                $q->where('first_name', 'like', "%{$officerSearch}%")
                    ->orWhere('last_name', 'like', "%{$officerSearch}%");
            });
        }

        if ($officerStatus) {
            $officersQuery->where('status', $officerStatus);
        }

        $officers = $officersQuery->latest()->paginate($officerPerPage)->withQueryString();

        $committeeSearch = $request->input('committees_search');
        $committeeStatus = $request->input('committees_status');
        $committeePerPage = (int) $request->input('committees_per_page', 10);
        $committeePerPage = max(1, min($committeePerPage, 500));

        $committeeQuery = CommitteeMember::with(['member', 'cooperative'])
            ->where('coop_id', $cooperative->id);

        if ($committeeSearch) {
            $committeeQuery->whereHas('member', function ($q) use ($committeeSearch) {
                $q->where('first_name', 'like', "%{$committeeSearch}%")
                    ->orWhere('last_name', 'like', "%{$committeeSearch}%");
            });
        }

        if ($committeeStatus) {
            $committeeQuery->where('status', $committeeStatus);
        }

        $committeeMembers = $committeeQuery->latest()->paginate($committeePerPage)->withQueryString();

        $activitySearch = $request->input('activities_search');
        $activityStatus = $request->input('activities_status');
        $activityCategory = $request->input('activities_category');
        $activityPerPage = (int) $request->input('activities_per_page', 15);
        $activityPerPage = max(1, min($activityPerPage, 500));

        $activitiesQuery = Activity::with(['cooperative', 'responsibleOfficer.member'])
            ->where('coop_id', $cooperative->id);

        if ($activitySearch) {
            $activitiesQuery->where(function ($q) use ($activitySearch) {
                $q->where('title', 'like', "%{$activitySearch}%")
                    ->orWhere('description', 'like', "%{$activitySearch}%")
                    ->orWhere('funding_source', 'like', "%{$activitySearch}%")
                    ->orWhere('implementing_partner', 'like', "%{$activitySearch}%");
            });
        }

        if ($activityStatus) {
            $activitiesQuery->where('status', $activityStatus);
        }

        if ($activityCategory) {
            $activitiesQuery->where('category', $activityCategory);
        }

        $activities = $activitiesQuery->latest()->paginate($activityPerPage)->withQueryString();

        $trainingSearch = $request->input('trainings_search');
        $trainingStatus = $request->input('trainings_status');
        $trainingTargetGroup = $request->input('trainings_target_group');
        $trainingPerPage = (int) $request->input('trainings_per_page', 15);
        $trainingPerPage = max(1, min($trainingPerPage, 500));

        $trainingsQuery = Training::with('cooperative')
            ->where('coop_id', $cooperative->id);

        if ($trainingSearch) {
            $trainingsQuery->where(function ($q) use ($trainingSearch) {
                $q->where('title', 'like', "%{$trainingSearch}%")
                    ->orWhere('facilitator', 'like', "%{$trainingSearch}%")
                    ->orWhere('venue', 'like', "%{$trainingSearch}%");
            });
        }

        if ($trainingStatus) {
            $trainingsQuery->where('status', $trainingStatus);
        }

        if ($trainingTargetGroup) {
            $trainingsQuery->where('target_group', $trainingTargetGroup);
        }

        $trainings = $trainingsQuery->latest()->paginate($trainingPerPage)->withQueryString();

        $cooperatives = Cooperative::select('id', 'name')
            ->where('id', $cooperative->id)
            ->orderBy('name')
            ->get();

        $loanTypes = LoanType::query()
            ->where('cooperative_id', $cooperative->id)
            ->orderBy('name')
            ->get(['id', 'cooperative_id', 'name', 'classification', 'description', 'is_active']);

        $loans = MemberLoan::query()
            ->where('coop_id', $cooperative->id)
            ->with(['member', 'loanType'])
            ->latest()
            ->get();

        $savings = MemberSavings::query()
            ->where('coop_id', $cooperative->id)
            ->with(['member'])
            ->latest()
            ->get();

        $financialRecords = FinancialRecord::query()
            ->where('coop_id', $cooperative->id)
            ->latest()
            ->get();

        $fundingSources = ActivityFundingSource::query()
            ->where('coop_id', $cooperative->id)
            ->with(['activity'])
            ->latest()
            ->get();

        return Inertia::render('Cooperatives/Show', [
            'cooperative' => $cooperative,
            'members' => $members,
            'memberFilters' => [
                'search' => $memberSearch,
                'membership_status' => $memberStatus,
                'membership_type' => $memberType,
                'per_page' => $request->input('members_per_page'),
            ],
            'membershipTypes' => $membershipTypes,
            'officers' => $officers,
            'officerFilters' => [
                'search' => $officerSearch,
                'coop_id' => $request->input('officers_coop_id', (string) $cooperative->id),
                'status' => $officerStatus,
                'per_page' => $request->input('officers_per_page'),
            ],
            'committeeMembers' => $committeeMembers,
            'committeeFilters' => [
                'search' => $committeeSearch,
                'coop_id' => $request->input('committees_coop_id', (string) $cooperative->id),
                'status' => $committeeStatus,
                'per_page' => $request->input('committees_per_page'),
            ],
            'activities' => $activities,
            'activityFilters' => [
                'search' => $activitySearch,
                'coop_id' => $request->input('activities_coop_id', (string) $cooperative->id),
                'status' => $activityStatus,
                'category' => $activityCategory,
                'per_page' => $request->input('activities_per_page'),
            ],
            'trainings' => $trainings,
            'trainingFilters' => [
                'search' => $trainingSearch,
                'status' => $trainingStatus,
                'target_group' => $trainingTargetGroup,
                'coop_id' => $request->input('trainings_coop_id', (string) $cooperative->id),
                'per_page' => $request->input('trainings_per_page'),
            ],
            'cooperatives' => $cooperatives,
            'loanTypes' => $loanTypes,
            'loans' => $loans,
            'savings' => $savings,
            'financialRecords' => $financialRecords,
            'fundingSources' => $fundingSources,
            'externalSupports' => \App\Models\ExternalSupport::where('coop_id', $cooperative->id)
                ->orderByDesc('date_granted')
                ->get(),
            'chairperson' => $chairperson ? [
                'id' => $chairperson->id,
                'position' => $chairperson->position,
                'status' => $chairperson->status,
                'term_start' => optional($chairperson->term_start)->toDateString(),
                'term_end' => optional($chairperson->term_end)->toDateString(),
                'member' => $chairperson->member ? [
                    'id' => $chairperson->member->id,
                    'full_name' => $chairperson->member->full_name,
                ] : null,
            ] : null,
            'generalManager' => $generalManager ? [
                'id' => $generalManager->id,
                'position' => $generalManager->position,
                'status' => $generalManager->status,
                'term_start' => optional($generalManager->term_start)->toDateString(),
                'term_end' => optional($generalManager->term_end)->toDateString(),
                'member' => $generalManager->member ? [
                    'id' => $generalManager->member->id,
                    'full_name' => $generalManager->member->full_name,
                ] : null,
            ] : null,
            'loanTypePermissions' => [
                'can_create' => $user?->can('create finance-member-loans') ?? false,
                'can_edit' => $user?->can('update finance-member-loans') ?? false,
                'can_delete' => $user?->can('delete finance-member-loans') ?? false,
            ],
        ]);
    }

}
