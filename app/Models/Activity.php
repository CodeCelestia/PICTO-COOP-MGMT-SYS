<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'office_id',
        'title',
        'description',
        'type',
        'activity_date',
        'start_time',
        'end_time',
        'venue',
        'venue_address',
        'budget',
        'expected_participants',
        'actual_participants',
        'status',
        'objectives',
        'outcomes',
        'organized_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'budget' => 'decimal:2',
        'expected_participants' => 'integer',
        'actual_participants' => 'integer',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organized_by');
    }

    public function activityParticipants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'activity_participants')
            ->withPivot(['attendance_status', 'registered_at', 'attended_at'])
            ->withTimestamps();
    }
}
