<?php

namespace Tests\Feature\Leave;

use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;

    protected User $requesterUser;

    protected InstitutionPerson $requester;

    protected User $headUser;

    protected InstitutionPerson $head;

    protected User $colleagueUser;

    protected InstitutionPerson $colleague;

    protected Unit $unit;

    protected ?LeaveYear $year = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-06-01');

        $this->hrUser = User::factory()->create(['password_change_at' => now()]);
        $this->hrUser->assignRole('hr-user');

        $this->unit = Unit::factory()->create();

        [$this->requesterUser, $this->requester] = $this->makeStaff();
        [$this->headUser, $this->head] = $this->makeStaff();
        [$this->colleagueUser, $this->colleague] = $this->makeStaff();

        $this->unit->update(['head_staff_id' => $this->head->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: InstitutionPerson}
     */
    private function makeStaff(): array
    {
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'hire_date' => now()->subYears(10),
        ]);
        Status::factory()->active()->create([
            'staff_id' => $staff->id,
            'institution_id' => $staff->institution_id,
        ]);
        $staff->units()->attach($this->unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->assignRole('staff');

        return [$user, $staff];
    }

    private function activeYear2030(): LeaveYear
    {
        return $this->year ??= LeaveYear::factory()->active()->create([
            'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeWorkingDayType(array $overrides = []): LeaveType
    {
        return LeaveType::factory()->create(array_merge([
            'counts_weekends' => false,
            'counts_holidays' => false,
            'min_notice_days' => 3,
            'is_active' => true,
            'requires_evidence' => false,
        ], $overrides));
    }

    private function entitle(LeaveType $type, int $days): LeaveEntitlement
    {
        return LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->activeYear2030()->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => $days,
        ]);
    }

    public function test_seeded_roles_grant_the_journey_permissions(): void
    {
        $this->actingAs($this->hrUser)->get(route('leave-year.index'))->assertOk();
        $this->actingAs($this->hrUser)->get(route('leave-reports.index'))->assertOk();
        $this->actingAs($this->requesterUser)->get(route('leave-request.index'))->assertOk();
        $this->actingAs($this->requesterUser)->get(route('leave-plan.index'))->assertOk();
        $this->actingAs($this->headUser)->get(route('leave-approvals.index'))->assertOk();

        $this->actingAs($this->requesterUser)->get(route('leave-year.index'))->assertForbidden();
        $this->actingAs($this->requesterUser)->get(route('leave-balance-adjustment.index'))->assertForbidden();
    }
}
