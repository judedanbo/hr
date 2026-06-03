<?php

namespace Tests\Feature\Leave;

use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->get(route('leave-balance.index'))->assertRedirect(route('login'));
    }

    public function test_renders_the_ledger_for_a_staff_member(): void
    {
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('view leave requests');

        $year = LeaveYear::factory()->active()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => null, 'days_allowed' => 20,
        ]);

        $this->actingAs($user)
            ->get(route('leave-balance.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveBalance/Index')->has('ledger', 1));
    }
}
