<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingParticipant extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'training_id',
        'member_id',
        'officer_id',
        'outcome',
        'certificate_no',
        'certificate_date',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'certificate_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'outcome',
                'certificate_no',
                'certificate_date',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} Training Participant record");
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }
}


