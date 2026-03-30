<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CooperativeType extends Model
{
    use SoftDeletes;

    protected $table = 'cooperative_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'parent_id',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'string',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CooperativeType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CooperativeType::class, 'parent_id');
    }

    public function cooperatives(): BelongsToMany
    {
        return $this->belongsToMany(Cooperative::class, 'cooperative_cooperative_type')
                    ->withTimestamps();
    }

    public function scopeRegions($query)
    {
        return $query->where('level', 'region');
    }

    public function scopeProvinces($query)
    {
        return $query->where('level', 'province');
    }

    public function scopeMunicipalities($query)
    {
        return $query->where('level', 'municipality');
    }

    public function scopeDescendants($query, $typeId)
    {
        $type = $this->find($typeId);
        if (!$type) {
            return $query->whereRaw('0 = 1');
        }

        $childIds = $type->children()->pluck('id')->toArray();

        if ($type->level === 'region') {
            $grandChildren = CooperativeType::whereIn('parent_id', $childIds)->pluck('id')->toArray();
            $childIds = array_merge($childIds, $grandChildren);
        }

        return $query->whereIn('id', $childIds);
    }

    protected static function booted()
    {
        static::deleting(function (CooperativeType $type) {
            // soft-cascade children to preserve hierarchy consistency
            $type->children()->get()->each(function (CooperativeType $child) {
                $child->delete();
            });
        });
    }
}

