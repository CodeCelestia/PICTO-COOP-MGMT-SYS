<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdsC4Declaration extends Model
{
    use HasFactory;

    protected $table = 'pds_c4_declarations';

    protected $fillable = [
        'pds_submission_id',
        'q34',
        'q34_details',
        'q35',
        'q35_details',
        'q36',
        'q36_details',
        'q37',
        'q37_details',
        'q38a',
        'q38a_details',
        'q38b',
        'q38b_details',
        'q39',
        'q39_details',
        'q40a',
        'q40a_details',
        'q40b',
        'q40b_details',
        'q41',
        'q41_details',
        'signature_date',
    ];

    protected $casts = [
        'q34' => 'boolean',
        'q35' => 'boolean',
        'q36' => 'boolean',
        'q37' => 'boolean',
        'q38a' => 'boolean',
        'q38b' => 'boolean',
        'q39' => 'boolean',
        'q40a' => 'boolean',
        'q40b' => 'boolean',
        'q41' => 'boolean',
        'signature_date' => 'date',
    ];

    public function pdsSubmission(): BelongsTo
    {
        return $this->belongsTo(PdsSubmission::class);
    }
}
