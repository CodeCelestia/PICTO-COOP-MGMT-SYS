<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

class Officer extends Model
{
    use SoftDeletes;
    use CoopScoped;
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'position',
                'committee',
                'term_start',
                'term_end',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} Officer record");
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopePosition(Builder $query, string $position): Builder
    {
        return $query->whereRaw('LOWER(position) = ?', [Str::lower($position)]);
    }

    public function scopePositionIn(Builder $query, array $positions): Builder
    {
        return $query->where(function (Builder $query) use ($positions) {
            foreach ($positions as $position) {
                $query->orWhereRaw('LOWER(position) = ?', [Str::lower($position)]);
            }
        });
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


