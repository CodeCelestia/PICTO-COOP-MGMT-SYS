<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MemberSavings extends Model
{
    use SoftDeletes;
    use CoopScoped;
    use LogsActivity;

    protected $fillable = [
        'coop_id',
        'member_id',
        'account_number',
        'account_status',
        'current_balance',
        'interest_rate',
        'opened_at',
        'closed_at',
        'last_interest_calculated',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'opened_at' => 'date',
            'closed_at' => 'date',
            'last_interest_calculated' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['coop_id', 'member_id', 'account_status', 'current_balance', 'interest_rate'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Member savings has been {$eventName}");
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class, 'member_savings_id');
    }
}
