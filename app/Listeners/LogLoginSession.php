<?php

namespace App\Listeners;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;

class LogLoginSession
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $request = request();

        $sessionToken = session()->getId();

        // Create or refresh login session record for this session token
        LoginSession::updateOrCreate(
            ['session_token' => $sessionToken],
            [
                'user_id' => $user->id,
                'login_at' => now(),
                'logout_at' => null,
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
                'login_status' => 'Success',
                'fail_reason' => null,
            ]
        );

        // Update user's last login timestamp
        User::where('id', $user->id)->update([
            'last_login_at' => now(),
        ]);
    }
}
