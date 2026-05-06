<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ActivityFundingSource;
use App\Models\ActivityParticipant;
use App\Models\CommitteeMember;
use App\Models\Cooperative;
use App\Models\ExternalSupport;
use App\Models\FinancialRecord;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Models\MemberSectorHistory;
use App\Models\MemberServiceAvailed;
use App\Models\Officer;
use App\Models\OfficerTermHistory;
use App\Models\PdsSubmission;
use App\Models\SavingsTransaction;
use App\Models\SkillInventory;
use App\Models\Training;
use App\Models\TrainingParticipant;
use App\Models\User;
use App\Models\UserCoopAssignment;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class RecycleBinController extends Controller
{
    private function authorizeRecycleBin(string $permission): void
    {
        if (! auth()->user()?->can($permission)) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizeRecycleBin('read recycle-bin');

        $filters = $request->only([
            'search',
            'type',
            'coop_id',
            'deleted_by',
            'date_from',
            'date_to',
            'per_page',
        ]);

        $typeFilter = $request->input('type', 'all');
        $search = $request->input('search');
        $coopId = $request->input('coop_id');
        $deletedBy = $request->input('deleted_by');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $resources = $this->recycleResources();

        $cooperatives = Cooperative::withTrashed()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $coopNameById = $cooperatives->pluck('name', 'id');

        $deletedByIds = ActivityLog::query()
            ->where('event', 'deleted')
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $deletedByOptions = User::query()
            ->whereIn('id', $deletedByIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $entries = collect();

        foreach ($resources as $key => $resource) {
            if ($typeFilter !== 'all' && $typeFilter !== $key) {
                continue;
            }

            $query = $resource['model']::onlyTrashed();

            if (!empty($resource['with'])) {
                $query->with($resource['with']);
            }

            if ($coopId && !empty($resource['coop_column'])) {
                $query->where($resource['coop_column'], $coopId);
            }

            if ($dateFrom) {
                $query->whereDate('deleted_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('deleted_at', '<=', $dateTo);
            }

            if ($search && !empty($resource['search'])) {
                $searchHandler = $resource['search'];
                $searchHandler($query, $search);
            }

            $records = $query->get();

            if ($records->isEmpty()) {
                continue;
            }

            $deletedByMap = $this->buildDeletedByMap($resource['model'], $records->pluck('id'));

            foreach ($records as $record) {
                $deletedLog = $deletedByMap[$record->id] ?? null;
                $deletedByIdValue = $deletedLog?->causer?->id;
                $deletedByName = $deletedLog?->causer?->name ?? 'System';

                $coopColumn = $resource['coop_column'];
                $resolvedCoopId = $coopColumn ? (int) ($record->{$coopColumn} ?? 0) : 0;
                $resolvedCoopName = $resolvedCoopId ? ($coopNameById[$resolvedCoopId] ?? 'Unknown') : null;

                $entries->push([
                    'id' => $record->id,
                    'type' => $key,
                    'type_label' => $resource['label'],
                    'title' => $resource['title']($record),
                    'cooperative_id' => $resolvedCoopId ?: null,
                    'cooperative_name' => $resolvedCoopName,
                    'deleted_at' => $record->deleted_at?->toIso8601String(),
                    'deleted_by_id' => $deletedByIdValue,
                    'deleted_by_name' => $deletedByName,
                    'supports_related_restore' => $resource['supports_related_restore'],
                ]);
            }
        }

        if ($deletedBy) {
            $entries = $entries->where('deleted_by_id', (int) $deletedBy)->values();
        }

        $entries = $entries->sortByDesc('deleted_at')->values();

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(5, min($perPage, 100));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('RecycleBin/Index', [
            'items' => $paginated,
            'types' => collect($resources)
                ->map(fn ($resource, $key) => [
                    'value' => $key,
                    'label' => $resource['label'],
                ])
                ->values(),
            'cooperatives' => $cooperatives,
            'deletedByOptions' => $deletedByOptions,
            'filters' => [
                'search' => $search,
                'type' => $typeFilter,
                'coop_id' => $coopId,
                'deleted_by' => $deletedBy,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function restore(Request $request)
    {
        $this->authorizeRecycleBin('restore recycle-bin');

        $validated = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
            'restore_related' => ['nullable', 'boolean'],
        ]);

        $resources = $this->recycleResources();
        $type = $validated['type'];

        if (!array_key_exists($type, $resources)) {
            abort(404);
        }

        $resource = $resources[$type];
        $modelClass = $resource['model'];
        $record = $modelClass::withTrashed()->findOrFail($validated['id']);
        $restoreRelated = (bool) ($validated['restore_related'] ?? false);

        $record->restore();

        if ($restoreRelated && $resource['supports_related_restore']) {
            $this->restoreRelated($type, $record);
        }

        return redirect()->route('recycle-bin.index')
            ->with('success', 'Record restored successfully.');
    }

    public function destroy(Request $request)
    {
        $this->authorizeRecycleBin('delete recycle-bin');

        $validated = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
        ]);

        $resources = $this->recycleResources();
        $type = $validated['type'];

        if (!array_key_exists($type, $resources)) {
            abort(404);
        }

        $resource = $resources[$type];
        $modelClass = $resource['model'];
        $record = $modelClass::withTrashed()->findOrFail($validated['id']);

        $record->forceDelete();

        return redirect()->route('recycle-bin.index')
            ->with('success', 'Record permanently deleted.');
    }

    private function recycleResources(): array
    {
        return [
            'cooperatives' => [
                'label' => 'Cooperative',
                'model' => Cooperative::class,
                'coop_column' => 'id',
                'supports_related_restore' => true,
                'title' => fn (Cooperative $record) => $record->name,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%")
                            ->orWhere('province', 'like', "%{$search}%")
                            ->orWhere('city_municipality', 'like', "%{$search}%");
                    });
                },
            ],
            'members' => [
                'label' => 'Member',
                'model' => Member::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (Member $record) => $record->full_name ?? trim("{$record->first_name} {$record->last_name}"),
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('membership_status', 'like', "%{$search}%");
                    });
                },
            ],
            'officers' => [
                'label' => 'Officer',
                'model' => Officer::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'with' => ['member' => fn ($q) => $q->withTrashed()],
                'title' => function (Officer $record) {
                    $memberName = $record->member?->full_name;
                    return $memberName ? "{$memberName} ({$record->position})" : "Officer #{$record->id}";
                },
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('position', 'like', "%{$search}%")
                            ->orWhere('committee', 'like', "%{$search}%")
                            ->orWhereHas('member', function ($memberQuery) use ($search) {
                                $memberQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
                },
            ],
            'committee_members' => [
                'label' => 'Committee Member',
                'model' => CommitteeMember::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'with' => ['member' => fn ($q) => $q->withTrashed()],
                'title' => function (CommitteeMember $record) {
                    $memberName = $record->member?->full_name;
                    return $memberName ? "{$memberName} ({$record->committee_name})" : "Committee Member #{$record->id}";
                },
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('committee_name', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%")
                            ->orWhereHas('member', function ($memberQuery) use ($search) {
                                $memberQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
                },
            ],
            'activities' => [
                'label' => 'Activity',
                'model' => Activity::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (Activity $record) => $record->title,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%");
                    });
                },
            ],
            'trainings' => [
                'label' => 'Training',
                'model' => Training::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (Training $record) => $record->title,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('target_group', 'like', "%{$search}%");
                    });
                },
            ],
            'activity_funding_sources' => [
                'label' => 'Activity Funding Source',
                'model' => ActivityFundingSource::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (ActivityFundingSource $record) => $record->funder_name,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('funder_name', 'like', "%{$search}%")
                            ->orWhere('funder_type', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%");
                    });
                },
            ],
            'financial_records' => [
                'label' => 'Financial Record',
                'model' => FinancialRecord::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (FinancialRecord $record) => $record->type . ' · ' . $record->period,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('type', 'like', "%{$search}%")
                            ->orWhere('period', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%");
                    });
                },
            ],
            'external_supports' => [
                'label' => 'External Support',
                'model' => ExternalSupport::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (ExternalSupport $record) => $record->provider_name,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('provider_name', 'like', "%{$search}%")
                            ->orWhere('support_type', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%");
                    });
                },
            ],
            'member_loans' => [
                'label' => 'Member Loan',
                'model' => MemberLoan::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (MemberLoan $record) => "Loan #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('status', 'like', "%{$search}%")
                            ->orWhere('purpose', 'like', "%{$search}%");
                    });
                },
            ],
            'loan_payments' => [
                'label' => 'Loan Payment',
                'model' => LoanPayment::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (LoanPayment $record) => "Payment #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('status', 'like', "%{$search}%")
                            ->orWhere('remarks', 'like', "%{$search}%");
                    });
                },
            ],
            'member_savings' => [
                'label' => 'Member Savings',
                'model' => MemberSavings::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => true,
                'title' => fn (MemberSavings $record) => $record->account_number ?? "Savings #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('account_number', 'like', "%{$search}%")
                            ->orWhere('account_status', 'like', "%{$search}%");
                    });
                },
            ],
            'savings_transactions' => [
                'label' => 'Savings Transaction',
                'model' => SavingsTransaction::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (SavingsTransaction $record) => "Transaction #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('type', 'like', "%{$search}%")
                            ->orWhere('remarks', 'like', "%{$search}%");
                    });
                },
            ],
            'skill_inventories' => [
                'label' => 'Skill Inventory',
                'model' => SkillInventory::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (SkillInventory $record) => $record->skill_name,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('skill_name', 'like', "%{$search}%")
                            ->orWhere('proficiency_level', 'like', "%{$search}%");
                    });
                },
            ],
            'member_services' => [
                'label' => 'Member Service',
                'model' => MemberServiceAvailed::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (MemberServiceAvailed $record) => $record->service_type,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('service_type', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('reference_no', 'like', "%{$search}%");
                    });
                },
            ],
            'users' => [
                'label' => 'User Account',
                'model' => User::class,
                'coop_column' => 'coop_id',
                'supports_related_restore' => false,
                'title' => fn (User $record) => $record->name,
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                },
            ],
            'pds_submissions' => [
                'label' => 'PDS Submission',
                'model' => PdsSubmission::class,
                'coop_column' => 'cooperative_id',
                'supports_related_restore' => false,
                'with' => ['user' => fn ($q) => $q->withTrashed()],
                'title' => fn (PdsSubmission $record) => $record->user?->name ?? "PDS #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                },
            ],
            'loan_types' => [
                'label' => 'Loan Type',
                'model' => LoanType::class,
                'coop_column' => 'cooperative_id',
                'supports_related_restore' => false,
                'title' => fn (LoanType $record) => $record->name,
                'search' => function ($query, string $search) {
                    $query->where('name', 'like', "%{$search}%");
                },
            ],
            'accreditations' => [
                'label' => 'Accreditation',
                'model' => Accreditation::class,
                'coop_column' => 'cooperative_id',
                'supports_related_restore' => false,
                'title' => fn (Accreditation $record) => $record->issuing_body ?? "Accreditation #{$record->id}",
                'search' => function ($query, string $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('issuing_body', 'like', "%{$search}%")
                            ->orWhere('level', 'like', "%{$search}%");
                    });
                },
            ],
        ];
    }

    private function buildDeletedByMap(string $modelClass, Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return ActivityLog::query()
            ->where('event', 'deleted')
            ->where('subject_type', $modelClass)
            ->whereIn('subject_id', $ids)
            ->with('causer:id,name')
            ->orderByDesc('id')
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($logs) => $logs->first());
    }

    private function restoreRelated(string $type, $record): void
    {
        switch ($type) {
            case 'cooperatives':
                $this->restoreCooperativeRelated($record);
                break;
            case 'members':
                $this->restoreMemberRelated($record);
                break;
            case 'officers':
                $this->restoreOfficerRelated($record);
                break;
            case 'activities':
                $this->restoreActivityRelated($record);
                break;
            case 'trainings':
                $this->restoreTrainingRelated($record);
                break;
            case 'member_loans':
                $this->restoreMemberLoanRelated($record);
                break;
            case 'member_savings':
                $this->restoreMemberSavingsRelated($record);
                break;
            case 'financial_records':
                $this->restoreFinancialRecordRelated($record);
                break;
            default:
                break;
        }
    }

    private function restoreCooperativeRelated(Cooperative $cooperative): void
    {
        $activityIds = $cooperative->activities()->withTrashed()->pluck('id');
        if ($activityIds->isNotEmpty()) {
            ActivityParticipant::withTrashed()->whereIn('activity_id', $activityIds)->restore();
        }

        $trainingIds = $cooperative->trainings()->withTrashed()->pluck('id');
        if ($trainingIds->isNotEmpty()) {
            TrainingParticipant::withTrashed()->whereIn('training_id', $trainingIds)->restore();
        }

        $memberIds = $cooperative->members()->withTrashed()->pluck('id');
        if ($memberIds->isNotEmpty()) {
            ActivityParticipant::withTrashed()->whereIn('member_id', $memberIds)->restore();
            TrainingParticipant::withTrashed()->whereIn('member_id', $memberIds)->restore();
            MemberSectorHistory::withTrashed()->whereIn('member_id', $memberIds)->restore();
        }

        $this->restoreRelation($cooperative->users());
        $this->restoreRelation($cooperative->userAssignments());
        $this->restoreRelation($cooperative->members());
        $this->restoreRelation($cooperative->officers());
        $this->restoreRelation($cooperative->committeeMembers());
        $this->restoreRelation($cooperative->activities());
        $this->restoreRelation($cooperative->activityFundingSources());
        $this->restoreRelation($cooperative->trainings());
        $this->restoreRelation($cooperative->financialRecords());
        $this->restoreRelation($cooperative->memberLoans());
        $this->restoreRelation($cooperative->loanPayments());
        $this->restoreRelation($cooperative->memberSavings());
        $this->restoreRelation($cooperative->savingsTransactions());
        $this->restoreRelation($cooperative->externalSupports());
        $this->restoreRelation($cooperative->skillInventories());
        $this->restoreRelation($cooperative->memberServicesAvailed());
        $this->restoreRelation($cooperative->officerTermHistories());
        $this->restoreRelation($cooperative->statusHistory());
        $this->restoreRelation($cooperative->loanTypes());
        $this->restoreRelation($cooperative->accreditations());
        $this->restoreRelation($cooperative->pdsSubmissions());
    }

    private function restoreMemberRelated(Member $member): void
    {
        $this->restoreRelation($member->servicesAvailed());
        $this->restoreRelation($member->sectorHistory());
        $this->restoreRelation($member->activityParticipants());
        $this->restoreRelation($member->trainingParticipants());
        $this->restoreRelation($member->officers());
        $this->restoreRelation($member->skillInventories());
        $this->restoreRelation($member->memberLoans());
        $this->restoreRelation($member->savingsAccount());

        $loanIds = $member->memberLoans()->withTrashed()->pluck('id');
        if ($loanIds->isNotEmpty()) {
            LoanPayment::withTrashed()->whereIn('loan_id', $loanIds)->restore();
        }

        $savingsIds = $member->savingsAccount()->withTrashed()->pluck('id');
        if ($savingsIds->isNotEmpty()) {
            SavingsTransaction::withTrashed()->whereIn('member_savings_id', $savingsIds)->restore();
        }

        if ($member->user()->withTrashed()->exists()) {
            $member->user()->withTrashed()->restore();
        }
    }

    private function restoreOfficerRelated(Officer $officer): void
    {
        $this->restoreRelation($officer->termHistory());
        $this->restoreRelation($officer->trainingParticipants());
    }

    private function restoreActivityRelated(Activity $activity): void
    {
        $this->restoreRelation($activity->participants());
        $this->restoreRelation($activity->fundingSources());
    }

    private function restoreTrainingRelated(Training $training): void
    {
        $this->restoreRelation($training->participants());
        $this->restoreRelation($training->skillsInventory());
    }

    private function restoreMemberLoanRelated(MemberLoan $loan): void
    {
        $this->restoreRelation($loan->payments());
    }

    private function restoreMemberSavingsRelated(MemberSavings $savings): void
    {
        $this->restoreRelation($savings->transactions());
    }

    private function restoreFinancialRecordRelated(FinancialRecord $record): void
    {
        $this->restoreRelation($record->externalSupports());
    }

    private function restoreRelation(HasOneOrMany $relation): void
    {
        $model = $relation->getModel();
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

        if (! $usesSoftDeletes) {
            return;
        }

        $relation->withTrashed()->restore();
    }
}
