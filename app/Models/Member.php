<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property-read int $id
 * @property int $coop_id
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $gender
 * @property string|null $address
 * @property string|null $region
 * @property string|null $province
 * @property string|null $city_municipality
 * @property string|null $barangay
 * @property string|null $phone
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $date_joined
 * @property string|null $membership_type
 * @property string|null $membership_status
 * @property string|null $share_capital
 * @property string|null $educational_attainment
 * @property string|null $primary_livelihood
 * @property string|null $sector
 * @property-read string $full_name
 */
class Member extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use CoopScoped;

    protected $appends = [
        'full_name',
        'age',
    ];

    protected $fillable = [
        'coop_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'address',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'phone',
        'email',
        'date_joined',
        'membership_type',
        'membership_status',
        'share_capital',
        'educational_attainment',
        'primary_livelihood',
        'sector',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'datetime',
            'date_joined' => 'datetime',
            'share_capital' => 'decimal:2',
        ];
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'membership_status',
                'membership_type',
                'sector',
                'primary_livelihood',
                'share_capital'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Member has been {$eventName}");
    }

    /**
     * Get the cooperative this member belongs to
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    /**
     * Get all services availed by this member
     */
    public function servicesAvailed(): HasMany
    {
        return $this->hasMany(MemberServiceAvailed::class);
    }

    /**
     * Get the sector history for this member
     */
    public function sectorHistory(): HasMany
    {
        return $this->hasMany(MemberSectorHistory::class);
    }

    /**
     * Get activity participant entries for this member.
     */
    public function activityParticipants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    /**
     * Get training participant entries for this member.
     */
    public function trainingParticipants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    /**
     * Get officer assignments for this member.
     */
    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }

    /**
     * Get skill inventory entries for this member.
     */
    public function skillInventories(): HasMany
    {
        return $this->hasMany(SkillInventory::class);
    }

    /**
     * Get the linked user account for this member
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'member_id');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get age attribute
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->diffInYears(now()) : null;
    }
}


