<?php

namespace App\Services;

use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlanItem;
use App\Models\LeaveYear;

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
}
