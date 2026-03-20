<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSectorHistory extends Model
{
    public $timestamps = false;

    protected $table = 'member_sector_history';

    protected $fillable = [
        'member_id',
        'previous_sector',
        'new_sector',
        'previous_livelihood',
        'new_livelihood',
        'change_reason',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the member
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
