<?php

namespace Tests\Feature\Person;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\Dependent;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Reading a person's record follows the rule of the Staff page: your own
 * record, or anyone's with the view all staff permission. Being signed in is
 * not enough to read a colleague's.
 */
class PersonReadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // The person page
    // -----------------------------------------------------------------------

    public function test_staff_cannot_open_another_persons_page(): void
    {
        $this->actingAs($this->staffUser())
            ->get(route('person.show', ['person' => $this->colleague()->id]))
            ->assertForbidden();
    }

    public function test_staff_can_open_their_own_page(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->get(route('person.show', ['person' => $user->person_id]))
            ->assertOk();
    }

    public function test_staff_can_open_the_page_of_their_own_dependant(): void
    {
        $user = $this->staffUser();
        $dependant = $this->dependantOf($this->staffRecordOf($user));

        $this->actingAs($user)
            ->get(route('person.show', ['person' => $dependant->id]))
            ->assertOk();
    }

    public function test_staff_cannot_open_the_page_of_a_colleagues_dependant(): void
    {
        $dependant = $this->dependantOf($this->activeStaff());

        $this->actingAs($this->staffUser())
            ->get(route('person.show', ['person' => $dependant->id]))
            ->assertForbidden();
    }

    public function test_a_user_with_no_staff_record_cannot_open_a_person_page(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('person.show', ['person' => $this->colleague()->id]))
            ->assertForbidden();
    }

    public function test_a_person_page_that_does_not_exist_is_not_found(): void
    {
        $this->actingAs($this->userWithRole('hr-user'))
            ->get(route('person.show', ['person' => 999999]))
            ->assertNotFound();
    }

    #[DataProvider('rolesThatViewAllStaff')]
    public function test_roles_that_view_all_staff_can_open_any_persons_page(string $role): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route('person.show', ['person' => $this->colleague()->id]))
            ->assertOk();
    }

    public function test_the_view_person_permission_also_opens_a_persons_page(): void
    {
        $this->actingAs($this->userWithPermissions(['view person']))
            ->get(route('person.show', ['person' => $this->colleague()->id]))
            ->assertOk();
    }

    // -----------------------------------------------------------------------
    // The people list
    // -----------------------------------------------------------------------

    public function test_staff_cannot_list_people(): void
    {
        $this->actingAs($this->staffUser())
            ->get(route('person.index'))
            ->assertForbidden();
    }

    #[DataProvider('rolesThatViewAllStaff')]
    public function test_roles_that_view_all_staff_can_list_people(string $role): void
    {
        $this->colleague();

        $this->actingAs($this->userWithRole($role))
            ->get(route('person.index'))
            ->assertOk();
    }

    public function test_the_view_all_people_permission_also_lists_people(): void
    {
        $this->actingAs($this->userWithPermissions(['view all people']))
            ->get(route('person.index'))
            ->assertOk();
    }

    // -----------------------------------------------------------------------
    // Data the person and staff pages load separately
    // -----------------------------------------------------------------------

    #[DataProvider('personLookups')]
    public function test_staff_cannot_load_a_colleagues_details(string $route): void
    {
        $this->actingAs($this->staffUser())
            ->get(route($route, ['person' => $this->colleague()->id]))
            ->assertForbidden();
    }

    #[DataProvider('personLookups')]
    public function test_staff_can_load_their_own_details(string $route): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->get(route($route, ['person' => $user->person_id]))
            ->assertOk();
    }

    #[DataProvider('personLookups')]
    public function test_a_holder_of_view_all_staff_can_load_anyones_details(string $route): void
    {
        $this->actingAs($this->userWithRole('general-admin-user'))
            ->get(route($route, ['person' => $this->colleague()->id]))
            ->assertOk();
    }

    public function test_the_edit_form_data_follows_the_rule_for_saving_it(): void
    {
        $user = $this->staffUser();
        $colleague = $this->colleague(['surname' => 'Mensah']);

        $this->actingAs($user)
            ->get(route('person.edit', ['person' => $colleague->id]))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('person.edit', ['person' => $user->person_id]))
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions(['update staff']))
            ->get(route('person.edit', ['person' => $colleague->id]))
            ->assertOk()
            ->assertJsonPath('surname', 'Mensah');
    }

    // -----------------------------------------------------------------------
    // The contact edit form
    // -----------------------------------------------------------------------

    public function test_staff_cannot_load_a_colleagues_contact(): void
    {
        $contact = $this->phoneOf($this->colleague());

        $this->actingAs($this->staffUser())
            ->get(route('contact.edit', ['contact' => $contact->id]))
            ->assertForbidden();
    }

    public function test_staff_can_load_their_own_contact(): void
    {
        $user = $this->staffUser();
        $contact = $this->phoneOf(Person::findOrFail($user->person_id));

        $this->actingAs($user)
            ->get(route('contact.edit', ['contact' => $contact->id]))
            ->assertOk()
            ->assertJsonPath('contact.contact', $contact->contact);
    }

    public function test_a_holder_of_update_staff_can_load_any_contact(): void
    {
        $contact = $this->phoneOf($this->colleague());

        $this->actingAs($this->userWithPermissions(['update contacts', 'update staff']))
            ->get(route('contact.edit', ['contact' => $contact->id]))
            ->assertOk();
    }

    // -----------------------------------------------------------------------
    // Signed out
    // -----------------------------------------------------------------------

    public function test_signed_out_visitors_are_sent_to_the_login_page(): void
    {
        $colleague = $this->colleague();

        foreach (['person.show', 'person.edit', 'person-roles.show', 'person-roles.dependent', 'person.avatar'] as $route) {
            $this->get(route($route, ['person' => $colleague->id]))->assertRedirect(route('login'));
        }

        $this->get(route('person.index'))->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rolesThatViewAllStaff(): array
    {
        return [
            'admin-user' => ['admin-user'],
            'aag-admin' => ['aag-admin'],
            'hr-user' => ['hr-user'],
            'personel-user' => ['personel-user'],
            'general-admin-user' => ['general-admin-user'],
            'internal-audit-user' => ['internal-audit-user'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function personLookups(): array
    {
        return [
            'roles summary' => ['person-roles.show'],
            'dependant summary' => ['person-roles.dependent'],
            'photo' => ['person.avatar'],
        ];
    }

    /**
     * A signed-in member of staff with only the staff role.
     */
    private function staffUser(): User
    {
        return $this->userWithRole('staff');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * A signed-in member of staff holding the given permissions directly.
     *
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        return $user;
    }

    /**
     * Another member of staff's person record.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function colleague(array $attributes = []): Person
    {
        $person = Person::factory()->create($attributes);
        $this->activeStaff($person);

        return $person;
    }

    private function activeStaff(?Person $person = null): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create(
            $person ? ['person_id' => $person->id] : []
        );
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }

    private function staffRecordOf(User $user): InstitutionPerson
    {
        return InstitutionPerson::query()->where('person_id', $user->person_id)->firstOrFail();
    }

    private function dependantOf(InstitutionPerson $staff): Person
    {
        $person = Person::factory()->create();
        Dependent::create([
            'staff_id' => $staff->id,
            'person_id' => $person->id,
            'relation' => 'Child',
        ]);

        return $person;
    }

    private function phoneOf(Person $person): Contact
    {
        return Contact::create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000111',
        ]);
    }
}
