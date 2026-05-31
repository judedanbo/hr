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
}
