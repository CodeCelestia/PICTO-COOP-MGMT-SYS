<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageCarouselPhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path',
        'original_name',
        'is_default',
        'is_enabled',
        'is_core',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'is_core' => 'boolean',
        ];
    }
}
