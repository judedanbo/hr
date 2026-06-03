<?php

namespace Tests\Unit;

use App\DataTransferObjects\LeaveReportFilter;
use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlan;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\Unit;
use App\Services\LeaveReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveReportService;
    }

    public function test_summary_aggregates_utilisation_liability_compliance_and_absence(): void
    {
        $unit = Unit::factory()->create();
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $year = LeaveYear::factory()->active()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => null, 'days_allowed' => 20,
        ]);
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => LeaveRequestStatusEnum::Approved, 'requested_days' => 5, 'approved_days' => 5,
        ]);
        LeavePlan::factory()->submitted()->create(['staff_id' => $staff->id, 'leave_year_id' => $year->id]);

        $filter = new LeaveReportFilter(yearId: $year->id, unitId: $unit->id);
        $summary = $this->service->summary($filter);

        $this->assertSame(20, $summary['utilisationByType'][0]['assigned']);
        $this->assertSame(5, $summary['utilisationByType'][0]['taken']);
        $this->assertSame(15, $summary['liability']);
        $this->assertSame(100, $summary['compliance']['rate']);
        $this->assertSame(5, $summary['kpis']['total_taken']);
        $this->assertSame(1, $summary['absencePattern'][0]['spells']);
        $this->assertSame(5, $summary['absencePattern'][0]['bradford']);
    }

    public function test_absence_pattern_counts_completed_leave_at_actual_days(): void
    {
        $unit = Unit::factory()->create();
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $year = LeaveYear::factory()->active()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => null, 'days_allowed' => 20,
        ]);
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => LeaveRequestStatusEnum::Approved, 'requested_days' => 5, 'approved_days' => 5,
        ]);
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => LeaveRequestStatusEnum::Completed, 'requested_days' => 6, 'approved_days' => 6, 'actual_days' => 4,
        ]);

        $summary = $this->service->summary(new LeaveReportFilter(yearId: $year->id, unitId: $unit->id));

        // Two spells (Approved + Completed); 5 approved + 4 actually-taken = 9 days.
        $this->assertSame(2, $summary['absencePattern'][0]['spells']);
        $this->assertSame(9, $summary['absencePattern'][0]['days']);
        $this->assertSame(2 * 2 * 9, $summary['absencePattern'][0]['bradford']);
    }

    public function test_staff_rows_resolves_category_entitlement_adjustment_planned_and_taken(): void
    {
        $unit = Unit::factory()->create();
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $category = \App\Models\JobCategory::factory()->create();
        $job = \App\Models\Job::factory()->create(['job_category_id' => $category->id]);
        $staff->ranks()->attach($job->id, ['start_date' => now()->subYear(), 'end_date' => null]);

        $year = LeaveYear::factory()->active()->create();
        $type = LeaveType::factory()->create();
        // Category-specific entitlement must win over the default.
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => null, 'days_allowed' => 20,
        ]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => $category->id, 'days_allowed' => 15,
        ]);
        \App\Models\LeaveBalanceAdjustment::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id, 'days' => 3,
        ]);

        $plan = LeavePlan::factory()->submitted()->create(['staff_id' => $staff->id, 'leave_year_id' => $year->id]);
        \App\Models\LeavePlanItem::factory()->create([
            'leave_plan_id' => $plan->id, 'leave_type_id' => $type->id, 'proposed_days' => 7,
        ]);
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id, 'leave_type_id' => $type->id, 'leave_year_id' => $year->id,
            'status' => LeaveRequestStatusEnum::Approved, 'requested_days' => 6, 'approved_days' => 6,
        ]);

        $rows = $this->service->staffRows(new LeaveReportFilter(yearId: $year->id, unitId: $unit->id));

        $this->assertCount(1, $rows);
        $this->assertSame(18, $rows[0]['assigned']); // 15 category + 3 adjustment
        $this->assertSame(7, $rows[0]['planned']);
        $this->assertSame(6, $rows[0]['taken']);
        $this->assertSame(12, $rows[0]['remaining']); // 18 − 6
        $this->assertSame($unit->name, $rows[0]['unit']);
    }

    public function test_staff_rows_returns_per_type_ledger_rows(): void
    {
        $unit = Unit::factory()->create();
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $year = LeaveYear::factory()->active()->create();
        $type = LeaveType::factory()->create();
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id, 'leave_type_id' => $type->id, 'job_category_id' => null, 'days_allowed' => 12,
        ]);

        $rows = $this->service->staffRows(new LeaveReportFilter(yearId: $year->id, unitId: $unit->id));

        $this->assertCount(1, $rows);
        $this->assertSame(12, $rows[0]['assigned']);
        $this->assertSame($staff->person->full_name, $rows[0]['staff']);
    }
}
