<?php

namespace Tests\Feature\Person;

use App\Enums\Identity;
use App\Models\Address;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\PersonIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A person's record (core details, identities, addresses) may be changed by
 * its owner or by someone holding the update staff permission, and by nobody
 * else who merely happens to be signed in.
 */
class PersonRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Core person record
    // -----------------------------------------------------------------------

    public function test_staff_cannot_update_another_persons_record(): void
    {
        $colleague = $this->colleague(['surname' => 'Original']);

        $this->actingAs($this->staffUser())
            ->patch(route('person.update', ['person' => $colleague->id]), $this->personPayload(['surname' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Original', $colleague->fresh()->surname);
    }

    public function test_staff_can_update_their_own_record(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->patch(route('person.update', ['person' => $user->person_id]), $this->personPayload(['surname' => 'Renamed']))
            ->assertRedirect(route('person.show', ['person' => $user->person_id]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', Person::findOrFail($user->person_id)->surname);
    }

    public function test_user_with_update_staff_can_update_another_persons_record(): void
    {
        $colleague = $this->colleague(['surname' => 'Original']);

        $this->actingAs($this->userWithPermissions(['update staff']))
            ->patch(route('person.update', ['person' => $colleague->id]), $this->personPayload(['surname' => 'Corrected']))
            ->assertRedirect(route('person.show', ['person' => $colleague->id]));

        $this->assertSame('Corrected', $colleague->fresh()->surname);
    }

    public function test_user_with_no_linked_person_cannot_update_a_person_record(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $user->assignRole('staff');
        $colleague = $this->colleague(['surname' => 'Original']);

        $this->actingAs($user)
            ->patch(route('person.update', ['person' => $colleague->id]), $this->personPayload(['surname' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Original', $colleague->fresh()->surname);
    }

    public function test_staff_cannot_create_a_person_record(): void
    {
        $user = $this->staffUser();
        $peopleBefore = Person::count();

        $this->actingAs($user)
            ->post(route('person.store'), $this->personPayload())
            ->assertForbidden();

        $this->assertSame($peopleBefore, Person::count());
    }

    public function test_the_person_create_permission_passes_the_person_store_gate(): void
    {
        $response = $this->actingAs($this->userWithPermissions(['view create person']))
            ->post(route('person.store'), $this->personPayload());

        $this->assertNotSame(403, $response->getStatusCode());
    }

    // -----------------------------------------------------------------------
    // Identities
    // -----------------------------------------------------------------------

    public function test_staff_cannot_add_an_identity_to_another_person(): void
    {
        $colleague = $this->colleague();

        $this->actingAs($this->staffUser())
            ->post(route('person.identity.create', ['person' => $colleague->id]), [
                'id_type' => Identity::Passport->value,
                'id_number' => 'P7654321',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('person_identities', ['person_id' => $colleague->id]);
    }

    public function test_staff_cannot_change_another_persons_identity(): void
    {
        $colleague = $this->colleague();
        $identity = $this->identityFor($colleague, 'P0000001');

        $this->actingAs($this->staffUser())
            ->post(route('person.identity.update', ['person' => $colleague->id, 'identity' => $identity->id]), [
                'id_type' => Identity::Passport->value,
                'id_number' => 'P9999999',
            ])
            ->assertForbidden();

        $this->assertSame('P0000001', $identity->fresh()->id_number);
    }

    public function test_staff_cannot_delete_another_persons_identity(): void
    {
        $colleague = $this->colleague();
        $identity = $this->identityFor($colleague, 'P0000001');

        $this->actingAs($this->staffUser())
            ->delete(route('person.identity.delete', ['person' => $colleague->id, 'identity' => $identity->id]))
            ->assertForbidden();

        $this->assertNotNull(PersonIdentity::find($identity->id));
    }

    public function test_an_identity_request_for_another_person_is_refused_before_it_is_validated(): void
    {
        $colleague = $this->colleague();
        $this->identityFor($colleague, 'GHA-123456789-0', Identity::GhanaCard);

        // The number is already taken, which validation would report. Someone with no
        // right to touch this record must be refused instead, so the validation
        // messages cannot be used to find out which ID numbers are on file.
        $this->actingAs($this->staffUser())
            ->post(route('person.identity.create', ['person' => $colleague->id]), [
                'id_type' => Identity::GhanaCard->value,
                'id_number' => 'GHA-123456789-0',
            ])
            ->assertForbidden();
    }

    public function test_staff_can_manage_their_own_identities(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->post(route('person.identity.create', ['person' => $user->person_id]), [
                'id_type' => Identity::Passport->value,
                'id_number' => 'P1111111',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $identity = PersonIdentity::where('person_id', $user->person_id)->firstOrFail();
        $this->assertSame('P1111111', $identity->id_number);

        $this->actingAs($user)
            ->post(route('person.identity.update', ['person' => $user->person_id, 'identity' => $identity->id]), [
                'id_type' => Identity::Passport->value,
                'id_number' => 'P2222222',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('P2222222', $identity->fresh()->id_number);

        $this->actingAs($user)
            ->delete(route('person.identity.delete', ['person' => $user->person_id, 'identity' => $identity->id]))
            ->assertRedirect();

        $this->assertNull(PersonIdentity::withTrashed()->find($identity->id));
    }

    public function test_staff_cannot_delete_another_persons_identity_through_their_own_person_route(): void
    {
        $user = $this->staffUser();
        $identity = $this->identityFor($this->colleague(), 'P0000001');

        $this->actingAs($user)
            ->delete(route('person.identity.delete', ['person' => $user->person_id, 'identity' => $identity->id]));

        $this->assertNotNull(PersonIdentity::find($identity->id));
    }

    public function test_user_with_update_staff_can_add_an_identity_to_another_person(): void
    {
        $colleague = $this->colleague();

        $this->actingAs($this->userWithPermissions(['update staff']))
            ->post(route('person.identity.create', ['person' => $colleague->id]), [
                'id_type' => Identity::Passport->value,
                'id_number' => 'P7654321',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('person_identities', [
            'person_id' => $colleague->id,
            'id_number' => 'P7654321',
        ]);
    }

    // -----------------------------------------------------------------------
    // Addresses
    // -----------------------------------------------------------------------

    public function test_staff_cannot_add_an_address_to_another_person(): void
    {
        $colleague = $this->colleague();
        $current = $this->addressFor($colleague);

        $this->actingAs($this->staffUser())
            ->post(route('person.address.create', ['person' => $colleague->id]), $this->addressPayload())
            ->assertForbidden();

        // Adding an address retires the current one, so both halves must be untouched.
        $this->assertNull($current->fresh()->valid_end);
        $this->assertSame(1, $colleague->address()->count());
    }

    public function test_staff_can_add_their_own_address(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->post(route('person.address.create', ['person' => $user->person_id]), $this->addressPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'addressable_type' => (new Person)->getMorphClass(),
            'addressable_id' => $user->person_id,
            'address_line_1' => '12 Independence Avenue',
            'valid_end' => null,
        ]);
    }

    public function test_user_with_update_staff_can_add_an_address_to_another_person(): void
    {
        $colleague = $this->colleague();

        $this->actingAs($this->userWithPermissions(['update staff']))
            ->post(route('person.address.create', ['person' => $colleague->id]), $this->addressPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $colleague->address()->count());
    }

    public function test_staff_cannot_delete_another_persons_address(): void
    {
        $colleague = $this->colleague();
        $address = $this->addressFor($colleague);

        $this->actingAs($this->staffUser())
            ->delete(route('person.address.delete', ['person' => $colleague->id, 'address' => $address->id]))
            ->assertForbidden();

        $this->assertNotSoftDeleted('addresses', ['id' => $address->id]);
    }

    public function test_staff_can_delete_one_of_their_own_addresses_and_only_that_one(): void
    {
        $user = $this->staffUser();
        $person = Person::findOrFail($user->person_id);
        $previous = $this->addressFor($person, ['valid_end' => now()->subYear()->toDateString()]);
        $current = $this->addressFor($person);

        $this->actingAs($user)
            ->delete(route('person.address.delete', ['person' => $person->id, 'address' => $previous->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('addresses', ['id' => $previous->id]);
        $this->assertNotSoftDeleted('addresses', ['id' => $current->id]);
    }

    public function test_staff_cannot_delete_another_persons_address_through_their_own_person_route(): void
    {
        $user = $this->staffUser();
        $address = $this->addressFor($this->colleague());

        $this->actingAs($user)
            ->delete(route('person.address.delete', ['person' => $user->person_id, 'address' => $address->id]))
            ->assertNotFound();

        $this->assertNotSoftDeleted('addresses', ['id' => $address->id]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * A signed-in member of staff holding only the staff role.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole('staff');

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
        $user->givePermissionTo($permissions);

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

    private function identityFor(Person $person, string $number, Identity $type = Identity::Passport): PersonIdentity
    {
        return $person->identities()->create([
            'id_type' => $type,
            'id_number' => $number,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function addressFor(Person $person, array $attributes = []): Address
    {
        return $person->address()->create(array_merge([
            'address_line_1' => '1 Original Road',
            'city' => 'Accra',
            'country' => 'GH',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function personPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Mr',
            'surname' => 'Mensah',
            'first_name' => 'Kwame',
            'other_names' => 'Kofi',
            'date_of_birth' => '1985-06-15',
            'gender' => 'M',
            'nationality' => 'GH',
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function addressPayload(): array
    {
        return [
            'address_line_1' => '12 Independence Avenue',
            'city' => 'Accra',
            'country' => 'GH',
        ];
    }
}
