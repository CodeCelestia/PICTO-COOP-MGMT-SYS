<?php

namespace App\Listeners;

use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordResetLinkSent;

class LogPasswordResetRequest
{
    /**
     * Handle the event.
     */
    public function handle(PasswordResetLinkSent $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $request = request();

        PasswordResetLog::create([
            'user_id' => $user->id,
            'requested_at' => now(),
            'requested_by' => 'Self',
            'reset_method' => 'Email Link',
            'status' => 'Pending',
            'ip_address' => $request->ip(),
            'remarks' => 'Password reset link sent to ' . $user->email,
        ]);
    }
}
