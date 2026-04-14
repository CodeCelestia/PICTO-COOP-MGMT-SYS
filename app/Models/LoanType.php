<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'cooperative_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(MemberLoan::class, 'loan_type_id');
    }
}
