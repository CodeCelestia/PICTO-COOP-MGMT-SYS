<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use HasFactory;
    protected $fillable = [
        'sdn_id',
        'name',
        'code',
        'cooperative_type',
        'registration_number',
        'date_registered',
        'asset_size',
        'classification',
        'status',
        'allow_self_registration',
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
        'key_services'             => 'array',
        'date_registered'          => 'date:Y-m-d',
        'asset_size'               => 'float',
        'allow_self_registration'  => 'boolean',
    ];

    public function sdn(): BelongsTo
    {
        return $this->belongsTo(Sdn::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'office_user_roles')
            ->using(OfficeUserRole::class)
            ->withPivot('office_role_id', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function personalDataSheets(): HasMany
    {
        return $this->hasMany(PersonalDataSheet::class);
    }
}
