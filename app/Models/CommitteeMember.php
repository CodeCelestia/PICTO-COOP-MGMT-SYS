<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $coop_id
 * @property int $member_id
 * @property string $committee_name
 * @property string|null $role
 * @property \Illuminate\Support\Carbon|null $date_assigned
 * @property \Illuminate\Support\Carbon|null $date_removed
 * @property string $status
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class CommitteeMember extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'coop_id',
        'member_id',
        'committee_name',
        'role',
        'date_assigned',
        'date_removed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_assigned' => 'date',
            'date_removed' => 'date',
        ];
    }

    /**
     * Get the member for this committee assignment.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the cooperative for this committee assignment.
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


