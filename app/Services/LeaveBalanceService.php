<?php

namespace App\Services;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\Holiday;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveYear;
use Carbon\Carbon;

class LeaveBalanceService
{
    /**
     * Resolve the entitlement for a staff member, leave type and year, preferring an
     * exact match on the staff's current job category, then the category default.
     */
    public function resolveEntitlement(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year): ?LeaveEntitlement
    {
        $categoryId = $this->currentJobCategoryId($staff);

        if ($categoryId !== null) {
            $exact = LeaveEntitlement::query()
                ->where('leave_year_id', $year->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('job_category_id', $categoryId)
                ->first();

            if ($exact) {
                return $exact;
            }
        }

        return LeaveEntitlement::query()
            ->where('leave_year_id', $year->id)
            ->where('leave_type_id', $leaveTypeId)
            ->whereNull('job_category_id')
            ->first();
    }

    /**
     * The total days assigned to a staff member for a leave type in a year.
     */
    public function assignedDays(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year): int
    {
        return $this->resolveEntitlement($staff, $leaveTypeId, $year)?->days_allowed ?? 0;
    }

    /**
     * The total days the staff member has already planned for a leave type in a year.
     */
    public function plannedDays(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year): int
    {
        return (int) LeavePlanItem::query()
            ->where('leave_type_id', $leaveTypeId)
            ->whereHas('leavePlan', function ($query) use ($staff, $year): void {
                $query->where('staff_id', $staff->id)
                    ->where('leave_year_id', $year->id);
            })
            ->sum('proposed_days');
    }

    /**
     * Days still available to plan for a leave type in a year (never below zero).
     */
    public function unplanned(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year): int
    {
        return max(0, $this->assignedDays($staff, $leaveTypeId, $year) - $this->plannedDays($staff, $leaveTypeId, $year));
    }

    public function currentJobCategoryId(InstitutionPerson $staff): ?int
    {
        return $staff->ranks()
            ->wherePivotNull('end_date')
            ->first()?->job_category_id;
    }

    /**
     * The holiday dates (Y-m-d) configured for a leave year.
     *
     * @return array<int, string>
     */
    public function holidayDates(?LeaveYear $year): array
    {
        if (! $year) {
            return [];
        }

        return Holiday::query()
            ->where('leave_year_id', $year->id)
            ->pluck('date')
            ->map(fn ($date): string => Carbon::parse($date)->toDateString())
            ->all();
    }

    /**
     * Days already committed to leave requests (excluding cancelled) for a leave
     * type in a year, optionally ignoring one request (when editing).
     */
    public function committedRequestDays(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year, ?int $ignoreRequestId = null): int
    {
        return (int) LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('leave_year_id', $year->id)
            ->whereNotIn('status', [LeaveRequestStatusEnum::Cancelled, LeaveRequestStatusEnum::Declined])
            ->when($ignoreRequestId, fn ($query) => $query->where('id', '!=', $ignoreRequestId))
            ->sum('requested_days');
    }

    /**
     * Days actually taken — the sum of approved_days across Approved requests for
     * a leave type in a year, optionally ignoring one request (when re-deciding).
     */
    public function takenDays(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year, ?int $ignoreRequestId = null): int
    {
        return (int) LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('leave_year_id', $year->id)
            ->where('status', LeaveRequestStatusEnum::Approved)
            ->when($ignoreRequestId, fn ($query) => $query->where('id', '!=', $ignoreRequestId))
            ->sum('approved_days');
    }

    /**
     * Days still available to request for a leave type in a year (never below zero).
     */
    public function remainingForRequest(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year, ?int $ignoreRequestId = null): int
    {
        return max(0, $this->assignedDays($staff, $leaveTypeId, $year)
            - $this->committedRequestDays($staff, $leaveTypeId, $year, $ignoreRequestId));
    }

    /**
     * Days left after what has actually been taken (Assigned − Taken).
     */
    public function remaining(InstitutionPerson $staff, int $leaveTypeId, LeaveYear $year): int
    {
        return max(0, $this->assignedDays($staff, $leaveTypeId, $year) - $this->takenDays($staff, $leaveTypeId, $year));
    }

    /**
     * The annual ledger for a staff member: per active leave type with an
     * entitlement, the Assigned / Planned / Taken / Remaining day counts.
     *
     * @return array<int, array{leave_type_id: int, leave_type: string, color: ?string, assigned: int, planned: int, taken: int, remaining: int}>
     */
    public function ledger(InstitutionPerson $staff, LeaveYear $year): array
    {
        return \App\Models\LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (\App\Models\LeaveType $type) use ($staff, $year): array {
                $assigned = $this->assignedDays($staff, $type->id, $year);

                return [
                    'leave_type_id' => $type->id,
                    'leave_type' => $type->name,
                    'color' => $type->color,
                    'assigned' => $assigned,
                    'planned' => $this->plannedDays($staff, $type->id, $year),
                    'taken' => $this->takenDays($staff, $type->id, $year),
                    'remaining' => $this->remaining($staff, $type->id, $year),
                ];
            })
            ->filter(fn (array $row): bool => $row['assigned'] > 0)
            ->values()
            ->all();
    }
}
