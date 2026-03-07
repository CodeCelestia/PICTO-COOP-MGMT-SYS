<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPendingMember extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Member $member,
        private readonly User   $applicant,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('office-admin.pds.index', absolute: false));

        return (new MailMessage)
            ->subject('New Membership Application Pending Review')
            ->greeting("Hello {$notifiable->name},")
            ->line("A new membership application has been submitted and is awaiting your review.")
            ->line("**Applicant:** {$this->applicant->name}")
            ->line("**Email:** {$this->applicant->email}")
            ->action('Review Application', $url)
            ->line('Please log in to approve or reject this application.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'new_pending_member',
            'member_id'    => $this->member->id,
            'applicant_id' => $this->applicant->id,
            'name'         => $this->applicant->name,
            'email'        => $this->applicant->email,
        ];
    }
}
