<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a coop_sdn_admin when a super_admin edits data within their SDN scope.
 * Provides an audit trail and visibility into cross-scope administrative actions.
 */
class SuperAdminCrossScopeEdit extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User   $superAdmin,
        private readonly string $modelType,
        private readonly int    $modelId,
        private readonly string $action,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'super_admin_cross_scope_edit',
            'super_admin_id' => $this->superAdmin->id,
            'super_admin'    => $this->superAdmin->name,
            'model_type'     => $this->modelType,
            'model_id'       => $this->modelId,
            'action'         => $this->action,
            'occurred_at'    => now()->toIso8601String(),
        ];
    }
}
