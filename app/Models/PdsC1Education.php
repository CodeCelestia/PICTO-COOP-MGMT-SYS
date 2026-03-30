<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC1Education extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c1_education';

    protected $fillable = [
        'pds_submission_id',
        'level',
        'school_name',
        'degree',
        'from',
        'to',
        'units',
        'year_graduated',
        'honors',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

