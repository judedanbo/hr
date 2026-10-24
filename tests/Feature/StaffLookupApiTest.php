<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Enums\StaffTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\StaffType;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffLookupApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createActiveStaff(string $staffNumber): InstitutionPerson
    {
        $person = Person::factory()->create([
            'title' => 'Mr',
            'first_name' => 'Kwame',
            'other_names' => 'Kofi',
            'surname' => 'Mensah',
            'gender' => 'M',
            'marital_status' => 'M',
            'date_of_birth' => '1980-05-15',
        ]);

        $staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'staff_number' => $staffNumber,
            'file_number' => 'FILE-001',
            'hire_date' => '2010-01-04',
        ]);

        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);

        return $staff;
    }

    public function test_returns_details_of_an_active_staff_member(): void
    {
        $staff = $this->createActiveStaff('123456');

        $category = JobCategory::factory()->create(['name' => 'Audit Professional']);
        $oldRank = Job::factory()->create(['name' => 'Auditor', 'job_category_id' => $category->id]);
        $rank = Job::factory()->create(['name' => 'Senior Auditor', 'job_category_id' => $category->id]);
        $staff->ranks()->attach($oldRank->id, ['start_date' => '2010-01-04', 'end_date' => '2015-01-01']);
        $staff->ranks()->attach($rank->id, ['start_date' => '2015-01-01']);

        $department = Unit::factory()->create(['name' => 'Finance Department']);
        $unit = Unit::factory()->create(['name' => 'Payroll Unit', 'unit_id' => $department->id]);
        $staff->units()->attach($unit->id, ['start_date' => '2015-01-01']);

        StaffType::create([
            'staff_id' => $staff->id,
            'staff_type' => StaffTypeEnum::Field,
            'start_date' => '2010-01-04',
        ]);

        Contact::factory()->create(['person_id' => $staff->person_id, 'contact_type' => ContactTypeEnum::EMAIL, 'contact' => 'kwame@audit.gov.gh']);
        Contact::factory()->create(['person_id' => $staff->person_id, 'contact_type' => ContactTypeEnum::PHONE, 'contact' => '0241234567']);
        Contact::factory()->create(['person_id' => $staff->person_id, 'contact_type' => ContactTypeEnum::PHONE, 'contact' => '0200000000', 'valid_end' => now()->subYear()]);
        Contact::factory()->create(['person_id' => $staff->person_id, 'contact_type' => ContactTypeEnum::ADDRESS, 'contact' => 'P.O. Box 1, Accra']);

        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/123456')
            ->assertOk()
            ->assertExactJson([
                'staff_number' => '123456',
                'file_number' => 'FILE-001',
                'title' => 'Mr',
                'first_name' => 'Kwame',
                'other_names' => 'Kofi',
                'surname' => 'Mensah',
                'gender' => 'Male',
                'date_of_birth' => '1980-05-15',
                'age' => $staff->person->fresh()->age,
                'retirement_date' => '2040-05-15',
                'marital_status' => 'Married',
                'nationality' => 'Ghanaian',
                'hire_date' => '2010-01-04',
                'status' => 'Active staff',
                'staff_type' => 'Field Staff',
                'rank' => 'Senior Auditor',
                'job_category' => 'Audit Professional',
                'unit' => 'Payroll Unit',
                'department' => 'Finance Department',
                'emails' => ['kwame@audit.gov.gh'],
                'phones' => ['0241234567'],
            ]);
    }

    public function test_staff_without_assignments_returns_nulls(): void
    {
        $this->createActiveStaff('222222');

        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/222222')
            ->assertOk()
            ->assertJson([
                'staff_number' => '222222',
                'staff_type' => null,
                'rank' => null,
                'job_category' => null,
                'unit' => null,
                'department' => null,
                'emails' => [],
                'phones' => [],
            ]);
    }

    public function test_unknown_staff_number_returns_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Staff not found.']);
    }

    public function test_retired_staff_returns_not_found(): void
    {
        $staff = InstitutionPerson::factory()->create(['staff_number' => '333333']);
        Status::factory()->retired()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);

        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/333333')->assertNotFound();
    }

    public function test_staff_whose_active_status_has_ended_returns_not_found(): void
    {
        $staff = InstitutionPerson::factory()->create(['staff_number' => '444444']);
        Status::factory()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id, 'end_date' => now()->subDay()]);

        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/444444')->assertNotFound();
    }

    public function test_token_without_ability_is_forbidden(): void
    {
        $this->createActiveStaff('123456');

        Sanctum::actingAs(User::factory()->create(), ['staff-statistics:read']);

        $this->getJson('/api/staff/123456')->assertForbidden();
    }

    public function test_request_without_token_is_unauthorized(): void
    {
        $this->getJson('/api/staff/123456')->assertUnauthorized();
    }

    public function test_lookup_is_logged(): void
    {
        $this->createActiveStaff('123456');

        Sanctum::actingAs(User::factory()->create(), ['staff:read']);

        $this->getJson('/api/staff/123456')->assertOk();

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'path' => 'api/staff/123456',
            'status' => 200,
        ]);
    }
}
