<?php

namespace Tests\Feature\MyProfile;

use App\Models\Address;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyProfileAddressUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_their_own_address(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'update contacts']));

        $address = $this->createAddressForPerson($staff->person);

        $this->actingAs($user)
            ->patch(route('person.address.update', ['person' => $staff->person_id, 'address' => $address->id]), [
                'address_line_1' => '123 Updated St',
                'address_line_2' => null,
                'city' => 'UpdatedCity',
                'region' => 'UpdatedRegion',
                'country' => 'GH',
                'post_code' => '00233',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'address_line_1' => '123 Updated St',
            'city' => 'UpdatedCity',
        ]);
    }

    public function test_stranger_cannot_update_another_persons_address(): void
    {
        $owner = $this->createActiveStaff();
        $address = $this->createAddressForPerson($owner->person);

        $stranger = $this->createActiveStaff();
        $strangerUser = User::factory()->create(['person_id' => $stranger->person_id]);
        $strangerUser->givePermissionTo(Permission::firstOrCreate(['name' => 'update contacts']));

        $this->actingAs($strangerUser)
            ->patch(route('person.address.update', ['person' => $owner->person_id, 'address' => $address->id]), [
                'address_line_1' => 'Hacked St',
                'address_line_2' => null,
                'city' => 'HackedCity',
                'region' => null,
                'country' => 'GH',
                'post_code' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
            'address_line_1' => 'Hacked St',
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $staff = $this->createActiveStaff();
        $address = $this->createAddressForPerson($staff->person);

        $this->patch(route('person.address.update', ['person' => $staff->person_id, 'address' => $address->id]), [
            'address_line_1' => '123 Updated St',
            'address_line_2' => null,
            'city' => 'UpdatedCity',
            'region' => null,
            'country' => 'GH',
            'post_code' => null,
        ])
            ->assertRedirect(route('login'));
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
