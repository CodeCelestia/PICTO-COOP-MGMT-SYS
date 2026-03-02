<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pds_id',
        'user_id',
        'office_id',
        'member_number',
        'member_type',
        'status',
        'date_joined',
        'date_approved',
        'date_left',
        'share_capital',
        'savings_balance',
        'loan_balance',
        'occupation',
        'employer',
        'monthly_income',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date_joined' => 'date',
        'date_approved' => 'date',
        'date_left' => 'date',
        'share_capital' => 'decimal:2',
        'savings_balance' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'monthly_income' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function pds(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'pds_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function committees(): BelongsToMany
    {
        return $this->belongsToMany(Committee::class, 'committee_members')
            ->withPivot(['position', 'appointed_date', 'status'])
            ->withTimestamps();
    }

    public function activityParticipants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_participants')
            ->withPivot(['attendance_status', 'registered_at', 'attended_at'])
            ->withTimestamps();
    }
}
