<?php

namespace App\Listeners;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $request = request();
        /** @var User|null $user */
        $user = $event->user;

        // Only log if we have a user (failed password attempt)
        if ($user) {
            LoginSession::create([
                'user_id' => $user->id,
                'login_at' => now(),
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
                'login_status' => 'Failed',
                'fail_reason' => 'Invalid credentials',
            ]);
        }
    }
}
