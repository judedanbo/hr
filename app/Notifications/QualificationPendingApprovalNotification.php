<?php

namespace App\Notifications;

use App\Models\Qualification;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QualificationPendingApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Qualification $qualification
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
            'qualification_id' => $this->qualification->id,
            'person_name' => $this->qualification->person->full_name,
            'qualification' => $this->qualification->qualification,
            'body' => $this->qualification->person->full_name . ' submitted a new qualification for approval.',
        ]);
    }
}
