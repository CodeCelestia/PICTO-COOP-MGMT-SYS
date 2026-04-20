<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accreditation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cooperative_id',
        'level',
        'date_granted',
        'valid_until',
        'issuing_body',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_granted' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class, 'cooperative_id');
    }
}
