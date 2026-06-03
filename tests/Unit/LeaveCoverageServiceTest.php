<?php

namespace Tests\Unit;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Unit;
use App\Services\LeaveCoverageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCoverageServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveCoverageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveCoverageService;
    }

    private function assign(InstitutionPerson $staff, Unit $unit): void
    {
        $staff->units()->attach($unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);
    }

    public function test_counts_concurrent_unit_colleagues_on_the_same_leave_type(): void
    {
        $unit = Unit::factory()->create();
        $type = LeaveType::factory()->create();
        $requester = InstitutionPerson::factory()->create();
        $colleague = InstitutionPerson::factory()->create();
        $this->assign($requester, $unit);
        $this->assign($colleague, $unit);

        LeaveRequest::factory()->create([
            'staff_id' => $colleague->id,
            'leave_type_id' => $type->id,
            'status' => LeaveRequestStatusEnum::Approved,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $count = $this->service->concurrentCount($requester, $type, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16'));
        $this->assertSame(1, $count);
    }

    public function test_exceeds_limit_respects_the_cap(): void
    {
        $unit = Unit::factory()->create();
        $requester = InstitutionPerson::factory()->create();
        $colleague = InstitutionPerson::factory()->create();
        $this->assign($requester, $unit);
        $this->assign($colleague, $unit);

        $type = LeaveType::factory()->create(['max_concurrent_per_unit' => 1]);
        LeaveRequest::factory()->create([
            'staff_id' => $colleague->id,
            'leave_type_id' => $type->id,
            'status' => LeaveRequestStatusEnum::Approved,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->assertTrue($this->service->exceedsLimit($requester, $type, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));

        $type->update(['max_concurrent_per_unit' => 2]);
        $this->assertFalse($this->service->exceedsLimit($requester->fresh(), $type->fresh(), Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }

    public function test_detects_a_breach_in_any_of_the_requesters_units(): void
    {
        $quietUnit = Unit::factory()->create();
        $busyUnit = Unit::factory()->create();
        $requester = InstitutionPerson::factory()->create();
        $colleague = InstitutionPerson::factory()->create();
        $this->assign($requester, $quietUnit);
        $this->assign($requester, $busyUnit);
        $this->assign($colleague, $busyUnit);

        $type = LeaveType::factory()->create(['max_concurrent_per_unit' => 1]);
        LeaveRequest::factory()->create([
            'staff_id' => $colleague->id,
            'leave_type_id' => $type->id,
            'status' => LeaveRequestStatusEnum::Approved,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        // The colleague is only in the second (busy) unit; the cap must still trip.
        $this->assertSame(1, $this->service->concurrentCount($requester, $type, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
        $this->assertTrue($this->service->exceedsLimit($requester, $type, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }

    public function test_no_cap_never_exceeds(): void
    {
        $unit = Unit::factory()->create();
        $requester = InstitutionPerson::factory()->create();
        $this->assign($requester, $unit);
        $type = LeaveType::factory()->create(['max_concurrent_per_unit' => null]);

        $this->assertFalse($this->service->exceedsLimit($requester, $type, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }
}
