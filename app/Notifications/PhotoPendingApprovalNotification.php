<?php

namespace App\Notifications;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoPendingApprovalNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Person $person
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Profile Photo Pending Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A staff member has submitted a new profile photo that requires your approval.')
            ->line('**Staff:** ' . $this->person->full_name)
            ->action('Review Photo Approvals', url('/staff-photo-approvals'))
            ->line('Please review and approve or reject this photo submission.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'person_id' => $this->person->id,
            'person_name' => $this->person->full_name,
            'message' => 'New profile photo pending approval',
        ];
    }
}
