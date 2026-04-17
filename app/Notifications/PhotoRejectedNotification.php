<?php

namespace App\Notifications;

use App\Models\Person;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PhotoRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Person $person
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return NotificationMeta::for(static::class, [
            'person_id' => $this->person->id,
            'person_name' => $this->person->full_name,
            'body' => 'Your profile photo submission was rejected. You can submit a new photo at any time.',
        ]);
    }
}
