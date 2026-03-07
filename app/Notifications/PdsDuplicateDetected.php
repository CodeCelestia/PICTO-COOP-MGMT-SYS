<?php

namespace App\Notifications;

use App\Models\PdsMergeQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PdsDuplicateDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PdsMergeQueue $mergeQueue) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('sdn-admin.merge-queue.index', absolute: false));

        return (new MailMessage)
            ->subject('Potential Duplicate PDS Detected')
            ->greeting("Hello {$notifiable->name},")
            ->line('A potential duplicate Personal Data Sheet has been detected and queued for your review.')
            ->line("**Match Type:** {$this->mergeQueue->match_type}")
            ->action('Review Merge Queue', $url)
            ->line('Please review and take the appropriate action (approve merge or reject).');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'pds_duplicate_detected',
            'merge_queue_id' => $this->mergeQueue->id,
            'match_type'     => $this->mergeQueue->match_type,
            'source_pds_id'  => $this->mergeQueue->source_pds_id,
            'target_pds_id'  => $this->mergeQueue->target_pds_id,
        ];
    }
}
