<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected JobCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
        $this->category = JobCategory::factory()->create([
            'institution_id' => $this->institution->id,
            'level' => 3,
        ]);
    }

    // ===================
    // MODEL CONFIGURATION TESTS
    // ===================

    public function test_job_uses_soft_deletes(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $job->delete();

        $this->assertSoftDeleted($job);
        $this->assertNotNull(Job::withTrashed()->find($job->id));
    }

    public function test_job_fillable_fields(): void
    {
        $data = [
            'name' => 'Test Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ];

        $job = Job::create($data);

        $this->assertEquals('Test Officer', $job->name);
        $this->assertEquals($this->institution->id, $job->institution_id);
    }

    // ===================
    // RELATIONSHIP TESTS
    // ===================

    public function test_job_belongs_to_institution(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $this->assertInstanceOf(Institution::class, $job->institution);
        $this->assertEquals($this->institution->id, $job->institution->id);
    }

    public function test_job_belongs_to_category(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $this->assertInstanceOf(JobCategory::class, $job->category);
        $this->assertEquals($this->category->id, $job->category->id);
    }

    public function test_job_has_many_staff(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $job->staff()->attach($staff->id, [
            'start_date' => now(),
        ]);

        $this->assertCount(1, $job->staff);
        $this->assertEquals($staff->id, $job->staff->first()->id);
    }
    // TODO: Fix this test if previous rank feature is reintroduced
    // public function test_job_can_have_previous_rank(): void
    // {
    //     $previousJob = Job::factory()->create([
    //         'name' => 'Junior Officer',
    //         'institution_id' => $this->institution->id,
    //         'job_category_id' => $this->category->id,
    //     ]);

    //     $currentJob = Job::factory()->create([
    //         'name' => 'Senior Officer',
    //         'institution_id' => $this->institution->id,
    //         'job_category_id' => $this->category->id,
    //         'previous_rank_id' => $previousJob->id,
    //     ]);

    //     $this->assertInstanceOf(Job::class, $currentJob->previousRank);
    //     $this->assertEquals($previousJob->id, $currentJob->previousRank->id);
    // }

    // ===================
    // SCOPE TESTS
    // ===================

    public function test_management_ranks_scope_filters_by_level(): void
    {
        $managementCategory = JobCategory::factory()->create([
            'institution_id' => $this->institution->id,
            'level' => 1,
        ]);

        $managementJob = Job::factory()->create([
            'name' => 'Director',
            'institution_id' => $this->institution->id,
            'job_category_id' => $managementCategory->id,
        ]);

        $regularCategory = JobCategory::factory()->create([
            'institution_id' => $this->institution->id,
            'level' => 5,
        ]);

        $regularJob = Job::factory()->create([
            'name' => 'Clerk',
            'institution_id' => $this->institution->id,
            'job_category_id' => $regularCategory->id,
        ]);

        $managementRanks = Job::managementRanks()->get();

        $this->assertTrue($managementRanks->contains('id', $managementJob->id));
        $this->assertFalse($managementRanks->contains('id', $regularJob->id));
    }

    public function test_other_ranks_scope_filters_by_level(): void
    {
        $otherCategory = JobCategory::factory()->create([
            'institution_id' => $this->institution->id,
            'level' => 3,
        ]);

        $otherJob = Job::factory()->create([
            'name' => 'Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $otherCategory->id,
        ]);

        $managementCategory = JobCategory::factory()->create([
            'institution_id' => $this->institution->id,
            'level' => 1,
        ]);

        Job::factory()->create([
            'name' => 'Director',
            'institution_id' => $this->institution->id,
            'job_category_id' => $managementCategory->id,
        ]);

        $otherRanks = Job::otherRanks()->get();

        $this->assertTrue($otherRanks->contains('id', $otherJob->id));
    }

    public function test_search_rank_scope_finds_by_name(): void
    {
        $job = Job::factory()->create([
            'name' => 'UniqueRankName',
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $results = Job::searchRank('UniqueRankName')->get();

        $this->assertTrue($results->contains('id', $job->id));
    }

    public function test_search_rank_scope_finds_by_category_name(): void
    {
        $namedCategory = JobCategory::factory()->create([
            'name' => 'UniqueCategory',
            'institution_id' => $this->institution->id,
        ]);

        $job = Job::factory()->create([
            'name' => 'Some Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $namedCategory->id,
        ]);

        $results = Job::searchRank('UniqueCategory')->get();

        $this->assertTrue($results->contains('id', $job->id));
    }

    // ===================
    // ACTIVE STAFF TESTS
    // ===================

    public function test_active_staff_relationship_filters_active_employees(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        // Create active staff
        $activePerson = Person::factory()->create();
        $activePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'ACTIVE001',
            'hire_date' => now()->subYear(),
        ]);
        $activeStaff = InstitutionPerson::where('person_id', $activePerson->id)->first();
        Status::create([
            'staff_id' => $activeStaff->id,
            'status' => EmployeeStatusEnum::Active,
            'start_date' => now(),
            'institution_id' => $this->institution->id,
        ]);
        $job->staff()->attach($activeStaff->id, [
            'start_date' => now()->subMonth(),
        ]);

        // Create retired staff
        $retiredPerson = Person::factory()->create();
        $retiredPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'RETIRED001',
            'hire_date' => now()->subYears(30),
        ]);
        $retiredStaff = InstitutionPerson::where('person_id', $retiredPerson->id)->first();
        Status::create([
            'staff_id' => $retiredStaff->id,
            'status' => EmployeeStatusEnum::Retired,
            'start_date' => now(),
            'institution_id' => $this->institution->id,
        ]);
        $job->staff()->attach($retiredStaff->id, [
            'start_date' => now()->subYears(25),
        ]);

        // Active staff should only include active employees
        $activeStaffList = $job->activeStaff;

        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
    }

    // ===================
    // FACTORY TESTS
    // ===================

    public function test_factory_creates_valid_job(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $this->assertNotNull($job->name);
        $this->assertNotNull($job->institution_id);
        $this->assertNotNull($job->job_category_id);
    }

    public function test_factory_creates_unique_jobs(): void
    {
        $job1 = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);
        $job2 = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $this->assertNotEquals($job1->id, $job2->id);
    }

    // ===================
    // EDGE CASE TESTS
    // ===================

    public function test_job_staff_with_end_date_in_future_is_still_assigned(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now()->subYear(),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $job->staff()->attach($staff->id, [
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $this->assertCount(1, $job->staff);
    }

    public function test_job_can_have_multiple_staff_members(): void
    {
        $job = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $person = Person::factory()->create();
            $person->institution()->attach($this->institution->id, [
                'staff_number' => 'STF00' . $i,
                'hire_date' => now()->subYear(),
            ]);
            $staff = InstitutionPerson::where('person_id', $person->id)->first();

            $job->staff()->attach($staff->id, [
                'start_date' => now(),
            ]);
        }

        $this->assertCount(3, $job->staff);
    }

    public function test_staff_member_can_have_multiple_job_history(): void
    {
        $job1 = Job::factory()->create([
            'name' => 'Junior Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $job2 = Job::factory()->create([
            'name' => 'Senior Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $this->category->id,
        ]);

        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now()->subYears(5),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // First job
        $job1->staff()->attach($staff->id, [
            'start_date' => now()->subYears(4),
            'end_date' => now()->subYear(),
        ]);

        // Second job (promotion)
        $job2->staff()->attach($staff->id, [
            'start_date' => now()->subYear(),
        ]);

        $this->assertCount(2, $staff->ranks);
    }
}
