<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $coop_id
 * @property string $funder_name
 * @property string $funder_type
 * @property string|null $amount_allocated
 * @property string|null $amount_released
 * @property \Illuminate\Support\Carbon|null $date_released
 * @property string|null $status
 * @property string|null $remarks
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityFundingSource extends Model
{
    use SoftDeletes;
    use CoopScoped;
    protected $fillable = [
        'activity_id',
        'coop_id',
        'funder_name',
        'funder_type',
        'amount_allocated',
        'amount_released',
        'date_released',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
            'amount_released' => 'decimal:2',
            'date_released' => 'date',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


