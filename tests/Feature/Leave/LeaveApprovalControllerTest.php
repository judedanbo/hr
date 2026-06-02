<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\LeaveRequestDecidedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaveApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $headUser;

    protected InstitutionPerson $head;

    protected InstitutionPerson $requester;

    protected User $requesterUser;

    protected LeaveYear $year;

    protected LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $headPerson = Person::factory()->create();
        $this->head = InstitutionPerson::factory()->create(['person_id' => $headPerson->id]);
        $this->headUser = User::factory()->create(['person_id' => $headPerson->id, 'password_change_at' => now()]);

        $reqPerson = Person::factory()->create();
        $this->requester = InstitutionPerson::factory()->create(['person_id' => $reqPerson->id]);
        $this->requesterUser = User::factory()->create(['person_id' => $reqPerson->id, 'password_change_at' => now()]);

        $this->year = LeaveYear::factory()->active()->create(['year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31']);
        $this->type = LeaveType::factory()->create();
        $this->entitle(20);
    }

    private function entitle(int $days): void
    {
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->year->id,
            'leave_type_id' => $this->type->id,
            'job_category_id' => null,
            'days_allowed' => $days,
        ]);
    }

    private function pendingRequest(int $requestedDays = 5): LeaveRequest
    {
        return LeaveRequest::factory()->create([
            'staff_id' => $this->requester->id,
            'leave_type_id' => $this->type->id,
            'leave_year_id' => $this->year->id,
            'requested_days' => $requestedDays,
            'approver_id' => $this->head->id,
            'status' => LeaveRequestStatusEnum::Pending,
        ]);
    }

    public function test_inbox_requires_authentication(): void
    {
        $this->get(route('leave-approvals.index'))->assertRedirect(route('login'));
    }

    public function test_inbox_shows_requests_assigned_to_the_head(): void
    {
        $this->pendingRequest();

        $this->actingAs($this->headUser)
            ->get(route('leave-approvals.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveApproval/Index')->has('requests.data', 1));
    }

    public function test_head_can_approve_a_request_and_notifies_requester(): void
    {
        Notification::fake();
        $leaveRequest = $this->pendingRequest(5);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $leaveRequest))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequestStatusEnum::Approved->value,
            'approved_days' => 5,
        ]);
        Notification::assertSentTo($this->requesterUser, LeaveRequestDecidedNotification::class);
    }

    public function test_head_can_reduce_approved_days(): void
    {
        $leaveRequest = $this->pendingRequest(5);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $leaveRequest), ['approved_days' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'approved_days' => 3]);
    }

    public function test_approval_blocked_when_exceeding_entitlement(): void
    {
        $this->year->entitlements()->delete();
        $this->entitle(3);
        $leaveRequest = $this->pendingRequest(5);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $leaveRequest), ['approved_days' => 5])
            ->assertSessionHasErrors('approved_days');
    }

    public function test_second_pending_request_blocked_once_first_approval_consumes_balance(): void
    {
        $this->year->entitlements()->delete();
        $this->entitle(8);

        $first = $this->pendingRequest(5);
        $second = $this->pendingRequest(5);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $first), ['approved_days' => 5])
            ->assertRedirect();

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $second), ['approved_days' => 5])
            ->assertSessionHasErrors('approved_days');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $second->id, 'status' => LeaveRequestStatusEnum::Pending->value,
        ]);
    }

    public function test_approval_blocked_when_unit_coverage_cap_reached(): void
    {
        $unit = Unit::factory()->create();
        $this->requester->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);
        $colleague = InstitutionPerson::factory()->create();
        $colleague->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $this->type->update(['max_concurrent_per_unit' => 1]);
        LeaveRequest::factory()->create([
            'staff_id' => $colleague->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id,
            'status' => LeaveRequestStatusEnum::Approved, 'approved_days' => 3,
            'start_date' => '2030-06-10', 'end_date' => '2030-06-14',
        ]);

        $leaveRequest = LeaveRequest::factory()->create([
            'staff_id' => $this->requester->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id,
            'requested_days' => 3, 'approver_id' => $this->head->id, 'status' => LeaveRequestStatusEnum::Pending,
            'start_date' => '2030-06-12', 'end_date' => '2030-06-14',
        ]);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $leaveRequest), ['approved_days' => 3])
            ->assertSessionHasErrors('coverage');
    }

    public function test_decline_requires_a_reason(): void
    {
        $leaveRequest = $this->pendingRequest();

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.decline', $leaveRequest), [])
            ->assertSessionHasErrors('decline_reason');

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.decline', $leaveRequest), ['decline_reason' => 'Coverage'])
            ->assertRedirect();
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => LeaveRequestStatusEnum::Declined->value]);
    }

    public function test_non_approver_cannot_decide(): void
    {
        $leaveRequest = $this->pendingRequest();
        $otherPerson = Person::factory()->create();
        InstitutionPerson::factory()->create(['person_id' => $otherPerson->id]);
        $other = User::factory()->create(['person_id' => $otherPerson->id, 'password_change_at' => now()]);

        $this->actingAs($other)
            ->post(route('leave-approvals.approve', $leaveRequest))
            ->assertForbidden();
    }

    public function test_admin_can_reassign_the_approver(): void
    {
        $admin = User::factory()->create(['password_change_at' => now()]);
        $admin->assignRole('super-administrator');
        $leaveRequest = $this->pendingRequest();
        $newApprover = InstitutionPerson::factory()->create();

        $this->actingAs($admin)
            ->post(route('leave-approvals.reassign', $leaveRequest), ['approver_id' => $newApprover->id])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'approver_id' => $newApprover->id]);
    }
}
