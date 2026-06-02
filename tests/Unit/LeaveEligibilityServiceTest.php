<?php

namespace Tests\Unit;

use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Services\LeaveBalanceService;
use App\Services\LeaveEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveEligibilityService(new LeaveBalanceService);
    }

    private function staffWithGender(string $gender, int $hiredMonthsAgo = 60): InstitutionPerson
    {
        $person = Person::factory()->create(['gender' => $gender]);

        return InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'hire_date' => now()->subMonths($hiredMonthsAgo),
        ]);
    }

    public function test_gender_restriction_blocks_ineligible_staff(): void
    {
        $staff = $this->staffWithGender('M');
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create(['gender_restriction' => 'F']);

        $this->assertNotEmpty($this->service->failures($staff, $type, $year));
    }

    public function test_gender_restriction_passes_for_eligible_staff(): void
    {
        $staff = $this->staffWithGender('F');
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create(['gender_restriction' => 'F']);

        $this->assertEmpty($this->service->failures($staff, $type, $year));
    }

    public function test_min_service_months_blocks_new_staff(): void
    {
        $staff = $this->staffWithGender('F', hiredMonthsAgo: 6);
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create(['gender_restriction' => null]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'min_service_months' => 24,
        ]);

        $this->assertNotEmpty($this->service->failures($staff, $type, $year));
    }

    public function test_min_service_months_passes_for_long_serving_staff(): void
    {
        $staff = $this->staffWithGender('F', hiredMonthsAgo: 48);
        $year = LeaveYear::factory()->create();
        $type = LeaveType::factory()->create(['gender_restriction' => null]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'min_service_months' => 24,
        ]);

        $this->assertEmpty($this->service->failures($staff, $type, $year));
    }
}
