<?php

namespace Tests\Unit\Services;

use App\Contracts\Services\StaffManagementServiceInterface;
use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\Contact;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\PersonIdentity;
use App\Models\Unit;
use App\Models\User;
use App\Services\Staff\StaffManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StaffManagementServiceInterface $service;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StaffManagementService;
        $this->institution = Institution::factory()->create();
    }

    public function test_create_staff_creates_person_and_institution_person_record(): void
    {
        $job = Job::factory()->create(['institution_id' => $this->institution->id]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        // Create a user with an associated person and institution for auth
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'AUTH001',
            'file_number' => 'AUTH001',
            'hire_date' => now(),
        ]);
        $user = User::factory()->create(['person_id' => $person->id]);
        $this->actingAs($user);

        $data = [
            'bio' => [
                'first_name' => 'John',
                'surname' => 'Doe',
                'date_of_birth' => '1990-01-15',
                'gender' => 'M',
                'ghana_card' => 'GHA-123456789-0',
            ],
            'employment' => [
                'staff_number' => 'STF001',
                'file_number' => 'FILE001',
                'hire_date' => '2023-01-01',
            ],
            'address' => [
                'address_line_1' => '123 Main St',
                'city' => 'Accra',
                'country' => 'Ghana',
            ],
            'contact' => [
                'contact_type' => ContactTypeEnum::PHONE->value,
                'contact' => '0551234567',
            ],
            'qualifications' => [
                'course' => 'Computer Science',
                'institution' => 'University of Ghana',
                'qualification' => 'BSc',
                'year' => '2012',
            ],
            'rank' => [
                'rank_id' => $job->id,
                'start_date' => '2023-01-01',
            ],
            'unit' => [
                'unit_id' => $unit->id,
                'start_date' => '2023-01-01',
            ],
        ];

        $staff = $this->service->create($data);

        $this->assertInstanceOf(InstitutionPerson::class, $staff);
        $this->assertEquals('STF001', $staff->staff_number);
        $this->assertEquals('FILE001', $staff->file_number);

        // Verify person record
        $this->assertEquals('John', $staff->person->first_name);
        $this->assertEquals('Doe', $staff->person->surname);

        // Verify identity was created
        $this->assertCount(1, $staff->person->identities);
        $this->assertEquals('GHA-123456789-0', $staff->person->identities->first()->id_number);

        // Verify status was created
        $this->assertCount(1, $staff->statuses);
        $this->assertEquals('A', $staff->statuses->first()->status->value);

        // Verify rank was assigned
        $this->assertCount(1, $staff->ranks);
        $this->assertEquals($job->id, $staff->ranks->first()->id);

        // Verify unit was assigned
        $this->assertCount(1, $staff->units);
        $this->assertEquals($unit->id, $staff->units->first()->id);
    }

    public function test_create_staff_throws_exception_when_no_institution(): void
    {
        // User without associated institution
        $user = User::factory()->create(['person_id' => null]);
        $this->actingAs($user);

        $data = [
            'bio' => [
                'first_name' => 'Jane',
                'surname' => 'Smith',
                'date_of_birth' => '1992-05-20',
            ],
            'employment' => [
                'staff_number' => 'STF002',
                'file_number' => 'FILE002',
                'hire_date' => '2023-06-01',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No institution found');

        $this->service->create($data);
    }

    public function test_update_staff_updates_personal_and_employment_information(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $originalName = $staff->person->first_name;

        $updateData = [
            'personalInformation' => [
                'first_name' => 'UpdatedName',
            ],
            'employmentInformation' => [
                'staff_number' => 'UPDATED001',
            ],
        ];

        $updatedStaff = $this->service->update($staff, $updateData);

        $this->assertEquals('UpdatedName', $updatedStaff->person->first_name);
        $this->assertEquals('UPDATED001', $updatedStaff->staff_number);
        $this->assertNotEquals($originalName, $updatedStaff->person->first_name);
    }

    public function test_add_contact_creates_contact_for_person(): void
    {
        $person = Person::factory()->create();

        $contactData = [
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0201234567',
        ];

        $contact = $this->service->addContact($person, $contactData);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertEquals('0201234567', $contact->contact);
        $this->assertDatabaseHas('contacts', [
            'person_id' => $person->id,
            'contact' => '0201234567',
        ]);
    }

    public function test_update_contact_modifies_existing_contact(): void
    {
        $person = Person::factory()->create();
        $contact = $person->contacts()->create([
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0551111111',
        ]);

        $updatedContact = $this->service->updateContact($contact, [
            'contact' => '0552222222',
        ]);

        $this->assertEquals('0552222222', $updatedContact->contact);
    }

    public function test_delete_contact_removes_contact(): void
    {
        $person = Person::factory()->create();
        $contact = $person->contacts()->create([
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0553333333',
        ]);

        $this->service->deleteContact($contact);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_add_address_closes_previous_active_address(): void
    {
        $person = Person::factory()->create();
        $oldAddress = $person->address()->create([
            'address_line_1' => 'Old Address',
            'city' => 'Accra',
            'country' => 'Ghana',
            'valid_end' => null,
        ]);

        $newAddressData = [
            'address_line_1' => 'New Address',
            'city' => 'Kumasi',
            'country' => 'Ghana',
        ];

        $newAddress = $this->service->addAddress($person, $newAddressData);

        $oldAddress->refresh();

        $this->assertNotNull($oldAddress->valid_end);
        $this->assertNull($newAddress->valid_end);
        $this->assertEquals('New Address', $newAddress->address_line_1);
    }

    public function test_add_identity_creates_identity_document(): void
    {
        $person = Person::factory()->create();

        $identityData = [
            'id_type' => Identity::Passport->value,
            'id_number' => 'G1234567',
        ];

        $identity = $this->service->addIdentity($person, $identityData);

        $this->assertInstanceOf(PersonIdentity::class, $identity);
        $this->assertEquals('G1234567', $identity->id_number);
    }

    public function test_update_identity_modifies_existing_identity(): void
    {
        $person = Person::factory()->create();
        $identity = $person->identities()->create([
            'id_type' => Identity::Passport->value,
            'id_number' => 'OLD123',
        ]);

        $updatedIdentity = $this->service->updateIdentity($identity, [
            'id_number' => 'NEW456',
        ]);

        $this->assertEquals('NEW456', $updatedIdentity->id_number);
    }

    public function test_delete_identity_removes_identity_document(): void
    {
        $person = Person::factory()->create();
        $identity = $person->identities()->create([
            'id_type' => Identity::Passport->value,
            'id_number' => 'DELETE123',
        ]);

        $this->service->deleteIdentity($identity);

        $this->assertDatabaseMissing('person_identities', [
            'id' => $identity->id,
        ]);
    }
}
