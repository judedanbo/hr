<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Person;
use App\Models\Unit;
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

    public function test_non_admin_only_sees_their_own_units_leave(): void
    {
        $type = LeaveType::factory()->create();

        $unit = Unit::factory()->create();
        $otherUnit = Unit::factory()->create();

        $person = Person::factory()->create();
        $viewerStaff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        $viewerStaff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);
        $viewer = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $viewer->givePermissionTo('view leave calendar');

        $colleague = InstitutionPerson::factory()->create();
        $colleague->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);
        $outsider = InstitutionPerson::factory()->create();
        $outsider->units()->attach($otherUnit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        foreach ([$colleague, $outsider] as $staff) {
            LeaveRequest::factory()->create([
                'staff_id' => $staff->id, 'leave_type_id' => $type->id,
                'status' => LeaveRequestStatusEnum::Approved,
                'start_date' => '2030-06-10', 'end_date' => '2030-06-14',
            ]);
        }

        // Only the colleague in the viewer's own unit is visible; the outsider is not.
        $this->actingAs($viewer)
            ->get(route('leave-calendar.index', ['month' => '2030-06', 'unit_id' => $otherUnit->id]))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveCalendar/Index')->has('entries', 1));
    }
}
