<?php

namespace Tests\Feature\MyProfile;

use App\Models\Address;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileAddressChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_change_own_address_and_previous_is_archived(): void
    {
        $user = $this->staffUser();
        $person = Person::find($user->person_id);

        $oldAddress = $this->createAddressForPerson($person);

        $this->actingAs($user)
            ->post(route('person.address.change', ['person' => $person->id]), [
                'address_line_1' => '99 New Street',
                'address_line_2' => null,
                'city' => 'NewCity',
                'region' => null,
                'country' => 'GH',
                'post_code' => null,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        // Old address should now have a valid_end date
        $this->assertNotNull($oldAddress->fresh()->valid_end);
        $this->assertEquals(now()->toDateString(), $oldAddress->fresh()->valid_end);

        // New active address should exist
        $this->assertDatabaseHas('addresses', [
            'addressable_type' => (new Person)->getMorphClass(),
            'addressable_id' => $person->id,
            'address_line_1' => '99 New Street',
            'city' => 'NewCity',
            'valid_end' => null,
        ]);
    }

    public function test_address_change_requires_address_line_1_city_and_country(): void
    {
        $user = $this->staffUser();
        $person = Person::find($user->person_id);

        $this->createAddressForPerson($person);
        $countBefore = $person->address()->count();

        $this->actingAs($user)
            ->post(route('person.address.change', ['person' => $person->id]), [
                'address_line_1' => '',
                'city' => '',
                'country' => '',
            ])
            ->assertSessionHasErrors(['address_line_1', 'city', 'country']);

        // No new row should have been created
        $this->assertEquals($countBefore, $person->address()->count());
    }

    public function test_staff_cannot_change_another_persons_address(): void
    {
        $me = $this->staffUser();

        $other = InstitutionPerson::factory()->create();
        $otherPerson = Person::find($other->person_id);
        $otherAddress = $this->createAddressForPerson($otherPerson);

        $this->actingAs($me)
            ->post(route('person.address.change', ['person' => $otherPerson->id]), [
                'address_line_1' => 'Hacker Lane',
                'city' => 'HackCity',
                'country' => 'GH',
            ])
            ->assertForbidden();

        // Other person's address should remain unchanged
        $this->assertNull($otherAddress->fresh()->valid_end);
        $this->assertDatabaseMissing('addresses', [
            'addressable_id' => $otherPerson->id,
            'address_line_1' => 'Hacker Lane',
        ]);
    }

    public function test_address_change_when_no_active_address_exists_creates_first_one(): void
    {
        $user = $this->staffUser();
        $person = Person::find($user->person_id);

        // Confirm no addresses exist
        $this->assertEquals(0, $person->address()->count());

        $this->actingAs($user)
            ->post(route('person.address.change', ['person' => $person->id]), [
                'address_line_1' => '1 First Ave',
                'address_line_2' => null,
                'city' => 'FirstCity',
                'region' => null,
                'country' => 'GH',
                'post_code' => null,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('addresses', [
            'addressable_type' => (new Person)->getMorphClass(),
            'addressable_id' => $person->id,
            'address_line_1' => '1 First Ave',
            'city' => 'FirstCity',
            'valid_end' => null,
        ]);

        $this->assertEquals(1, $person->address()->count());
    }

    private function staffUser(): User
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    private function createAddressForPerson(Person $person): Address
    {
        return $person->address()->create([
            'address_line_1' => '1 Original Road',
            'address_line_2' => null,
            'city' => 'OriginalCity',
            'region' => 'OriginalRegion',
            'country' => 'GH',
            'post_code' => '00100',
        ]);
    }
}
