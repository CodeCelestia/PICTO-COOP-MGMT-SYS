<?php

namespace App\Models;

use App\Models\Concerns\CoopScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserCoopAssignment extends Model
{
    use CoopScoped;
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'coop_id',
        'access_level',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }
}


