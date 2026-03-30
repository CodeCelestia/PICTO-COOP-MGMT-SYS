<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC2WorkExperience extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c2_work_experiences';

    protected $fillable = [
        'pds_submission_id',
        'date_from',
        'date_to',
        'position_title',
        'dept_agency',
        'monthly_salary',
        'salary_grade',
        'status_appointment',
        'govt_service',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

