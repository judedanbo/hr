<?php

namespace App\Services;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use Carbon\CarbonInterface;

class LeaveConflictService
{
    /**
     * Whether the staff member already has a non-cancelled leave request whose
     * dates overlap the given range.
     */
    public function overlaps(InstitutionPerson $staff, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): bool
    {
        return LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->where('status', '!=', LeaveRequestStatusEnum::Cancelled)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();
    }
}
