<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $coop_id
 * @property string $title
 * @property \Illuminate\Support\Carbon|null $date_conducted
 * @property string|null $facilitator
 * @property string|null $skills_targeted
 * @property string|null $venue
 * @property string $target_group
 * @property int|null $no_of_participants
 * @property bool $follow_up_needed
 * @property \Illuminate\Support\Carbon|null $follow_up_date
 * @property string|null $follow_up_remarks
 * @property string $status
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use SoftDeletes;
    use CoopScoped;
    protected $fillable = [
        'coop_id',
        'title',
        'date_conducted',
        'facilitator',
        'skills_targeted',
        'venue',
        'target_group',
        'no_of_participants',
        'follow_up_needed',
        'follow_up_date',
        'follow_up_remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_conducted' => 'date',
            'follow_up_date' => 'date',
            'follow_up_needed' => 'boolean',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    public function skillsInventory(): HasMany
    {
        return $this->hasMany(SkillInventory::class);
    }
}


