<?php

namespace Tests\Unit\Services;

use App\Enums\ContactTypeEnum;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Position;
use App\Models\Qualification;
use App\Models\Unit;
use App\Models\User;
use App\Services\StaffProfileProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function createActiveStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }

    public function test_returns_null_when_no_active_institution_person_exists(): void
    {
        $provider = new StaffProfileProvider;

        $this->assertNull($provider->forPerson(999_999));
    }

    public function test_returns_payload_with_expected_top_level_keys(): void
    {
        $staff = $this->createActiveStaff();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertIsArray($payload);
        $this->assertSame(
            ['person', 'qualifications', 'contacts', 'address', 'staff'],
            array_keys($payload),
        );
    }

    public function test_person_block_contains_expected_fields(): void
    {
        $staff = $this->createActiveStaff();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertArrayHasKey('id', $payload['person']);
        $this->assertArrayHasKey('name', $payload['person']);
        $this->assertArrayHasKey('initials', $payload['person']);
        $this->assertArrayHasKey('image', $payload['person']);
        $this->assertArrayHasKey('identities', $payload['person']);
    }

    public function test_staff_block_contains_expected_employment_fields(): void
    {
        $staff = $this->createActiveStaff();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertArrayHasKey('staff_id', $payload['staff']);
        $this->assertArrayHasKey('staff_number', $payload['staff']);
        $this->assertArrayHasKey('file_number', $payload['staff']);
        $this->assertArrayHasKey('hire_date', $payload['staff']);
        $this->assertArrayHasKey('ranks', $payload['staff']);
        $this->assertArrayHasKey('units', $payload['staff']);
        $this->assertArrayHasKey('dependents', $payload['staff']);
    }

    public function test_payload_maps_nested_relations_when_populated(): void
    {
        $staff = $this->createActiveStaff();
        $person = $staff->person;

        // Contact
        $contact = $person->contacts()->create([
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0241234567',
        ]);

        // Address (polymorphic)
        $person->address()->create([
            'address_line_1' => '123 Test Street',
            'city' => 'Accra',
            'country' => 'GH',
        ]);

        // Rank — attach via job_staff pivot
        $job = Job::factory()->create(['name' => 'Senior Test Officer']);
        $staff->ranks()->attach($job->id, [
            'start_date' => now()->subYears(2)->toDateString(),
        ]);

        // Unit — attach via staff_unit pivot
        $unit = Unit::factory()->create(['name' => 'Test Division Unit']);
        $staff->units()->attach($unit->id, [
            'start_date' => now()->subYear()->toDateString(),
        ]);

        // Qualification (belongs to person)
        Qualification::factory()->approved()->create([
            'person_id' => $person->id,
            'qualification' => 'Bachelor of Science',
        ]);

        // Dependent — DependentFactory definition is empty, so we create inline.
        // A dependent requires a linked Person for name mapping.
        $depPerson = Person::factory()->create([
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);
        $staff->dependents()->create([
            'person_id' => $depPerson->id,
            'relation' => 'Spouse',
        ]);

        $payload = (new StaffProfileProvider)->forPerson($person->id);

        // Contacts
        $this->assertNotNull($payload['contacts']);
        $this->assertSame('0241234567', $payload['contacts'][0]['contact']);

        // Address
        $this->assertNotNull($payload['address']);
        $this->assertSame('Accra', $payload['address']['city']);

        // Ranks
        $this->assertNotEmpty($payload['staff']['ranks']);
        $this->assertSame('Senior Test Officer', $payload['staff']['ranks'][0]['name']);

        // Units
        $this->assertNotEmpty($payload['staff']['units']);
        $this->assertSame('Test Division Unit', $payload['staff']['units'][0]['unit_name']);

        // Qualifications
        $this->assertNotEmpty($payload['qualifications']);
        $this->assertSame('Bachelor of Science', $payload['qualifications'][0]['qualification']);

        // Dependents
        $this->assertNotEmpty($payload['staff']['dependents']);
        $depFullName = $payload['staff']['dependents'][0]['name'];
        $this->assertStringContainsString('Doe', $depFullName);
    }

    /**
     * `id` addresses the assignment row (edit/delete routes bind to it) while
     * `position_id` addresses the position itself (the select value and the
     * link through to the position page). Conflating them breaks both.
     */
    public function test_positions_expose_the_assignment_id_and_the_position_id_separately(): void
    {
        $staff = $this->createActiveStaff();
        $position = Position::factory()->create(['name' => 'Director of Audit']);

        $assignment = $staff->positionAssignments()->create([
            'position_id' => $position->id,
            'start_date' => '2024-03-04',
        ]);

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);
        $row = $payload['staff']['positions'][0];

        $this->assertSame($assignment->id, $row['id']);
        $this->assertSame($position->id, $row['position_id']);
        $this->assertSame('Director of Audit', $row['name']);
        $this->assertSame('2024-03-04', $row['start_date']);
        $this->assertSame('04 Mar 2024', $row['start_date_display']);
        $this->assertNull($row['end_date']);
        $this->assertTrue($row['is_current']);
    }

    public function test_soft_deleted_position_assignments_are_excluded(): void
    {
        $staff = $this->createActiveStaff();
        $position = Position::factory()->create();

        $assignment = $staff->positionAssignments()->create([
            'position_id' => $position->id,
            'start_date' => now()->subYear(),
        ]);
        $assignment->delete();

        $payload = (new StaffProfileProvider)->forPerson($staff->person_id);

        $this->assertSame([], $payload['staff']['positions']);
    }
}
