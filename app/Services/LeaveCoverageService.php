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
     * The highest number of distinct OTHER staff on an Approved leave request of
     * the same leave type overlapping the range, across any of the requester's
     * current units. The cap is per-unit, so the busiest unit decides.
     */
    public function concurrentCount(InstitutionPerson $staff, LeaveType $leaveType, CarbonInterface $start, CarbonInterface $end, ?int $ignoreRequestId = null): int
    {
        $units = $staff->units()->wherePivotNull('end_date')->get();

        $max = 0;

        foreach ($units as $unit) {
            $unitStaffIds = $unit->staff()->pluck('institution_person.id')
                ->reject(fn ($id): bool => $id === $staff->id);

            if ($unitStaffIds->isEmpty()) {
                continue;
            }

            $count = LeaveRequest::query()
                ->whereIn('staff_id', $unitStaffIds)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', LeaveRequestStatusEnum::Approved)
                ->when($ignoreRequestId, fn ($query) => $query->where('id', '!=', $ignoreRequestId))
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString())
                ->distinct()
                ->count('staff_id');

            $max = max($max, $count);
        }

        return $max;
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
