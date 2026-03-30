<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC3VoluntaryWork extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c3_voluntary_works';

    protected $fillable = [
        'pds_submission_id',
        'organization',
        'date_from',
        'date_to',
        'hours',
        'position',
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

