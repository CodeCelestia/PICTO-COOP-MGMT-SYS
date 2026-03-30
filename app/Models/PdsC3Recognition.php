<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC3Recognition extends Model
{
    use SoftDeletes;

    protected $table = 'pds_c3_recognitions';

    protected $fillable = [
        'pds_submission_id',
        'recognition',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}

