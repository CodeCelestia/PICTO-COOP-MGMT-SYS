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

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'registration_number', 'status', 'province'])
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

    /**
     * Get the activities for this cooperative.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'coop_id');
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

    public function loanTypes(): HasMany
    {
        return $this->hasMany(LoanType::class, 'cooperative_id');
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class, 'cooperative_id');
    }
}


