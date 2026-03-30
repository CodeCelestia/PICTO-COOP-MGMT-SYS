<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PdsSubmission extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'cooperative_id',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'submitted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "PDS submission has been {$eventName}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }

    public function c1Profile(): HasOne
    {
        return $this->hasOne(PdsC1Profile::class);
    }

    public function c1Children(): HasMany
    {
        return $this->hasMany(PdsC1Child::class);
    }

    public function c1Education(): HasMany
    {
        return $this->hasMany(PdsC1Education::class);
    }

    public function c2Eligibilities(): HasMany
    {
        return $this->hasMany(PdsC2Eligibility::class);
    }

    public function c2WorkExperiences(): HasMany
    {
        return $this->hasMany(PdsC2WorkExperience::class);
    }

    public function c3VoluntaryWorks(): HasMany
    {
        return $this->hasMany(PdsC3VoluntaryWork::class);
    }

    public function c3LearningDevelopments(): HasMany
    {
        return $this->hasMany(PdsC3LearningDevelopment::class);
    }

    public function c3SpecialSkills(): HasMany
    {
        return $this->hasMany(PdsC3SpecialSkill::class);
    }

    public function c3Recognitions(): HasMany
    {
        return $this->hasMany(PdsC3Recognition::class);
    }

    public function c3Memberships(): HasMany
    {
        return $this->hasMany(PdsC3Membership::class);
    }

    public function c4Declaration(): HasOne
    {
        return $this->hasOne(PdsC4Declaration::class);
    }

    public function c4References(): HasMany
    {
        return $this->hasMany(PdsC4Reference::class);
    }

    public function c4GovernmentId(): HasOne
    {
        return $this->hasOne(PdsC4GovernmentId::class);
    }

    public function loadFullPds(): self
    {
        $this->load([
            'c1Profile',
            'c1Children',
            'c1Education',
            'c2Eligibilities',
            'c2WorkExperiences',
            'c3VoluntaryWorks',
            'c3LearningDevelopments',
            'c3SpecialSkills',
            'c3Recognitions',
            'c3Memberships',
            'c4Declaration',
            'c4References',
            'c4GovernmentId',
        ]);

        return $this;
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCoop(Builder $query, int $coopId): Builder
    {
        return $query->where('cooperative_id', $coopId);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('status', 'final');
    }
}


