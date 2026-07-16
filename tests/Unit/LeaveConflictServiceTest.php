<?php

namespace Tests\Unit;

use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Services\LeaveConflictService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveConflictServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveConflictService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveConflictService;
    }

    public function test_detects_an_overlapping_request(): void
    {
        $staff = InstitutionPerson::factory()->create();
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->assertTrue($this->service->overlaps($staff, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }

    public function test_non_overlapping_range_is_allowed(): void
    {
        $staff = InstitutionPerson::factory()->create();
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->assertFalse($this->service->overlaps($staff, Carbon::parse('2030-06-20'), Carbon::parse('2030-06-24')));
    }

    public function test_cancelled_requests_do_not_conflict(): void
    {
        $staff = InstitutionPerson::factory()->create();
        LeaveRequest::factory()->cancelled()->create([
            'staff_id' => $staff->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->assertFalse($this->service->overlaps($staff, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }

    public function test_declined_requests_do_not_conflict(): void
    {
        $staff = InstitutionPerson::factory()->create();
        LeaveRequest::factory()->create([
            'staff_id' => $staff->id,
            'status' => \App\Enums\LeaveRequestStatusEnum::Declined,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->assertFalse($this->service->overlaps($staff, Carbon::parse('2030-06-12'), Carbon::parse('2030-06-16')));
    }
}
