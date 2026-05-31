<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssociateUserStaffTest extends TestCase
{
    use RefreshDatabase;

    /** Create a Person that is a staff member (has an institution_person row). */
    protected function staffPerson(): Person
    {
        $person = Person::factory()->create();
        $person->institution()->attach(Institution::factory()->create()->id, [
            'staff_number' => 'STAFF' . fake()->unique()->numerify('#####'),
            'hire_date' => now()->subYears(3),
        ]);

        return $person;
    }

    /** A user holding the association permission. */
    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('associate user staff');

        return $user;
    }

    public function test_associate_user_staff_permission_is_seeded(): void
    {
        $this->assertTrue(
            Permission::where('name', 'associate user staff')->exists(),
            "Permission 'associate user staff' should be seeded"
        );

        $this->assertTrue(
            Role::findByName('super-administrator')->hasPermissionTo('associate user staff')
        );
    }

    public function test_staff_options_returns_unlinked_staff(): void
    {
        $linkable = $this->staffPerson();

        // A staff person already linked to another user must be excluded.
        $linked = $this->staffPerson();
        User::factory()->create(['person_id' => $linked->id]);

        // A non-staff person (no institution_person row) must be excluded.
        $nonStaff = Person::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('users.staff-options'));

        $response->assertOk();
        $values = collect($response->json())->pluck('value');

        $this->assertTrue($values->contains($linkable->id));
        $this->assertFalse($values->contains($linked->id));
        $this->assertFalse($values->contains($nonStaff->id));

        $option = collect($response->json())->firstWhere('value', $linkable->id);
        $this->assertNotNull($option);
        $this->assertStringContainsString('—', $option['label']);
        $this->assertStringContainsString($linkable->fresh()->institution->first()->staff->staff_number, $option['label']);
    }

    public function test_staff_options_requires_permission(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('users.staff-options'));

        $response->assertForbidden();
    }
}
