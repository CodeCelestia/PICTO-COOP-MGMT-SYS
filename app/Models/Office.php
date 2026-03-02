<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Office extends Model
{
    protected $fillable = [
        'name',
        'code',
        'cooperative_type',
        'registration_number',
        'date_registered',
        'asset_size',
        'classification',
        'status',
        'key_services',
        'year_of_latest_audit',
        'chairperson',
        'general_manager',
        'region_code',
        'region_name',
        'province_code',
        'province_name',
        'city_municipality_code',
        'city_municipality_name',
        'barangay_code',
        'barangay_name',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'key_services'    => 'array',
        'date_registered' => 'date:Y-m-d',
        'asset_size'      => 'float',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'office_user_roles')
            ->withPivot('role_name', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }
}

