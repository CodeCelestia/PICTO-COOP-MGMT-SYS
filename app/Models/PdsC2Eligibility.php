<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC2Eligibility extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c2_eligibilities';

    protected $fillable = [
        'pds_submission_id',
        'name',
        'rating',
        'exam_date',
        'exam_place',
        'license_number',
        'license_validity',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'license_validity' => 'date',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

