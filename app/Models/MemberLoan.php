<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MemberLoan extends Model
{
    use SoftDeletes;
    use CoopScoped;
    use LogsActivity;

    protected $fillable = [
        'coop_id',
        'member_id',
        'principal',
        'interest_rate',
        'term_months',
        'status',
        'purpose',
        'approved_by',
        'approved_at',
        'remarks',
        'disbursement_date',
        'amount_disbursed',
        'disbursement_method',
        'disburse_remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'amount_disbursed' => 'decimal:2',
            'approved_at' => 'datetime',
            'disbursement_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'coop_id',
                'member_id',
                'principal',
                'interest_rate',
                'term_months',
                'status',
                'approved_by',
                'amount_disbursed',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Member loan has been {$eventName}");
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_id');
    }

    public function getRemainingBalance(): float
    {
        $baseAmount = (float) ($this->amount_disbursed ?? $this->principal);
        $paidAmount = (float) $this->payments()->whereIn('status', ['Paid', 'Partial'])->sum('amount_paid');

        return max(0, $baseAmount - $paidAmount);
    }

    public function getRepaymentSchedule()
    {
        return $this->payments()
            ->whereNotNull('payment_number')
            ->orderBy('payment_number')
            ->get();
    }

    public function getNextPaymentDue(): ?LoanPayment
    {
        return $this->payments()
            ->whereNotNull('payment_number')
            ->where('status', 'Pending')
            ->orderBy('due_date')
            ->first();
    }
}
