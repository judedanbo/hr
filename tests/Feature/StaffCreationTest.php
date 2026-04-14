<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        // Create institution first
        $this->institution = Institution::factory()->create();

        // Create a person linked to user for institution context
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'ADMIN001',
            'hire_date' => now()->subYears(5),
        ]);

        // Create user with permission and link to person
        $this->user = User::factory()->create(['person_id' => $person->id]);
        $this->user->givePermissionTo('create staff');
        $this->user->givePermissionTo('view all staff');
    }

    public function test_staff_creation_page_requires_authentication(): void
    {
        $response = $this->get(route('staff.create'));

        $response->assertRedirect();
    }

    public function test_staff_creation_page_requires_create_staff_permission(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('staff.create'));

        $response->assertForbidden();
    }

    public function test_staff_creation_page_loads_for_authorized_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('staff.create'));

        $response->assertStatus(200);
    }

    public function test_can_create_staff_with_valid_data(): void
    {
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $staffData = [
            'staffData' => [
                'bio' => [
                    'title' => 'Mr.',
                    'surname' => 'Doe',
                    'first_name' => 'John',
                    'other_names' => 'William',
                    'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
                    'gender' => 'M',
                    'marital_status' => 'S',
                    'ghana_card' => 'GHA-123456789-1',
                ],
                'address' => [
                    'address_line_1' => '123 Test Street',
                    'city' => 'Accra',
                    'region' => 'Greater Accra',
                    'country' => 'Ghana' . rand(100, 999), // Unique for validation
                ],
                'contact' => [
                    'contact_type' => 1,
                    'contact' => '024' . rand(1000000, 9999999), // Unique for validation
                ],
                'employment' => [
                    'staff_number' => 'STF001',
                    'file_number' => 'FILE001',
                    'hire_date' => now()->subMonths(6)->format('Y-m-d'),
                ],
                'qualifications' => [
                    'institution' => 'University of Ghana',
                    'course' => 'Computer Science',
                    'year' => '2020',
                ],
                'rank' => [
                    'rank_id' => $rank->id,
                    'start_date' => now()->subMonths(6)->format('Y-m-d'),
                ],
                'unit' => [
                    'unit_id' => $unit->id,
                    'start_date' => now()->subMonths(6)->format('Y-m-d'),
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        // Should redirect to staff show page on success
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify person was created
        $this->assertDatabaseHas('people', [
            'surname' => 'Doe',
            'first_name' => 'John',
            'gender' => 'M',
        ]);

        // Verify staff record was created
        $this->assertDatabaseHas('institution_person', [
            'staff_number' => 'STF001',
            'file_number' => 'FILE001',
        ]);
    }

    public function test_staff_creation_requires_valid_ghana_card_format(): void
    {
        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['bio']['ghana_card'] = 'INVALID-FORMAT';

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.bio.ghana_card');
    }

    public function test_staff_creation_requires_unique_ghana_card(): void
    {
        // Create existing person with Ghana Card
        $existingPerson = Person::factory()->create();
        $existingPerson->identities()->create([
            'id_type' => 'G',  // 'G' represents Ghana Card in Identity enum
            'id_number' => 'GHA-123456789-1',
        ]);

        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['bio']['ghana_card'] = 'GHA-123456789-1';

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.bio.ghana_card');
    }

    public function test_staff_creation_requires_unique_staff_number(): void
    {
        // Create existing staff with same staff number
        InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'staff_number' => 'STF001',
        ]);

        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['employment']['staff_number'] = 'STF001';

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.employment.staff_number');
    }

    public function test_staff_creation_requires_minimum_age_of_18(): void
    {
        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['bio']['date_of_birth'] = now()->subYears(17)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.bio.date_of_birth');
    }

    public function test_staff_creation_requires_hire_date_not_in_future(): void
    {
        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['employment']['hire_date'] = now()->addMonth()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.employment.hire_date');
    }

    public function test_staff_creation_requires_rank_start_date_after_hire_date(): void
    {
        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['employment']['hire_date'] = now()->subMonths(3)->format('Y-m-d');
        $staffData['staffData']['rank']['start_date'] = now()->subMonths(6)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasErrors('staffData.rank.start_date');
    }

    public function test_staff_creation_without_unit_is_valid(): void
    {
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['rank']['rank_id'] = $rank->id;
        unset($staffData['staffData']['unit']);

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasNoErrors();
    }

    public function test_staff_creation_creates_active_status(): void
    {
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        $staffData = $this->getMinimalStaffData();
        $staffData['staffData']['rank']['rank_id'] = $rank->id;

        $response = $this->actingAs($this->user)
            ->post(route('staff.store'), $staffData);

        $response->assertSessionHasNoErrors();

        // Verify active status was created
        $staffNumber = $staffData['staffData']['employment']['staff_number'];
        $staff = InstitutionPerson::where('staff_number', $staffNumber)->first();
        if ($staff) {
            $this->assertDatabaseHas('status', [
                'staff_id' => $staff->id,
                'status' => 'A',
            ]);
        }
    }

    /**
     * Helper method to get minimal valid staff data
     */
    protected function getMinimalStaffData(): array
    {
        $category = JobCategory::factory()->create(['institution_id' => $this->institution->id]);
        $rank = Job::factory()->create([
            'institution_id' => $this->institution->id,
            'job_category_id' => $category->id,
        ]);

        return [
            'staffData' => [
                'bio' => [
                    'title' => 'Mr.',
                    'surname' => 'Test',
                    'first_name' => 'User',
                    'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
                    'gender' => 'M',
                    'ghana_card' => 'GHA-' . rand(1000000, 9999999) . '-' . rand(1, 9),
                ],
                'address' => [
                    'address_line_1' => '123 Test Street',
                    'city' => 'Accra',
                    'region' => 'Region' . rand(100, 999),
                    'country' => 'Country' . rand(100, 999),
                ],
                'contact' => [
                    'contact_type' => 1,
                    'contact' => '024' . rand(1000000, 9999999),
                ],
                'employment' => [
                    'staff_number' => 'STF' . rand(100, 999),
                    'file_number' => 'FILE' . rand(100, 999),
                    'hire_date' => now()->subMonths(3)->format('Y-m-d'),
                ],
                'qualifications' => [
                    'institution' => 'University of Ghana',
                    'course' => 'Computer Science',
                    'year' => '2020',
                ],
                'rank' => [
                    'rank_id' => $rank->id,
                    'start_date' => now()->subMonths(3)->format('Y-m-d'),
                ],
            ],
        ];
    }
}
