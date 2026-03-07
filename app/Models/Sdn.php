<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sdn extends Model
{
    use HasFactory;
    protected $table = 'sdns';

    protected $fillable = ['name', 'description', 'contact', 'created_by'];

    /** The user who created this SDN (super_admin) */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** All offices belonging to this SDN */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    /** All users whose primary affiliation is this SDN */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
