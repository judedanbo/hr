<?php

namespace App\Services;

use App\Models\InstitutionPerson;
use App\Models\LeaveType;
use App\Models\LeaveYear;

class LeaveEligibilityService
{
    public function __construct(
        private LeaveBalanceService $balance,
    ) {}

    /**
     * Return the reasons (if any) the staff member is ineligible for a leave type.
     * An empty array means eligible.
     *
     * @return array<int, string>
     */
    public function failures(InstitutionPerson $staff, LeaveType $leaveType, LeaveYear $year): array
    {
        $failures = [];

        if ($leaveType->gender_restriction) {
            $gender = $staff->person?->gender;
            if ($gender !== $leaveType->gender_restriction) {
                $failures[] = $leaveType->name . ' is restricted to ' . $leaveType->gender_restriction->label() . ' staff.';
            }
        }

        $entitlement = $this->balance->resolveEntitlement($staff, $leaveType->id, $year);
        $requiredMonths = (int) ($entitlement?->min_service_months ?? 0);

        if ($requiredMonths > 0) {
            $servedMonths = $staff->hire_date
                ? $staff->hire_date->diffInMonths(now())
                : 0;

            if ($servedMonths < $requiredMonths) {
                $failures[] = $leaveType->name . ' requires at least ' . $requiredMonths . ' months of service.';
            }
        }

        return $failures;
    }
}
