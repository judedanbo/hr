<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCalendarControllerTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('leave-calendar.index'))->assertForbidden();
    }

    public function test_index_shows_approved_entries_for_the_month(): void
    {
        $type = LeaveType::factory()->create();
        $staff = InstitutionPerson::factory()->create();
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id,
            'leave_type_id' => $type->id,
            'status' => LeaveRequestStatusEnum::Approved,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);
        // A pending request in the same month must not appear.
        LeaveRequest::factory()->create([
            'leave_type_id' => $type->id,
            'status' => LeaveRequestStatusEnum::Pending,
            'start_date' => '2030-06-20',
            'end_date' => '2030-06-22',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('leave-calendar.index', ['month' => '2030-06']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveCalendar/Index')->has('entries', 1)->where('month', '2030-06'));
    }
}
