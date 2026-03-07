<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin', 'coop_office_admin']);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $target->sdn_id === $user->sdn_id;
        if ($user->hasRole('coop_office_admin')) return $target->office_id === $user->office_id;

        // Users can always view themselves
        return $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin', 'coop_office_admin']);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $target->sdn_id === $user->sdn_id;
        if ($user->hasRole('coop_office_admin')) return $target->office_id === $user->office_id;

        // Users can update themselves (settings)
        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        // Cannot delete yourself
        if ($user->id === $target->id) return false;

        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $target->sdn_id === $user->sdn_id;

        return false;
    }

    /**
     * Only super_admin can impersonate, and never impersonate another super_admin.
     */
    public function impersonate(User $user, User $target): bool
    {
        return $user->hasRole('super_admin') && !$target->hasRole('super_admin');
    }

    /**
     * Reset another user's password.
     */
    public function resetPassword(User $user, User $target): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $target->sdn_id === $user->sdn_id;
        if ($user->hasRole('coop_office_admin')) return $target->office_id === $user->office_id;

        return false;
    }
}
