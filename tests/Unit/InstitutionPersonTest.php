<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatusEnum;
use App\Enums\TransferStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Status;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionPersonTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::factory()->create();
    }

    // ===================
    // MODEL CONFIGURATION TESTS
    // ===================

    public function test_institution_person_uses_correct_table(): void
    {
        $staff = new InstitutionPerson;
        $this->assertEquals('institution_person', $staff->getTable());
    }

    public function test_institution_person_has_primary_key_id(): void
    {
        $staff = new InstitutionPerson;
        $this->assertEquals('id', $staff->getKeyName());
    }

    public function test_institution_person_has_incrementing_id(): void
    {
        $staff = new InstitutionPerson;
        $this->assertTrue($staff->incrementing);
    }

    public function test_hire_date_is_cast_to_date(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => '2020-01-15',
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $this->assertInstanceOf(\Carbon\Carbon::class, $staff->hire_date);
    }

    // ===================
    // RELATIONSHIP TESTS
    // ===================

    public function test_staff_belongs_to_person(): void
    {
        $person = Person::factory()->create(['surname' => 'TestPerson']);
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $this->assertInstanceOf(Person::class, $staff->person);
        $this->assertEquals('TestPerson', $staff->person->surname);
    }

    public function test_staff_belongs_to_institution(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $this->assertInstanceOf(Institution::class, $staff->institution);
        $this->assertEquals($this->institution->id, $staff->institution->id);
    }

    public function test_staff_has_many_units(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $staff->units()->attach($unit->id, [
            'start_date' => now(),
            'status' => TransferStatusEnum::Approved,
        ]);

        $this->assertCount(1, $staff->units);
        $this->assertEquals($unit->id, $staff->units->first()->id);
    }

    public function test_staff_has_many_ranks(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        $staff->ranks()->attach($rank->id, [
            'start_date' => now(),
        ]);

        $this->assertCount(1, $staff->ranks);
        $this->assertEquals($rank->id, $staff->ranks->first()->id);
    }

    public function test_staff_has_many_statuses(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        Status::create([
            'staff_id' => $staff->id,
            'status' => EmployeeStatusEnum::Active,
            'start_date' => now(),
            'institution_id' => $this->institution->id,
        ]);

        $this->assertCount(1, $staff->statuses);
    }

    // ===================
    // SCOPE TESTS
    // ===================

    public function test_active_scope_filters_active_staff(): void
    {
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

        $activeStaffList = InstitutionPerson::active()->get();

        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
        $this->assertFalse($activeStaffList->contains('id', $retiredStaff->id));
    }

    public function test_retired_scope_filters_retired_staff(): void
    {
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

        $retiredStaffList = InstitutionPerson::retired()->get();

        $this->assertTrue($retiredStaffList->contains('id', $retiredStaff->id));
    }

    public function test_search_scope_finds_by_staff_number(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'UNIQUE123',
            'hire_date' => now(),
        ]);

        $results = InstitutionPerson::search('UNIQUE123')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('UNIQUE123', $results->first()->staff_number);
    }

    public function test_search_scope_finds_by_file_number(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'file_number' => 'FILE999',
            'hire_date' => now(),
        ]);

        $results = InstitutionPerson::search('FILE999')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('FILE999', $results->first()->file_number);
    }

    public function test_male_staff_scope(): void
    {
        $malePerson = Person::factory()->create(['gender' => 'M']);
        $malePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'MALE001',
            'hire_date' => now(),
        ]);

        $femalePerson = Person::factory()->create(['gender' => 'F']);
        $femalePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'FEMALE001',
            'hire_date' => now(),
        ]);

        $maleStaff = InstitutionPerson::maleStaff()->get();

        $this->assertTrue($maleStaff->contains('staff_number', 'MALE001'));
        $this->assertFalse($maleStaff->contains('staff_number', 'FEMALE001'));
    }

    public function test_female_staff_scope(): void
    {
        $femalePerson = Person::factory()->create(['gender' => 'F']);
        $femalePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'FEMALE001',
            'hire_date' => now(),
        ]);

        $malePerson = Person::factory()->create(['gender' => 'M']);
        $malePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'MALE001',
            'hire_date' => now(),
        ]);

        $femaleStaff = InstitutionPerson::femaleStaff()->get();

        $this->assertTrue($femaleStaff->contains('staff_number', 'FEMALE001'));
        $this->assertFalse($femaleStaff->contains('staff_number', 'MALE001'));
    }

    // ===================
    // FILTER SCOPE TESTS
    // ===================

    public function test_filter_by_rank_scope(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'name' => 'Senior Officer',
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        $staff->ranks()->attach($rank->id, ['start_date' => now()]);

        $results = InstitutionPerson::filterByRank($rank->id)->get();

        $this->assertTrue($results->contains('id', $staff->id));
    }

    public function test_filter_by_unit_scope(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);
        $staff->units()->attach($unit->id, ['start_date' => now()]);

        $results = InstitutionPerson::filterByUnit($unit->id)->get();

        $this->assertTrue($results->contains('id', $staff->id));
    }

    public function test_filter_by_gender_scope(): void
    {
        $malePerson = Person::factory()->create(['gender' => 'M']);
        $malePerson->institution()->attach($this->institution->id, [
            'staff_number' => 'MALE001',
            'hire_date' => now(),
        ]);

        $results = InstitutionPerson::filterByGender('M')->get();

        $this->assertTrue($results->contains('staff_number', 'MALE001'));
    }

    public function test_filter_by_hire_date_range_scope(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF2020',
            'hire_date' => '2020-06-15',
        ]);

        $results = InstitutionPerson::filterByHireDateRange('2020-01-01', '2020-12-31')->get();

        $this->assertTrue($results->contains('staff_number', 'STF2020'));
    }

    public function test_filter_by_hire_date_from_scope(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF2022',
            'hire_date' => '2022-06-15',
        ]);

        $results = InstitutionPerson::filterByHireDateFrom('2022-01-01')->get();

        $this->assertTrue($results->contains('staff_number', 'STF2022'));
    }

    public function test_filter_by_status_scope(): void
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        Status::create([
            'staff_id' => $staff->id,
            'status' => EmployeeStatusEnum::Active,
            'start_date' => now(),
            'institution_id' => $this->institution->id,
        ]);

        $results = InstitutionPerson::filterByStatus('A')->get();

        $this->assertTrue($results->contains('id', $staff->id));
    }
}
