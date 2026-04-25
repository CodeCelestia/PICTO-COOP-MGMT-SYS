<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property-read int $id
 * @property string $name
 * @property string|null $registration_number
 * @property string|null $classification
 * @property \Illuminate\Support\Carbon|null $date_established
 * @property string|null $address
 * @property string|null $province
 * @property string|null $region
 * @property string|null $city_municipality
 * @property string|null $barangay
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $status
 */
class Cooperative extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use CoopScoped;

    protected string $coopScopeKey = 'id';
    protected bool $attachCoopId = false;

    protected $fillable = [
        'name',
        'registration_number',
        'classification',
        'date_established',
        'address',
        'province',
        'region',
        'city_municipality',
        'barangay',
        'email',
        'phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_established' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Cooperative $cooperative) {
            $isForceDeleting = $cooperative->isForceDeleting();

            $deleteRelation = static function (HasMany $relation, bool $isForceDeleting): void {
                $model = $relation->getModel();
                $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

                if ($isForceDeleting && $usesSoftDeletes) {
                    $relation->withTrashed()->forceDelete();
                    return;
                }

                $relation->getQuery()->delete();
            };

            $activityQuery = $cooperative->activities();
            $activityIds = ($isForceDeleting ? $activityQuery->withTrashed() : $activityQuery)
                ->pluck('id');

            if ($activityIds->isNotEmpty()) {
                ActivityParticipant::whereIn('activity_id', $activityIds)->delete();
            }

            $trainingQuery = $cooperative->trainings();
            $trainingIds = ($isForceDeleting ? $trainingQuery->withTrashed() : $trainingQuery)
                ->pluck('id');

            if ($trainingIds->isNotEmpty()) {
                TrainingParticipant::whereIn('training_id', $trainingIds)->delete();
            }

            $memberQuery = $cooperative->members();
            $memberIds = ($isForceDeleting ? $memberQuery->withTrashed() : $memberQuery)
                ->pluck('id');

            if ($memberIds->isNotEmpty()) {
                ActivityParticipant::whereIn('member_id', $memberIds)->delete();
                TrainingParticipant::whereIn('member_id', $memberIds)->delete();
                MemberSectorHistory::whereIn('member_id', $memberIds)->delete();
            }

            $deleteRelation($cooperative->users(), $isForceDeleting);
            $deleteRelation($cooperative->userAssignments(), $isForceDeleting);
            $deleteRelation($cooperative->members(), $isForceDeleting);
            $deleteRelation($cooperative->officers(), $isForceDeleting);
            $deleteRelation($cooperative->committeeMembers(), $isForceDeleting);
            $deleteRelation($cooperative->activities(), $isForceDeleting);
            $deleteRelation($cooperative->activityFundingSources(), $isForceDeleting);
            $deleteRelation($cooperative->trainings(), $isForceDeleting);
            $deleteRelation($cooperative->financialRecords(), $isForceDeleting);
            $deleteRelation($cooperative->memberLoans(), $isForceDeleting);
            $deleteRelation($cooperative->loanPayments(), $isForceDeleting);
            $deleteRelation($cooperative->memberSavings(), $isForceDeleting);
            $deleteRelation($cooperative->savingsTransactions(), $isForceDeleting);
            $deleteRelation($cooperative->externalSupports(), $isForceDeleting);
            $deleteRelation($cooperative->skillInventories(), $isForceDeleting);
            $deleteRelation($cooperative->memberServicesAvailed(), $isForceDeleting);
            $deleteRelation($cooperative->officerTermHistories(), $isForceDeleting);
            $deleteRelation($cooperative->statusHistory(), $isForceDeleting);
            $deleteRelation($cooperative->loanTypes(), $isForceDeleting);
            $deleteRelation($cooperative->accreditations(), $isForceDeleting);
            $deleteRelation($cooperative->pdsSubmissions(), $isForceDeleting);
        });
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'registration_number',
                'classification',
                'date_established',
                'address',
                'region',
                'province',
                'city_municipality',
                'barangay',
                'email',
                'phone',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Cooperative has been {$eventName}");
    }

    /**
     * Get the users assigned to this cooperative
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'coop_id');
    }

    /**
     * Get the user-coop assignments for this cooperative
     */
    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserCoopAssignment::class, 'coop_id');
    }

    /**
     * Get the members of this cooperative
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'coop_id');
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class, 'coop_id');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class, 'coop_id');
    }

    /**
     * Get the activities for this cooperative.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'coop_id');
    }

    public function activityFundingSources(): HasMany
    {
        return $this->hasMany(ActivityFundingSource::class, 'coop_id');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class, 'coop_id');
    }

    public function types()
    {
        return $this->belongsToMany(CooperativeType::class, 'cooperative_cooperative_type')
                    ->withTimestamps();
    }

    public function regionTypes()
    {
        return $this->types()->where('level', 'region');
    }

    public function provinceTypes()
    {
        return $this->types()->where('level', 'province');
    }

    public function municipalityTypes()
    {
        return $this->types()->where('level', 'municipality');
    }

    /**
     * Get the cooperative status history entries.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(CooperativeStatusHistory::class, 'coop_id');
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class, 'coop_id');
    }

    public function memberLoans(): HasMany
    {
        return $this->hasMany(MemberLoan::class, 'coop_id');
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'coop_id');
    }

    public function memberSavings(): HasMany
    {
        return $this->hasMany(MemberSavings::class, 'coop_id');
    }

    public function savingsTransactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class, 'coop_id');
    }

    public function externalSupports(): HasMany
    {
        return $this->hasMany(ExternalSupport::class, 'coop_id');
    }

    public function skillInventories(): HasMany
    {
        return $this->hasMany(SkillInventory::class, 'coop_id');
    }

    public function memberServicesAvailed(): HasMany
    {
        return $this->hasMany(MemberServiceAvailed::class, 'coop_id');
    }

    public function officerTermHistories(): HasMany
    {
        return $this->hasMany(OfficerTermHistory::class, 'coop_id');
    }

    public function loanTypes(): HasMany
    {
        return $this->hasMany(LoanType::class, 'cooperative_id');
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class, 'cooperative_id');
    }

    public function pdsSubmissions(): HasMany
    {
        return $this->hasMany(PdsSubmission::class, 'cooperative_id');
    }
}


