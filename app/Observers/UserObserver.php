<?php

namespace App\Observers;

use App\Models\AccountStatusHistory;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Log initial account status when user is created
        if ($user->account_status) {
            AccountStatusHistory::create([
                'user_id' => $user->id,
                'previous_status' => 'Pending Approval',
                'new_status' => $user->account_status,
                'change_reason' => 'Account created',
                'changed_by' => $user->created_by ?? 'System',
                'changed_at' => now(),
                'remarks' => 'Initial account status set during creation',
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Check if account_status has changed
        if ($user->isDirty('account_status') && $user->getOriginal('account_status') !== null) {
            $previousStatus = $user->getOriginal('account_status');
            $newStatus = $user->account_status;

            AccountStatusHistory::create([
                'user_id' => $user->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'change_reason' => 'Account status updated',
                'changed_by' => auth()->user()?->name ?? 'System',
                'changed_at' => now(),
                'remarks' => "Status changed from {$previousStatus} to {$newStatus}",
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
