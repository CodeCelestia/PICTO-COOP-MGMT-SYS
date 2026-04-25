<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LoanPayment extends Model
{
    use SoftDeletes;
    use CoopScoped;
    use LogsActivity;

    protected $fillable = [
        'loan_id',
        'coop_id',
        'payment_number',
        'principal_due',
        'interest_due',
        'total_due',
        'amount_paid',
        'due_date',
        'paid_at',
        'balance_after',
        'status',
        'remarks',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'principal_due' => 'decimal:2',
            'interest_due' => 'decimal:2',
            'total_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'payment_number',
                'principal_due',
                'interest_due',
                'amount_paid',
                'due_date',
                'paid_at',
                'balance_after',
                'status',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Loan payment has been {$eventName}");
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(MemberLoan::class, 'loan_id');
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
