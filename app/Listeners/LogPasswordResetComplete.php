<?php

namespace App\Listeners;

use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class LogPasswordResetComplete
{
    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $request = request();

        // Find the most recent pending reset log for this user and mark it completed
        $resetLog = PasswordResetLog::where('user_id', $user->id)
            ->where('status', 'Pending')
            ->latest('requested_at')
            ->first();

        if ($resetLog) {
            $resetLog->update([
                'status' => 'Completed',
                'completed_at' => now(),
            ]);
        }

        // Update user's password_changed_at timestamp
        User::where('id', $user->id)->update([
            'password_changed_at' => now(),
        ]);
    }
}
