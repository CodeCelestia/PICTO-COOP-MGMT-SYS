<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $activity_id
 * @property string $category
 * @property int $coop_id
 * @property string $funder_name
 * @property string $funder_type
 * @property string|null $amount_allocated
 * @property string|null $amount_released
 * @property \Illuminate\Support\Carbon|null $date_released
 * @property string|null $status
 * @property string|null $remarks
 * @property array|null $attachment_paths
 * @property array|null $attachment_names
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityFundingSource extends Model
{
    use SoftDeletes;
    use CoopScoped;
    use LogsActivity;

    protected $fillable = [
        'activity_id',
        'category',
        'coop_id',
        'funder_name',
        'funder_type',
        'amount_allocated',
        'amount_released',
        'date_released',
        'status',
        'remarks',
        'attachment_paths',
        'attachment_names',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
            'amount_released' => 'decimal:2',
            'date_released' => 'date',
            'category' => 'string',
            'attachment_paths' => 'array',
            'attachment_names' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'funder_name',
                'funder_type',
                'category',
                'amount_allocated',
                'amount_released',
                'date_released',
                'status',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} Funding Source: ".($this->funder_name ?? 'Unknown'));
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


