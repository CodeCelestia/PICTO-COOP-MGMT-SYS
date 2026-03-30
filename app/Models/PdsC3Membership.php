<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC3Membership extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c3_memberships';

    protected $fillable = [
        'pds_submission_id',
        'membership',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

