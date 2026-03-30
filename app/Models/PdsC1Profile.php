<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC1Profile extends Model
{
    use SoftDeletes;
    protected $table = 'pds_c1_profiles';

    protected $fillable = [
        'pds_submission_id',
        'surname', 'first_name', 'middle_name', 'name_extension',
        'date_of_birth', 'place_of_birth', 'sex', 'civil_status',
        'citizenship', 'dual_country', 'dual_citizenship_type',
        'height', 'weight', 'blood_type', 'umid_id', 'pagibig_id',
        'philhealth_no', 'philsys_no', 'tin_no', 'agency_employee_no',
        'telephone_no', 'mobile_no', 'email',
        'res_house_no', 'res_street', 'res_subdivision', 'res_barangay',
        'res_city', 'res_province', 'res_zipcode',
        'perm_house_no', 'perm_street', 'perm_subdivision', 'perm_barangay',
        'perm_city', 'perm_province', 'perm_zipcode',
        'spouse_surname', 'spouse_firstname', 'spouse_middlename', 'spouse_extension',
        'spouse_occupation', 'spouse_employer', 'spouse_business_addr', 'spouse_telephone',
        'father_surname', 'father_firstname', 'father_middlename', 'father_extension',
        'mother_surname', 'mother_firstname', 'mother_middlename', 'mother_extension',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}


