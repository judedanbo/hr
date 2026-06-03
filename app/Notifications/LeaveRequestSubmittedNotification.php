<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest
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
        $staffName = $this->leaveRequest->staff?->person?->full_name ?? 'A staff member';
        $type = $this->leaveRequest->leaveType?->name;

        return NotificationMeta::for(static::class, [
            'leave_request_id' => $this->leaveRequest->id,
            'staff_name' => $staffName,
            'leave_type' => $type,
            'body' => $staffName . ' submitted a ' . $type . ' request (' . $this->leaveRequest->requested_days . ' day(s)).',
        ]);
    }
}
