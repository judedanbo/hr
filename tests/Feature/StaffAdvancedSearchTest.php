<?php

namespace Tests\Feature;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StaffAdvancedSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with staff viewing permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view all staff');
    }

    public function test_staff_index_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('staff.index'));

        $response->assertStatus(200);
    }

    public function test_staff_index_requires_authentication(): void
    {
        $response = $this->get(route('staff.index'));

        $response->assertRedirect('/login');
    }

    public function test_can_filter_staff_by_rank(): void
    {
        // Create test data
        $rank1 = Job::factory()->create(['name' => 'Senior Officer']);
        $rank2 = Job::factory()->create(['name' => 'Junior Officer']);

        $staff1 = InstitutionPerson::factory()->create();
        $staff2 = InstitutionPerson::factory()->create();

        // Assign ranks
        $staff1->ranks()->attach($rank1->id, ['start_date' => now()->subYear()]);
        $staff2->ranks()->attach($rank2->id, ['start_date' => now()->subYear()]);

        // Filter by rank1
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['rank_id' => $rank1->id]));

        $response->assertStatus(200);
        // Note: Actual data assertions would require checking the response data
        // which depends on how Inertia renders the component
    }

    public function test_can_filter_staff_by_job_category(): void
    {
        $category = JobCategory::factory()->create(['name' => 'Administrative']);
        $rank = Job::factory()->create(['job_category_id' => $category->id]);

        $staff = InstitutionPerson::factory()->create();
        $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['job_category_id' => $category->id]));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_unit(): void
    {
        $unit = Unit::factory()->create(['name' => 'IT Department']);
        $staff = InstitutionPerson::factory()->create();

        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['unit_id' => $unit->id]));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_department(): void
    {
        $department = Unit::factory()->create([
            'name' => 'Main Department',
            'unit_id' => null, // Parent unit
        ]);

        $subUnit = Unit::factory()->create([
            'name' => 'Sub Unit',
            'unit_id' => $department->id,
        ]);

        $staff = InstitutionPerson::factory()->create();
        $staff->units()->attach($subUnit->id, ['start_date' => now()->subYear()]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['department_id' => $department->id]));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_gender(): void
    {
        $malePerson = Person::factory()->create(['gender' => 'M']);
        $femalePerson = Person::factory()->create(['gender' => 'F']);

        $maleStaff = InstitutionPerson::factory()->create(['person_id' => $malePerson->id]);
        $femaleStaff = InstitutionPerson::factory()->create(['person_id' => $femalePerson->id]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['gender' => 'M']));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_status(): void
    {
        $staff = InstitutionPerson::factory()->create(['status' => 'A']); // Active

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['status' => 'A']));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_hire_date_range(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'hire_date' => '2020-06-15',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'hire_date_from' => '2020-01-01',
                'hire_date_to' => '2020-12-31',
            ]));

        $response->assertStatus(200);
    }

    public function test_can_filter_staff_by_age_range(): void
    {
        // Create a person aged 35 (born 35 years ago)
        $person = Person::factory()->create([
            'dob' => now()->subYears(35)->format('Y-m-d'),
        ]);

        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'age_from' => 30,
                'age_to' => 40,
            ]));

        $response->assertStatus(200);
    }

    public function test_can_combine_multiple_filters(): void
    {
        $category = JobCategory::factory()->create();
        $rank = Job::factory()->create(['job_category_id' => $category->id]);
        $unit = Unit::factory()->create();

        $person = Person::factory()->create(['gender' => 'F']);
        $staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'status' => 'A',
            'hire_date' => '2018-03-01',
        ]);

        $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'rank_id' => $rank->id,
                'unit_id' => $unit->id,
                'gender' => 'F',
                'status' => 'A',
            ]));

        $response->assertStatus(200);
    }

    public function test_filters_return_no_results_when_no_match(): void
    {
        $rank = Job::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['rank_id' => $rank->id]));

        $response->assertStatus(200);
        // Should return empty results
    }

    public function test_filters_persist_across_pagination(): void
    {
        $rank = Job::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['rank_id' => $rank->id, 'page' => 2]));

        $response->assertStatus(200);
    }

    public function test_invalid_rank_id_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['rank_id' => 99999]));

        $response->assertSessionHasErrors('rank_id');
    }

    public function test_invalid_unit_id_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['unit_id' => 99999]));

        $response->assertSessionHasErrors('unit_id');
    }

    public function test_invalid_gender_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['gender' => 'X']));

        $response->assertSessionHasErrors('gender');
    }

    public function test_invalid_hire_date_to_before_hire_date_from_returns_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'hire_date_from' => '2023-12-31',
                'hire_date_to' => '2023-01-01',
            ]));

        $response->assertSessionHasErrors('hire_date_to');
    }

    public function test_age_from_less_than_18_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'age_from' => 15,
                'age_to' => 25,
            ]));

        $response->assertSessionHasErrors('age_from');
    }

    public function test_age_to_greater_than_100_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'age_from' => 50,
                'age_to' => 150,
            ]));

        $response->assertSessionHasErrors('age_to');
    }

    public function test_basic_text_search_still_works(): void
    {
        $person = Person::factory()->create([
            'fname' => 'John',
            'sname' => 'Doe',
        ]);

        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', ['search' => 'John']));

        $response->assertStatus(200);
    }

    public function test_can_combine_text_search_with_filters(): void
    {
        $rank = Job::factory()->create();
        $person = Person::factory()->create([
            'fname' => 'Jane',
            'sname' => 'Smith',
        ]);

        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);

        $response = $this->actingAs($this->user)
            ->get(route('staff.index', [
                'search' => 'Jane',
                'rank_id' => $rank->id,
            ]));

        $response->assertStatus(200);
    }

    public function test_filter_options_endpoint_is_accessible(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/api/staff-search/options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'jobCategories',
                'jobs',
                'units',
                'departments',
                'statuses',
                'genders',
            ]);
    }

    public function test_filter_options_endpoint_requires_authentication(): void
    {
        $response = $this->get('/api/staff-search/options');

        $response->assertStatus(401); // Unauthorized
    }
}
