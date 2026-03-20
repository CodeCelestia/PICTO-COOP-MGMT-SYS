<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $coop_id
 * @property int|null $member_id
 * @property int|null $officer_id
 * @property string|null $account_type
 * @property string|null $account_status
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'coop_id',
        'member_id',
        'officer_id',
        'account_type',
        'account_status',
        'profile_photo',
        'last_login_at',
        'password_changed_at',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $appends = [
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * Get the full avatar URL for the user
     */
    public function getAvatarAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        if (Str::startsWith($this->profile_photo, ['http://', 'https://'])) {
            return $this->profile_photo;
        }

        return asset('storage/' . ltrim($this->profile_photo, '/'));
    }
    
    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'account_type', 'account_status', 'coop_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User has been {$eventName}");
    }
    
    // Spatie HasRoles trait provides these methods automatically:
    // - hasRole(), assignRole(), removeRole(), syncRoles(), getRoleNames()
    // - hasPermissionTo(), givePermissionTo(), revokePermissionTo()
    // - hasAnyRole(), hasAllRoles(), etc.

    /**
     * Get the cooperative this user belongs to
     */
    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class, 'coop_id');
    }

    /**
     * Get the member profile for this user
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the coop assignments for the user
     */
    public function coopAssignments()
    {
        return $this->hasMany(UserCoopAssignment::class);
    }

    /**
     * Get the login sessions for the user
     */
    public function loginSessions()
    {
        return $this->hasMany(LoginSession::class);
    }

    /**
     * Get the password reset logs for the user
     */
    public function passwordResetLogs()
    {
        return $this->hasMany(PasswordResetLog::class);
    }

    /**
     * Get the account status history for the user
     */
    public function accountStatusHistories()
    {
        return $this->hasMany(AccountStatusHistory::class);
    }
}
