<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class SkillInventory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'member_id',
        'coop_id',
        'skill_name',
        'proficiency_level',
        'training_id',
        'date_acquired',
        'last_updated',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'last_updated' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}


