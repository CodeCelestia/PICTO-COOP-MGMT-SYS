<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $coop_id
 * @property string $period
 * @property string $type
 * @property string $amount
 * @property string|null $source
 * @property string|null $purpose
 * @property \Illuminate\Support\Carbon|null $date_recorded
 * @property string|null $total_assets
 * @property string|null $total_liabilities
 * @property string|null $net_surplus
 * @property string|null $capital_build_up
 * @property string|null $external_assistance_received
 * @property string|null $type_of_assistance
 * @property string|null $reference_doc
 * @property string|null $recorded_by
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialRecord extends Model
{
    use SoftDeletes;
    use CoopScoped;
    protected $fillable = [
        'coop_id',
        'period',
        'type',
        'amount',
        'source',
        'purpose',
        'date_recorded',
        'total_assets',
        'total_liabilities',
        'net_surplus',
        'capital_build_up',
        'external_assistance_received',
        'type_of_assistance',
        'reference_doc',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_surplus' => 'decimal:2',
            'capital_build_up' => 'decimal:2',
            'external_assistance_received' => 'decimal:2',
            'date_recorded' => 'date',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function externalSupports(): HasMany
    {
        return $this->hasMany(ExternalSupport::class);
    }
}


