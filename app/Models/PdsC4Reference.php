<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsC4Reference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pds_c4_references';

    protected $fillable = [
        'pds_submission_id',
        'name',
        'address',
        'tel_no',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}
