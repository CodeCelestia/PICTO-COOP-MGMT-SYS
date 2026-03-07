<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PersonalDataSheet extends Model
{
    use HasFactory;
    protected $fillable = [
        // Office relationship
        'office_id',
        
        // Basic identity
        'first_name', 'middle_name', 'last_name', 'name_extension',
        'date_of_birth', 'place_of_birth', 'gender', 'civil_status',
        'height', 'weight', 'blood_type',
        'citizenship', 'dual_citizenship_type', 'dual_country',

        // Government IDs & contact
        'gsis_id', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no',
        'telephone_no', 'phone_number', 'email',

        // Residential address
        'region_code', 'region_name', 'province_code', 'province_name',
        'city_municipality_code', 'city_municipality_name',
        'barangay_code', 'barangay_name',
        'street_address', 'res_house', 'res_subdivision', 'res_zip',

        // Permanent address
        'perm_same_as_res',
        'perm_house', 'perm_street', 'perm_subdivision', 'perm_zip',
        'perm_region_code', 'perm_region_name',
        'perm_province_code', 'perm_province_name',
        'perm_city_municipality_code', 'perm_city_municipality_name',
        'perm_barangay_code', 'perm_barangay_name',

        // Spouse
        'spouse_surname', 'spouse_first_name', 'spouse_middle_name', 'spouse_name_extension',
        'spouse_occupation', 'spouse_employer', 'spouse_business_address', 'spouse_telephone',

        // Father
        'father_surname', 'father_first_name', 'father_middle_name', 'father_name_extension',

        // Mother
        'mother_surname', 'mother_first_name', 'mother_middle_name',

        // JSON sub-records
        'children', 'education', 'eligibilities', 'work_experiences',
        'voluntary_works', 'learning_developments', 'references_list',

        // Other information
        'special_skills', 'non_academic_distinctions', 'memberships', 'questions',

        // Government-issued ID & declaration
        'government_issued_id', 'id_no', 'id_issue_place', 'date_accomplished',

        // Audit fields
        'created_by',
        'duplicate_of',
    ];

    protected $casts = [
        'date_of_birth'       => 'date:Y-m-d',
        'date_accomplished'   => 'date:Y-m-d',
        'perm_same_as_res'    => 'boolean',
        'children'            => 'array',
        'education'           => 'array',
        'eligibilities'       => 'array',
        'work_experiences'    => 'array',
        'voluntary_works'     => 'array',
        'learning_developments' => 'array',
        'references_list'     => 'array',
        'questions'           => 'array',
        'height'              => 'float',
        'weight'              => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        // Sync email changes to linked user account
        static::updated(function ($pds) {
            if ($pds->isDirty('email') && $pds->user) {
                $pds->user->update(['email' => $pds->email]);
                
                activity('pds_sync')
                    ->performedOn($pds)
                    ->withProperties([
                        'old_email' => $pds->getOriginal('email'),
                        'new_email' => $pds->email,
                        'user_id' => $pds->user->id,
                    ])
                    ->log('pds.email_synced_to_user');
            }
        });
    }

    public function createdByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function originalPds(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'duplicate_of');
    }

    public function mergeQueueEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PdsMergeQueue::class, 'source_pds_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'pds_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'pds_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : '') . "{$this->last_name}" . ($this->name_extension ? " {$this->name_extension}" : ''));
    }

    /**
     * Check if the PDS is complete based on required fields
     */
    public function isComplete(): bool
    {
        $requiredFields = ['first_name', 'last_name', 'date_of_birth', 'gender', 'email', 'citizenship'];
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) return false;
        }
        if (empty($this->city_municipality_name) && empty($this->province_name)) return false;
        return true;
    }

    /**
     * Get the completion percentage of the PDS
     */
    public function getCompletionPercentage(): int
    {
        $totalFields = 0; $filledFields = 0;
        $basicFields = ['first_name', 'last_name', 'date_of_birth', 'gender', 'email', 'citizenship', 'phone_number'];
        foreach ($basicFields as $field) { $totalFields++; if (!empty($this->$field)) $filledFields++; }
        $addressFields = ['province_name', 'city_municipality_name', 'barangay_name'];
        foreach ($addressFields as $field) { $totalFields++; if (!empty($this->$field)) $filledFields++; }
        $education = $this->education ?? []; $totalFields += 3;
        if (is_array($education)) {
            $count = 0;
            foreach ($education as $edu) {
                if (!empty($edu['school_name']) || !empty($edu['degree_course'])) { $filledFields++; $count++; if ($count >= 3) break; }
            }
        }
        $references = $this->references_list ?? []; $totalFields += 3;
        if (is_array($references)) {
            $count = 0;
            foreach ($references as $ref) {
                if (!empty($ref['name'])) { $filledFields++; $count++; if ($count >= 3) break; }
            }
        }
        return $totalFields > 0 ? (int) round(($filledFields / $totalFields) * 100) : 0;
    }
}