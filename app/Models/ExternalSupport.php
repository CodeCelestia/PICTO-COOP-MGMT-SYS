<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $coop_id
 * @property int|null $financial_record_id
 * @property string $support_type
 * @property string $provider_name
 * @property string|null $amount
 * @property \Illuminate\Support\Carbon|null $date_granted
 * @property \Illuminate\Support\Carbon|null $date_completed
 * @property string|null $status
 * @property string|null $remarks
 */
class ExternalSupport extends Model
{
    protected $fillable = [
        'coop_id',
        'financial_record_id',
        'support_type',
        'provider_name',
        'amount',
        'date_granted',
        'date_completed',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date_granted' => 'date',
            'date_completed' => 'date',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }
}
