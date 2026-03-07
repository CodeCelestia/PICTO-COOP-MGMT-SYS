<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     * Collects minimal: name, email, password.
     * Optionally accepts office_id if that office allows self-registration.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password'  => $this->passwordRules(),
            'office_id' => 'nullable|exists:offices,id',
        ])->validate();

        // Validate that the chosen office permits self-registration
        $officeId = null;
        $sdnId    = null;
        if (!empty($input['office_id'])) {
            $office = Office::find($input['office_id']);
            if ($office && $office->allow_self_registration) {
                $officeId = $office->id;
                $sdnId    = $office->sdn_id;
            }
        }

        $user = User::create([
            'name'      => $input['name'],
            'email'     => $input['email'],
            'password'  => $input['password'],
            'office_id' => $officeId,
            'sdn_id'    => $sdnId,
            'status'    => 'pending',  // Activated after PDS completion
        ]);

        $user->assignRole('member');

        activity('registration')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['office_id' => $officeId, 'sdn_id' => $sdnId])
            ->log('New member registered — PDS pending');

        return $user;
    }
}
