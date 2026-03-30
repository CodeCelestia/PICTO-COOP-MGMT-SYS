<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC3SpecialSkill extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c3_special_skills';

    protected $fillable = [
        'pds_submission_id',
        'skill',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

