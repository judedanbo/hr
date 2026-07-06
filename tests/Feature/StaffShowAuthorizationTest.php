<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffShowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** Create a Person that is an active staff member. */
    protected function staffPerson(): Person
    {
        $institution = Institution::factory()->create();
        $person = Person::factory()->create();
        $person->institution()->attach($institution->id, [
            'staff_number' => 'STAFF' . fake()->unique()->numerify('#####'),
            'hire_date' => now()->subYears(3),
        ]);

        $staff = InstitutionPerson::query()
            ->where('person_id', $person->id)
            ->where('institution_id', $institution->id)
            ->firstOrFail();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $institution->id,
        ]);

        return $person;
    }

    protected function staffId(Person $person): int
    {
        return $person->institution->first()->staff->id;
    }

    public function test_admin_who_is_also_staff_can_view_another_staff_details(): void
    {
        $adminPerson = $this->staffPerson();
        $admin = User::factory()->create(['person_id' => $adminPerson->id]);
        $admin->givePermissionTo(['view staff', 'view all staff']);

        $otherStaff = $this->staffPerson();

        $response = $this->actingAs($admin)
            ->get(route('staff.show', ['staff' => $this->staffId($otherStaff)]));

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('error');
    }

    public function test_self_service_staff_cannot_view_another_staff_details(): void
    {
        $staffPerson = $this->staffPerson();
        $staff = User::factory()->create(['person_id' => $staffPerson->id]);
        $staff->givePermissionTo('view staff');

        $otherStaff = $this->staffPerson();

        $response = $this->actingAs($staff)
            ->get(route('staff.show', ['staff' => $this->staffId($otherStaff)]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to view details of this staff');
    }

    public function test_self_service_staff_can_view_their_own_details(): void
    {
        $staffPerson = $this->staffPerson();
        $staff = User::factory()->create(['person_id' => $staffPerson->id]);
        $staff->givePermissionTo('view staff');

        $response = $this->actingAs($staff)
            ->get(route('staff.show', ['staff' => $this->staffId($staffPerson)]));

        $response->assertSessionMissing('error');
    }
}
