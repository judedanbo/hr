<?php

namespace Tests\Unit;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlan;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveBalanceService;
    }

    public function test_assigned_days_uses_category_default_when_staff_has_no_rank(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 20,
        ]);

        $this->assertSame(20, $this->service->assignedDays($staff, $type->id, $year));
    }

    public function test_assigned_days_prefers_current_job_category_over_default(): void
    {
        $category = JobCategory::factory()->create();
        $job = Job::factory()->create(['job_category_id' => $category->id]);
        $staff = InstitutionPerson::factory()->create();
        $staff->ranks()->attach($job->id, ['start_date' => now()->subYear(), 'end_date' => null]);

        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 20,
        ]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => $category->id,
            'days_allowed' => 15,
        ]);

        $this->assertSame(15, $this->service->assignedDays($staff, $type->id, $year));
    }

    public function test_planned_and_unplanned_days(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 20,
        ]);

        $plan = LeavePlan::factory()->create(['staff_id' => $staff->id, 'leave_year_id' => $year->id]);
        LeavePlanItem::factory()->create(['leave_plan_id' => $plan->id, 'leave_type_id' => $type->id, 'proposed_days' => 3]);
        LeavePlanItem::factory()->create(['leave_plan_id' => $plan->id, 'leave_type_id' => $type->id, 'proposed_days' => 2]);

        $this->assertSame(5, $this->service->plannedDays($staff, $type->id, $year));
        $this->assertSame(15, $this->service->unplanned($staff, $type->id, $year));
    }

    public function test_committed_request_days_excludes_cancelled_and_drives_remaining(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 20,
        ]);

        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id, 'requested_days' => 6,
        ]);
        LeaveRequest::factory()->cancelled()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id, 'requested_days' => 10,
        ]);

        $this->assertSame(6, $this->service->committedRequestDays($staff, $type->id, $year));
        $this->assertSame(14, $this->service->remainingForRequest($staff, $type->id, $year));
    }

    public function test_taken_days_sums_approved_and_committed_excludes_declined(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create();

        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => \App\Enums\LeaveRequestStatusEnum::Approved, 'requested_days' => 8, 'approved_days' => 6,
        ]);
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => \App\Enums\LeaveRequestStatusEnum::Declined, 'requested_days' => 5,
        ]);

        $this->assertSame(6, $this->service->takenDays($staff, $type->id, $year));
        // Declined excluded; only the approved request's requested_days counts as committed.
        $this->assertSame(8, $this->service->committedRequestDays($staff, $type->id, $year));
    }
}
