<?php

namespace App\Notifications;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoRejectedNotification extends Notification
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
            ->subject('Your Profile Photo Submission Was Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your profile photo submission has been reviewed and was not approved.')
            ->line('You are welcome to submit a new photo at any time.')
            ->action('Update My Profile', url('/my-profile'))
            ->line('Please ensure your photo meets the requirements before resubmitting.');
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
            'message' => 'Your profile photo submission was rejected',
        ];
    }
}
