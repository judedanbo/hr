<?php

namespace App\Services;

use App\Models\LeaveType;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class LeaveDayCalculator
{
    /**
     * Count the number of leave days between two inclusive dates, honouring the
     * leave type's rules for whether weekends and public holidays are counted.
     *
     * @param  iterable<int, CarbonInterface|string>  $holidayDates
     */
    public function calculateDays(LeaveType $type, CarbonInterface $start, CarbonInterface $end, iterable $holidayDates = []): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $holidays = $this->normaliseHolidays($holidayDates);

        $days = 0;
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            if (! $type->counts_weekends && $date->isWeekend()) {
                continue;
            }

            if (! $type->counts_holidays && $holidays->contains($date->toDateString())) {
                continue;
            }

            $days++;
        }

        return $days;
    }

    /**
     * @param  iterable<int, CarbonInterface|string>  $holidayDates
     * @return Collection<int, string>
     */
    private function normaliseHolidays(iterable $holidayDates): Collection
    {
        return collect($holidayDates)
            ->map(fn ($date): string => $date instanceof CarbonInterface ? $date->toDateString() : (string) $date)
            ->unique()
            ->values();
    }
}
