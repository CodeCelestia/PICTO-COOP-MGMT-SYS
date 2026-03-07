<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdsMergeQueue extends Model
{
    protected $table = 'pds_merge_queue';

    protected $fillable = [
        'match_type',
        'source_pds_id',
        'target_pds_id',
        'triggered_by',
        'reviewed_by',
        'reviewed_at',
        'status',
        'notes',
        'sdn_id',
        'office_id',
        'match_context',
    ];

    protected $casts = [
        'match_context' => 'array',
        'reviewed_at'   => 'datetime',
    ];

    public function sourcePds(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'source_pds_id');
    }

    public function targetPds(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'target_pds_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sdn(): BelongsTo
    {
        return $this->belongsTo(Sdn::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
