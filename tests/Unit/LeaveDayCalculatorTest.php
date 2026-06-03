<?php

namespace Tests\Unit;

use App\Models\LeaveType;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class LeaveDayCalculatorTest extends TestCase
{
    private function leaveType(bool $countsWeekends = false, bool $countsHolidays = false): LeaveType
    {
        $type = new LeaveType;
        $type->counts_weekends = $countsWeekends;
        $type->counts_holidays = $countsHolidays;

        return $type;
    }

    public function test_it_counts_working_days_excluding_weekends(): void
    {
        // 2025-06-02 (Mon) .. 2025-06-08 (Sun) => 5 weekdays.
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(),
            Carbon::parse('2025-06-02'),
            Carbon::parse('2025-06-08'),
        );

        $this->assertSame(5, $days);
    }

    public function test_it_counts_calendar_days_when_weekends_and_holidays_count(): void
    {
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(countsWeekends: true, countsHolidays: true),
            Carbon::parse('2025-06-02'),
            Carbon::parse('2025-06-08'),
            ['2025-06-04'],
        );

        $this->assertSame(7, $days);
    }

    public function test_it_excludes_holidays_for_working_day_types(): void
    {
        // 2025-06-04 is a Wednesday holiday, removed from the 5 weekdays.
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(),
            Carbon::parse('2025-06-02'),
            Carbon::parse('2025-06-08'),
            [Carbon::parse('2025-06-04')],
        );

        $this->assertSame(4, $days);
    }

    public function test_a_weekend_only_range_is_zero_working_days(): void
    {
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(),
            Carbon::parse('2025-06-07'), // Saturday
            Carbon::parse('2025-06-08'), // Sunday
        );

        $this->assertSame(0, $days);
    }

    public function test_a_single_weekday_counts_as_one(): void
    {
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(),
            Carbon::parse('2025-06-03'),
            Carbon::parse('2025-06-03'),
        );

        $this->assertSame(1, $days);
    }

    public function test_end_before_start_returns_zero(): void
    {
        $days = (new LeaveDayCalculator)->calculateDays(
            $this->leaveType(),
            Carbon::parse('2025-06-10'),
            Carbon::parse('2025-06-01'),
        );

        $this->assertSame(0, $days);
    }
}
