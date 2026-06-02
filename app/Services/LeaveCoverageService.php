<?php

namespace App\Services;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\CarbonInterface;

class LeaveCoverageService
{
    /**
     * Count distinct OTHER staff in the requester's current unit who already have
     * an Approved leave request of the same leave type overlapping the range.
     */
    public function concurrentCount(InstitutionPerson $staff, LeaveType $leaveType, CarbonInterface $start, CarbonInterface $end, ?int $ignoreRequestId = null): int
    {
        $unit = $staff->units()->wherePivotNull('end_date')->first();

        if (! $unit) {
            return 0;
        }

        $unitStaffIds = $unit->staff()->pluck('institution_person.id')
            ->reject(fn ($id): bool => $id === $staff->id);

        if ($unitStaffIds->isEmpty()) {
            return 0;
        }

        return LeaveRequest::query()
            ->whereIn('staff_id', $unitStaffIds)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequestStatusEnum::Approved)
            ->when($ignoreRequestId, fn ($query) => $query->where('id', '!=', $ignoreRequestId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->distinct()
            ->count('staff_id');
    }

    /**
     * Whether approving this leave would breach the unit's concurrent-leave cap
     * for the leave type (no cap configured = never breaches).
     */
    public function exceedsLimit(InstitutionPerson $staff, LeaveType $leaveType, CarbonInterface $start, CarbonInterface $end, ?int $ignoreRequestId = null): bool
    {
        if (! $leaveType->max_concurrent_per_unit) {
            return false;
        }

        return $this->concurrentCount($staff, $leaveType, $start, $end, $ignoreRequestId) >= $leaveType->max_concurrent_per_unit;
    }
}
