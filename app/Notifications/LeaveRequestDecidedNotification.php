<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\NotificationMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestDecidedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $outcome,
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
        $type = $this->leaveRequest->leaveType?->name;

        $body = match ($this->outcome) {
            'Declined' => 'Your ' . $type . ' request was declined.'
                . ($this->leaveRequest->decline_reason ? ' Reason: ' . $this->leaveRequest->decline_reason : ''),
            'Reduced' => 'Your ' . $type . ' request was approved for ' . $this->leaveRequest->approved_days . ' of ' . $this->leaveRequest->requested_days . ' day(s).',
            default => 'Your ' . $type . ' request was approved (' . $this->leaveRequest->approved_days . ' day(s)).',
        };

        return NotificationMeta::for(static::class, [
            'leave_request_id' => $this->leaveRequest->id,
            'outcome' => $this->outcome,
            'body' => $body,
        ]);
    }
}
