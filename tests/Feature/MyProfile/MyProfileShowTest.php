<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('my-profile.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_person_id_gets_403(): void
    {
        $user = User::factory()->create(['person_id' => null]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertForbidden();
    }

    public function test_authenticated_staff_user_sees_their_profile(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MyProfile/Index')
                ->has('person')
                ->has('staff')
                ->has('qualifications')
                ->where('person.id', $staff->person_id)
            );
    }

    public function test_user_with_no_active_staff_record_sees_404(): void
    {
        // Create a person with no InstitutionPerson (active staff) record.
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertNotFound();
    }

    /**
     * Mirrors the helper introduced in StaffProfileProviderTest — creates an
     * InstitutionPerson that the `active()` scope returns.
     */
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
}
