<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $member_id
 * @property int $coop_id
 * @property string|null $position
 * @property string|null $committee
 * @property \Illuminate\Support\Carbon|null $term_start
 * @property \Illuminate\Support\Carbon|null $term_end
 * @property string|null $status
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Officer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'member_id',
        'coop_id',
        'position',
        'committee',
        'term_start',
        'term_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
        ];
    }

    /**
     * Get the member for this officer record.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the cooperative for this officer record.
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    /**
     * Get the term history entries for this officer.
     */
    public function termHistory(): HasMany
    {
        return $this->hasMany(OfficerTermHistory::class);
    }

    /**
     * Get training participant entries for this officer.
     */
    public function trainingParticipants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }
}


