<?php

namespace Tests\Unit\Services;

use App\Models\InstitutionPerson;
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
}
