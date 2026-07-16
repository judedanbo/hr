<?php

namespace Tests\Feature\Leave;

use App\Models\LeavePlanningWindow;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePlanningWindowControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-planning-window.index'))->assertForbidden();
    }

    public function test_index_displays_windows(): void
    {
        LeavePlanningWindow::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-planning-window.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeavePlanningWindow/Index')->has('windows.data', 2));
    }

    public function test_store_creates_a_window(): void
    {
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-planning-window.store'), [
                'leave_year_id' => $year->id,
                'opens_at' => '2030-01-01 09:00',
                'closes_at' => '2030-01-31 17:00',
                'allow_after_close' => false,
                'require_full_plan' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_planning_windows', [
            'leave_year_id' => $year->id,
            'require_full_plan' => true,
        ]);
    }

    public function test_store_rejects_second_window_for_same_year(): void
    {
        $window = LeavePlanningWindow::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-planning-window.store'), [
                'leave_year_id' => $window->leave_year_id,
                'opens_at' => '2030-01-01 09:00',
                'closes_at' => '2030-01-31 17:00',
                'allow_after_close' => false,
                'require_full_plan' => false,
            ])
            ->assertSessionHasErrors('leave_year_id');
    }

    public function test_store_rejects_close_before_open(): void
    {
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('leave-planning-window.store'), [
                'leave_year_id' => $year->id,
                'opens_at' => '2030-02-01 09:00',
                'closes_at' => '2030-01-01 17:00',
                'allow_after_close' => false,
                'require_full_plan' => false,
            ])
            ->assertSessionHasErrors('closes_at');
    }

    public function test_delete_soft_deletes_a_window(): void
    {
        $window = LeavePlanningWindow::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-planning-window.delete', $window))
            ->assertRedirect();

        $this->assertSoftDeleted('leave_planning_windows', ['id' => $window->id]);
    }
}
