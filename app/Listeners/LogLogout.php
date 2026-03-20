<?php

namespace App\Listeners;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $sessionId = session()->getId();

        // Find the current session and update logout time
        LoginSession::where('user_id', $user->id)
            ->where('session_token', $sessionId)
            ->whereNull('logout_at')
            ->update([
                'logout_at' => now(),
            ]);
    }
}
