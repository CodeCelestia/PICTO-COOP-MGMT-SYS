<?php

namespace App\Policies;

use App\Models\PersonalDataSheet;
use App\Models\User;

class PersonalDataSheetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin', 'coop_office_admin']);
    }

    public function view(User $user, PersonalDataSheet $pds): bool
    {
        if ($user->hasRole('super_admin')) return true;

        if ($user->hasRole('coop_sdn_admin')) {
            return $pds->office?->sdn_id === $user->sdn_id;
        }

        if ($user->hasRole('coop_office_admin')) {
            return $pds->office_id === $user->office_id;
        }

        // Members can only view their own PDS
        if ($user->hasRole('member')) {
            return $user->pds_id === $pds->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // All authenticated roles can create (members create their own during onboarding)
        return $user->hasAnyRole(['super_admin', 'coop_sdn_admin', 'coop_office_admin', 'member']);
    }

    public function update(User $user, PersonalDataSheet $pds): bool
    {
        if ($user->hasRole('super_admin')) return true;

        if ($user->hasRole('coop_sdn_admin')) {
            return $pds->office?->sdn_id === $user->sdn_id;
        }

        if ($user->hasRole('coop_office_admin')) {
            return $pds->office_id === $user->office_id;
        }

        if ($user->hasRole('member')) {
            return $user->pds_id === $pds->id;
        }

        return false;
    }

    public function delete(User $user, PersonalDataSheet $pds): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $pds->office?->sdn_id === $user->sdn_id;

        return false;
    }

    /**
     * Admin can create a new user account from this PDS.
     */
    public function createUser(User $user, PersonalDataSheet $pds): bool
    {
        if ($user->hasRole('super_admin')) return true;
        if ($user->hasRole('coop_sdn_admin')) return $pds->office?->sdn_id === $user->sdn_id;
        if ($user->hasRole('coop_office_admin')) return $pds->office_id === $user->office_id;

        return false;
    }
}
