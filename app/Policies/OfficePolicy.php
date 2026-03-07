<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

class OfficePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin', 'coop_office_admin']);
    }

    public function view(User $user, Office $office): bool
    {
        return match (true) {
            $user->hasRole('super_admin')         => true,
            $user->hasRole('coop_sdn_admin')      => $user->sdn_id === $office->sdn_id,
            $user->hasRole('coop_office_admin')  => $user->office_id === $office->id,
            default                                => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin']);
    }

    public function update(User $user, Office $office): bool
    {
        if ($user->hasRole('super_admin')) {
            // Cross-scope edits are allowed but must be audited (logged in controller)
            return true;
        }

        if ($user->hasRole('coop_sdn_admin')) {
            return $user->sdn_id === $office->sdn_id;
        }

        if ($user->hasRole('coop_office_admin')) {
            return $user->office_id === $office->id;
        }

        return false;
    }

    public function delete(User $user, Office $office): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $user->sdn_id === $office->sdn_id;

        return false;
    }
}
