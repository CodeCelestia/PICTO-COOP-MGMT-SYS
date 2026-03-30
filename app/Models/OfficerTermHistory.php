<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class OfficerTermHistory extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $table = 'officer_term_history';

    protected $fillable = [
        'officer_id',
        'member_id',
        'coop_id',
        'position',
        'committee',
        'term_start',
        'term_end',
        'status',
        'reason_for_change',
        'election_year',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'recorded_at' => 'datetime',
            'election_year' => 'integer',
        ];
    }

    /**
     * Get the officer record for this term entry.
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * Get the member for this term entry.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the cooperative for this term entry.
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


