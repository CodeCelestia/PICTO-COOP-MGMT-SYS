<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PersonalDataSheet extends Model
{
    protected $fillable = [
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

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'pds_id');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'pds_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : '') . "{$this->last_name}" . ($this->name_extension ? " {$this->name_extension}" : ''));
    }
}