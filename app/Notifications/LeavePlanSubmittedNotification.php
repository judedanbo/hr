<?php

namespace App\Notifications;

use App\Models\LeavePlan;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeavePlanSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeavePlan $leavePlan
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
        $staffName = $this->leavePlan->staff?->person?->full_name ?? 'A staff member';
        $year = $this->leavePlan->leaveYear?->year;

        return NotificationMeta::for(static::class, [
            'leave_plan_id' => $this->leavePlan->id,
            'staff_name' => $staffName,
            'year' => $year,
            'body' => $staffName . ' submitted their ' . $year . ' leave plan.',
        ]);
    }
}
