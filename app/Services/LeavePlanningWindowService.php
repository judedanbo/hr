<?php

namespace App\Services;

use App\Models\LeavePlanningWindow;
use App\Models\LeaveYear;

class LeavePlanningWindowService
{
    public function windowForYear(LeaveYear $year): ?LeavePlanningWindow
    {
        return LeavePlanningWindow::query()
            ->where('leave_year_id', $year->id)
            ->first();
    }

    /**
     * The planning window for the active leave year, if it is currently open.
     */
    public function openWindow(): ?LeavePlanningWindow
    {
        $window = LeavePlanningWindow::query()
            ->whereHas('leaveYear', fn ($query) => $query->where('is_active', true))
            ->with('leaveYear')
            ->first();

        return $window && $window->isOpen() ? $window : null;
    }

    public function isOpen(?LeavePlanningWindow $window): bool
    {
        return $window?->isOpen() ?? false;
    }
}
