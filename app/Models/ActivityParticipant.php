<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipant extends Model
{
    protected $fillable = [
        'activity_id',
        'member_id',
        'role',
        'date_joined',
        'is_beneficiary',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_joined' => 'date',
            'is_beneficiary' => 'boolean',
        ];
    }

    /**
     * Get the activity for this participant entry.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the member for this participant entry.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
