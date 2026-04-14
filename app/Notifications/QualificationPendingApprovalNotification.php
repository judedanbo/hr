<?php

namespace App\Notifications;

use App\Models\Qualification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QualificationPendingApprovalNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Qualification $qualification
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
            ->subject('New Qualification Pending Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new qualification has been submitted and requires your approval.')
            ->line('**Staff:** ' . $this->qualification->person->full_name)
            ->line('**Qualification:** ' . $this->qualification->qualification)
            ->line('**Institution:** ' . $this->qualification->institution)
            ->line('**Course:** ' . $this->qualification->course)
            ->line('**Year:** ' . $this->qualification->year)
            ->action('Review Qualifications', url('/qualification'))
            ->line('Please review and approve or reject this qualification.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'qualification_id' => $this->qualification->id,
            'person_name' => $this->qualification->person->full_name,
            'qualification' => $this->qualification->qualification,
            'message' => 'New qualification pending approval',
        ];
    }
}
