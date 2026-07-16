<?php

namespace App\Services;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use Carbon\CarbonInterface;

class LeaveConflictService
{
    /**
     * Whether the staff member already has a live leave request whose dates
     * overlap the given range. Cancelled and declined requests never conflict.
     */
    public function overlaps(InstitutionPerson $staff, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): bool
    {
        return LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->whereNotIn('status', [LeaveRequestStatusEnum::Cancelled, LeaveRequestStatusEnum::Declined])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();
    }
}
