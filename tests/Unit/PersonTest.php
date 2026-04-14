<?php

namespace Tests\Unit;

use App\Enums\ContactTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Models\Contact;
use App\Models\Dependent;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\PersonIdentity;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonTest extends TestCase
{
    use RefreshDatabase;

    // ===================
    // MODEL ATTRIBUTE TESTS
    // ===================

    public function test_person_uses_soft_deletes(): void
    {
        $person = Person::factory()->create();
        $person->delete();

        $this->assertSoftDeleted($person);
        $this->assertNotNull(Person::withTrashed()->find($person->id));
    }

    public function test_gender_is_cast_to_enum(): void
    {
        $person = Person::factory()->create(['gender' => 'M']);

        $this->assertInstanceOf(GenderEnum::class, $person->gender);
        $this->assertEquals(GenderEnum::MALE, $person->gender);
    }

    public function test_date_of_birth_is_cast_to_date(): void
    {
        $person = Person::factory()->create(['date_of_birth' => '1990-05-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $person->date_of_birth);
    }

    public function test_marital_status_is_cast_to_enum(): void
    {
        $person = Person::factory()->create(['marital_status' => 'S']);

        $this->assertInstanceOf(MaritalStatusEnum::class, $person->marital_status);
    }

    // ===================
    // ACCESSOR TESTS
    // ===================

    public function test_full_name_attribute_returns_complete_name(): void
    {
        $person = Person::factory()->create([
            'title' => 'Mr.',
            'first_name' => 'John',
            'other_names' => 'William',
            'surname' => 'Doe',
        ]);

        $this->assertEquals('Mr. John William Doe', $person->full_name);
    }

    public function test_full_name_with_null_other_names(): void
    {
        $person = Person::factory()->create([
            'title' => 'Ms.',
            'first_name' => 'Jane',
            'other_names' => null,
            'surname' => 'Smith',
        ]);

        $this->assertEquals('Ms. Jane  Smith', $person->full_name);
    }

    public function test_initials_attribute_returns_correct_initials(): void
    {
        $person = Person::factory()->create([
            'first_name' => 'John',
            'other_names' => 'William',
            'surname' => 'Doe',
        ]);

        $this->assertEquals('JD', $person->initials);
    }

    public function test_initials_with_double_surname(): void
    {
        $person = Person::factory()->create([
            'first_name' => null,
            'other_names' => null,
            'surname' => 'Van Der Berg',
        ]);

        $this->assertEquals('VD', $person->initials);
    }

    public function test_age_attribute_calculates_correctly(): void
    {
        $birthDate = now()->subYears(30)->subMonths(5);
        $person = Person::factory()->create(['date_of_birth' => $birthDate]);

        $this->assertEquals(30, $person->age);
    }

    public function test_age_attribute_with_null_date_of_birth(): void
    {
        $person = Person::factory()->create(['date_of_birth' => null]);

        $this->assertEquals(0, $person->age);
    }

    // ===================
    // RELATIONSHIP TESTS
    // ===================

    public function test_person_belongs_to_many_institutions(): void
    {
        $person = Person::factory()->create();
        $institution = Institution::factory()->create();

        $person->institution()->attach($institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $this->assertInstanceOf(Institution::class, $person->institution->first());
        $this->assertEquals($institution->id, $person->institution->first()->id);
    }

    public function test_person_has_many_contacts(): void
    {
        $person = Person::factory()->create();

        Contact::create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0241234567',
        ]);

        $this->assertCount(1, $person->contacts);
        $this->assertEquals('0241234567', $person->contacts->first()->contact);
    }

    public function test_person_has_many_identities(): void
    {
        $person = Person::factory()->create();

        PersonIdentity::create([
            'person_id' => $person->id,
            'id_type' => 'G',
            'id_number' => 'GHA-123456789-1',
        ]);

        $this->assertCount(1, $person->identities);
    }

    public function test_person_has_many_qualifications(): void
    {
        $person = Person::factory()->create();

        Qualification::create([
            'person_id' => $person->id,
            'qualification' => 'Bachelor of Science',
            'institution' => 'University of Ghana',
            'year' => 2015,
        ]);

        $this->assertCount(1, $person->qualifications);
    }

    public function test_person_has_many_addresses(): void
    {
        $person = Person::factory()->create();

        $person->address()->create([
            'address_line_1' => '123 Test Street',
            'city' => 'Accra',
            'country' => 'Ghana',
        ]);

        $this->assertCount(1, $person->address);
    }

    public function test_person_has_one_user(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id]);

        $this->assertInstanceOf(User::class, $person->user);
        $this->assertEquals($user->id, $person->user->id);
    }

    public function test_person_has_many_dependents_through_staff(): void
    {
        $person = Person::factory()->create();
        $institution = Institution::factory()->create();

        $person->institution()->attach($institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Create dependent person
        $dependentPerson = Person::factory()->create();
        Dependent::create([
            'staff_id' => $staff->id,
            'person_id' => $dependentPerson->id,
            'relation' => 'Child',
        ]);

        $this->assertCount(1, $person->dependents);
    }

    // ===================
    // SCOPE TESTS
    // ===================

    public function test_search_scope_finds_by_first_name(): void
    {
        Person::factory()->create(['first_name' => 'UniqueFirstName']);
        Person::factory()->create(['first_name' => 'Other']);

        $results = Person::search('UniqueFirstName')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('UniqueFirstName', $results->first()->first_name);
    }

    public function test_search_scope_finds_by_surname(): void
    {
        Person::factory()->create(['surname' => 'UniqueSurname']);

        $results = Person::search('UniqueSurname')->get();

        $this->assertCount(1, $results);
    }

    public function test_search_scope_finds_by_multiple_terms(): void
    {
        Person::factory()->create([
            'first_name' => 'John',
            'surname' => 'Doe',
        ]);

        $results = Person::search('John Doe')->get();

        $this->assertCount(1, $results);
    }

    public function test_male_scope_filters_male_persons(): void
    {
        Person::factory()->create(['gender' => 'M', 'surname' => 'MalePerson']);
        Person::factory()->create(['gender' => 'F', 'surname' => 'FemalePerson']);

        $results = Person::male()->get();

        $this->assertTrue($results->contains('surname', 'MalePerson'));
        $this->assertFalse($results->contains('surname', 'FemalePerson'));
    }

    public function test_female_scope_filters_female_persons(): void
    {
        Person::factory()->create(['gender' => 'F', 'surname' => 'FemalePerson']);
        Person::factory()->create(['gender' => 'M', 'surname' => 'MalePerson']);

        $results = Person::female()->get();

        $this->assertTrue($results->contains('surname', 'FemalePerson'));
        $this->assertFalse($results->contains('surname', 'MalePerson'));
    }

    public function test_order_dob_scope_orders_by_date_of_birth(): void
    {
        $older = Person::factory()->create(['date_of_birth' => '1980-01-01']);
        Person::factory()->create(['date_of_birth' => '2000-01-01']);

        $results = Person::orderDob()->get();

        $this->assertEquals($older->id, $results->first()->id);
    }

    // ===================
    // HELPER METHOD TESTS
    // ===================

    public function test_is_staff_returns_true_for_staff_member(): void
    {
        $person = Person::factory()->create();
        $institution = Institution::factory()->create();

        $person->institution()->attach($institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);

        $this->assertTrue($person->isStaff());
    }

    public function test_is_staff_returns_false_for_non_staff(): void
    {
        $person = Person::factory()->create();

        $this->assertFalse($person->isStaff());
    }

    // ===================
    // FACTORY TESTS
    // ===================

    public function test_factory_creates_valid_person(): void
    {
        $person = Person::factory()->create();

        $this->assertNotNull($person->surname);
        $this->assertNotNull($person->first_name);
        $this->assertNotNull($person->date_of_birth);
    }

    public function test_factory_creates_unique_persons(): void
    {
        $person1 = Person::factory()->create();
        $person2 = Person::factory()->create();

        $this->assertNotEquals($person1->id, $person2->id);
    }

    // ===================
    // EDGE CASE TESTS
    // ===================

    public function test_fillable_fields_can_be_mass_assigned(): void
    {
        $data = [
            'title' => 'Dr.',
            'surname' => 'Smith',
            'first_name' => 'Jane',
            'other_names' => 'Elizabeth',
            'date_of_birth' => '1985-03-20',
            'gender' => 'F',
            'marital_status' => MaritalStatusEnum::MARRIED,
        ];

        $person = Person::create($data);

        $this->assertEquals('Dr.', $person->title);
        $this->assertEquals('Smith', $person->surname);
        $this->assertEquals('Jane', $person->first_name);
    }

    public function test_person_can_have_multiple_institutions(): void
    {
        $person = Person::factory()->create();
        $institution1 = Institution::factory()->create();
        $institution2 = Institution::factory()->create();

        $person->institution()->attach($institution1->id, [
            'staff_number' => 'STF001',
            'hire_date' => now(),
        ]);
        $person->institution()->attach($institution2->id, [
            'staff_number' => 'STF002',
            'hire_date' => now(),
        ]);

        $this->assertCount(2, $person->institution);
    }
}
