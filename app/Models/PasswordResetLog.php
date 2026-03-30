<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordResetLog extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'requested_at',
        'requested_by',
        'reset_method',
        'status',
        'completed_at',
        'ip_address',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


