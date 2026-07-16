<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected InstitutionPerson $staff;

    protected LeaveYear $year;

    protected LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-06-01');

        $person = Person::factory()->create();
        $this->staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $this->staff->id, 'institution_id' => $this->staff->institution_id]);
        $this->staffUser = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $this->staffUser->givePermissionTo(['cancel leave request', 'resume leave request', 'amend leave request']);

        $this->year = LeaveYear::factory()->active()->create(['year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31']);
        $this->type = LeaveType::factory()->calendarDays()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->year->id, 'leave_type_id' => $this->type->id, 'job_category_id' => null, 'days_allowed' => 20,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function approvedRequest(): LeaveRequest
    {
        return LeaveRequest::factory()->create([
            'staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id,
            'status' => LeaveRequestStatusEnum::Approved, 'requested_days' => 5, 'approved_days' => 5,
            'start_date' => '2030-06-10', 'end_date' => '2030-06-14',
        ]);
    }

    public function test_cancelling_an_approved_request_recredits_the_balance(): void
    {
        $request = $this->approvedRequest();
        $balance = app(LeaveBalanceService::class);
        $this->assertSame(5, $balance->takenDays($this->staff, $this->type->id, $this->year));

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.cancel', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $request->id, 'status' => LeaveRequestStatusEnum::Cancelled->value]);
        $this->assertSame(0, $balance->takenDays($this->staff->fresh(), $this->type->id, $this->year));
    }

    public function test_resuming_records_actual_days_and_recredits_unused(): void
    {
        $request = $this->approvedRequest();

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.resume', $request), ['actual_return_date' => '2030-06-12'])
            ->assertRedirect();

        // Used = 2030-06-10..2030-06-11 inclusive = 2 calendar days.
        $this->assertDatabaseHas('leave_requests', [
            'id' => $request->id,
            'status' => LeaveRequestStatusEnum::Completed->value,
            'actual_days' => 2,
        ]);
        $this->assertSame(2, app(LeaveBalanceService::class)->takenDays($this->staff, $this->type->id, $this->year));
    }

    public function test_amending_cancels_the_original_and_creates_a_linked_pending_request(): void
    {
        $request = $this->approvedRequest();

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.amend', $request), ['start_date' => '2030-07-01', 'end_date' => '2030-07-03'])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $request->id, 'status' => LeaveRequestStatusEnum::Cancelled->value]);
        $amended = LeaveRequest::where('amended_from_id', $request->id)->first();
        $this->assertNotNull($amended);
        $this->assertSame(LeaveRequestStatusEnum::Pending, $amended->status);
        $this->assertSame(3, $amended->requested_days);
    }

    public function test_balance_adjustment_changes_assigned_days(): void
    {
        $balance = app(LeaveBalanceService::class);
        $this->assertSame(20, $balance->assignedDays($this->staff, $this->type->id, $this->year));

        \App\Models\LeaveBalanceAdjustment::factory()->create([
            'staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id, 'days' => 5,
        ]);

        $this->assertSame(25, $balance->assignedDays($this->staff->fresh(), $this->type->id, $this->year));
    }
}
