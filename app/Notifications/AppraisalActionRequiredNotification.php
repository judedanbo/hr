<?php

namespace App\Notifications;

use App\Models\Appraisal;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppraisalActionRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Appraisal $appraisal,
        public string $action,
        public string $body,
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
            'appraisal_id' => $this->appraisal->id,
            'action' => $this->action,
            'body' => $this->body,
        ]);
    }
}
