<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class CooperativeStatusHistory extends Model
{
    use SoftDeletes;
    use CoopScoped;
    public $timestamps = false;

    protected $table = 'cooperative_status_history';

    protected $fillable = [
        'coop_id',
        'previous_status',
        'new_status',
        'change_reason',
        'changed_by',
        'changed_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the cooperative for this status history entry.
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


