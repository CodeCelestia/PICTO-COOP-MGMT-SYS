<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pds_id',
        'sdn_id',
        'office_id',
        'department_id',
        'status',
        'role_request',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role_request'         => 'array',
            'must_change_password' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'pds_id');
    }

    /** The SDN this user directly belongs to (for coop_sdn_admin) */
    public function sdn(): BelongsTo
    {
        return $this->belongsTo(Sdn::class);
    }

    /** The primary office this user belongs to */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** Member profile linked to this user */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /** All offices this user is assigned to (with office roles) */
    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'office_user_roles')
            ->using(OfficeUserRole::class)
            ->withPivot('office_role_id', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isActive(): bool    { return $this->status === 'active'; }
    public function isSuspended(): bool { return $this->status === 'suspended'; }
    public function hasPds(): bool      { return !is_null($this->pds_id); }
}
