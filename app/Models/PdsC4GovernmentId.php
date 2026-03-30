<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdsC4GovernmentId extends Model
{
    use HasFactory;

    protected $table = 'pds_c4_government_ids';

    protected $fillable = [
        'pds_submission_id',
        'govt_id_type',
        'govt_id_no',
        'id_issue_date',
        'id_issue_place',
    ];

    protected $casts = [
        'id_issue_date' => 'date',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}
