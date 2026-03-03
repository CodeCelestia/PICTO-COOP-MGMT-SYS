<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OfficeUserRole extends Pivot
{
    protected $table = 'office_user_roles';

    public $incrementing = true;

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function officeRole(): BelongsTo
    {
        return $this->belongsTo(OfficeRole::class, 'office_role_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
