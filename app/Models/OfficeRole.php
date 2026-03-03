<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeRole extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
