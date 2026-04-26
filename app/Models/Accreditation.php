<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Accreditation extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'cooperative_id',
        'level',
        'date_granted',
        'valid_until',
        'issuing_body',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_granted' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'level',
                'date_granted',
                'valid_until',
                'issuing_body',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} Accreditation record");
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }
}
