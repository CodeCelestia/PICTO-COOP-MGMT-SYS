<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Committee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'office_id',
        'name',
        'code',
        'type',
        'description',
        'term_years',
        'term_start',
        'term_end',
        'status',
        'responsibilities',
    ];

    protected $casts = [
        'term_start' => 'date',
        'term_end' => 'date',
        'term_years' => 'integer',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'committee_members')
            ->withPivot(['position', 'appointed_date', 'status'])
            ->withTimestamps();
    }
}
