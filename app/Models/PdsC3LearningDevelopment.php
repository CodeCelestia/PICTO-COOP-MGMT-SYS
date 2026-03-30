<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC3LearningDevelopment extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c3_learning_developments';

    protected $fillable = [
        'pds_submission_id',
        'title',
        'date_from',
        'date_to',
        'hours',
        'type',
        'conducted_by',
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

