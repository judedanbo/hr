<?php

namespace Tests\Unit;

use App\Models\LeavePlanningWindow;
use App\Models\LeaveYear;
use App\Services\LeavePlanningWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePlanningWindowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_window_is_open_within_its_dates(): void
    {
        $window = LeavePlanningWindow::factory()->open()->make();

        $this->assertTrue($window->isOpen());
    }

    public function test_window_is_closed_after_close_date(): void
    {
        $window = LeavePlanningWindow::factory()->closed()->make();

        $this->assertFalse($window->isOpen());
    }

    public function test_closed_window_is_open_when_late_entries_allowed(): void
    {
        $window = LeavePlanningWindow::factory()->closed()->make(['allow_after_close' => true]);

        $this->assertTrue($window->isOpen());
    }

    public function test_open_window_only_returned_for_active_year(): void
    {
        $service = new LeavePlanningWindowService;

        $inactiveYear = LeaveYear::factory()->create(['is_active' => false]);
        LeavePlanningWindow::factory()->open()->create(['leave_year_id' => $inactiveYear->id]);
        $this->assertNull($service->openWindow());

        $activeYear = LeaveYear::factory()->active()->create();
        LeavePlanningWindow::factory()->open()->create(['leave_year_id' => $activeYear->id]);
        $this->assertNotNull($service->openWindow());
    }
}
