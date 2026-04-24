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
 * @property string|null $description
 * @property string $category
 * @property \Illuminate\Support\Carbon|null $date_started
 * @property \Illuminate\Support\Carbon|null $date_ended
 * @property string $status
 * @property int|null $responsible_officer_id
 * @property string|null $funding_source
 * @property string|null $budget
 * @property string|null $actual_expense
 * @property int|null $target_member_beneficiaries
 * @property int|null $target_community_beneficiaries
 * @property int|null $actual_member_beneficiaries
 * @property int|null $actual_community_beneficiaries
 * @property string|null $venue
 * @property string|null $implementing_partner
 * @property string|null $outcomes
 * @property string|null $outcomes_attachment_path
 * @property string|null $remarks
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    use CoopScoped;
    protected $fillable = [
        'coop_id',
        'title',
        'description',
        'category',
        'date_started',
        'date_ended',
        'status',
        'responsible_officer_id',
        'funding_source',
        'budget',
        'actual_expense',
        'target_member_beneficiaries',
        'target_community_beneficiaries',
        'actual_member_beneficiaries',
        'actual_community_beneficiaries',
        'venue',
        'implementing_partner',
        'outcomes',
        'outcomes_attachment_path',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_started' => 'date',
            'date_ended' => 'date',
            'budget' => 'decimal:2',
            'actual_expense' => 'decimal:2',
        ];
    }

    /**
     * Get the cooperative that owns this activity.
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    /**
     * Get the responsible officer for this activity.
     */
    public function responsibleOfficer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'responsible_officer_id');
    }

    /**
     * Get the participants for this activity.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    /**
     * Get the funding sources for this activity.
     */
    public function fundingSources(): HasMany
    {
        return $this->hasMany(ActivityFundingSource::class);
    }
}


