<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $member_id
 * @property int $coop_id
 * @property string $service_type
 * @property string|null $service_detail
 * @property \Illuminate\Support\Carbon|null $date_availed
 * @property string|null $amount
 * @property string|null $status
 * @property string|null $reference_no
 * @property string|null $remarks
 * @property string|null $recorded_by
 */
class MemberServiceAvailed extends Model
{
    use LogsActivity;

    protected $table = 'member_services_availed';

    protected $fillable = [
        'member_id',
        'coop_id',
        'service_type',
        'service_detail',
        'date_availed',
        'amount',
        'status',
        'reference_no',
        'remarks',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date_availed' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['service_type', 'status', 'amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Service availment has been {$eventName}");
    }

    /**
     * Get the member who availed this service
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the cooperative
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}
