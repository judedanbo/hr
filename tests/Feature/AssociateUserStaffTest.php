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

    public function test_admin_can_associate_user_with_staff_record(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $person = $this->staffPerson();

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($person->id, $user->fresh()->person_id);
    }

    public function test_cannot_associate_a_non_staff_person(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $nonStaff = Person::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $nonStaff->id,
            ]);

        $response->assertSessionHasErrors('person_id');
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_cannot_associate_a_staff_already_linked_to_another_user(): void
    {
        $person = $this->staffPerson();
        User::factory()->create(['person_id' => $person->id]);

        $user = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertSessionHasErrors('person_id');
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_associate_requires_permission(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $person = $this->staffPerson();

        $response = $this->actingAs(User::factory()->create())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $person->id,
            ]);

        $response->assertForbidden();
        $this->assertNull($user->fresh()->person_id);
    }

    public function test_can_reassociate_user_to_a_different_staff_record(): void
    {
        $firstPerson = $this->staffPerson();
        $secondPerson = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $firstPerson->id]);

        $response = $this->actingAs($this->adminUser())
            ->patch(route('user.associate-staff', ['user' => $user->id]), [
                'person_id' => $secondPerson->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($secondPerson->id, $user->fresh()->person_id);
    }

    public function test_dissociate_clears_person_and_removes_staff_role(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);
        $user->assignRole('staff');

        $response = $this->actingAs($this->adminUser())
            ->delete(route('user.dissociate-staff', ['user' => $user->id]));

        $response->assertRedirect();
        $user->refresh();
        $this->assertNull($user->person_id);
        $this->assertFalse($user->hasRole('staff'));
    }

    public function test_dissociate_requires_permission(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs(User::factory()->create())
            ->delete(route('user.dissociate-staff', ['user' => $user->id]));

        $response->assertForbidden();
        $this->assertEquals($person->id, $user->fresh()->person_id);
    }

    /** A user permitted to assign roles. */
    protected function roleAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('assign roles to user');
        $user->givePermissionTo('update user roles');
        $user->givePermissionTo('update role');

        return $user;
    }

    public function test_cannot_assign_staff_role_to_unlinked_user(): void
    {
        $target = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['staff'],
            ]);

        $response->assertSessionHasErrors('roles');
        $this->assertFalse($target->fresh()->hasRole('staff'));
    }

    public function test_can_assign_staff_role_to_linked_user(): void
    {
        $person = $this->staffPerson();
        $target = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['staff'],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('staff'));
    }

    public function test_can_assign_non_staff_role_to_unlinked_user(): void
    {
        $target = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('user.add.roles', ['user' => $target->id]), [
                'roles' => ['admin-user'],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('admin-user'));
    }

    public function test_add_users_rejects_unlinked_user_for_staff_role(): void
    {
        $staffRole = Role::findByName('staff');
        $unlinked = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('role.add.users', ['role' => $staffRole->id]), [
                'users' => [$unlinked->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertFalse($unlinked->fresh()->hasRole('staff'));
    }

    public function test_add_users_allows_linked_user_for_staff_role(): void
    {
        $staffRole = Role::findByName('staff');
        $person = $this->staffPerson();
        $linked = User::factory()->create(['person_id' => $person->id]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('role.add.users', ['role' => $staffRole->id]), [
                'users' => [$linked->id],
            ]);

        $response->assertSessionHas('success');
        $this->assertTrue($linked->fresh()->hasRole('staff'));
    }

    public function test_user_show_payload_includes_staff_link(): void
    {
        $person = $this->staffPerson();
        $user = User::factory()->create(['person_id' => $person->id]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view user');

        $response = $this->actingAs($viewer)->get(route('user.show', ['user' => $user->id]));

        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('User/Show')
            ->where('user.person_id', $person->id)
            ->where('user.staff.id', $person->id)
        );
    }

    public function test_cannot_create_new_user_with_staff_role(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $creator = User::factory()->create();
        $creator->givePermissionTo('create user');

        $response = $this->actingAs($creator)
            ->post(route('user.store'), [
                'userData' => [
                    'bio' => ['name' => 'New Person', 'email' => 'newperson@example.test'],
                    'roles' => ['staff'],
                ],
            ]);

        $response->assertSessionHasErrors('userData.roles');
        $this->assertDatabaseMissing('users', ['email' => 'newperson@example.test']);
    }

    public function test_add_users_rejects_mixed_batch_when_one_is_unlinked(): void
    {
        $staffRole = \Spatie\Permission\Models\Role::findByName('staff');
        $linked = User::factory()->create(['person_id' => $this->staffPerson()->id]);
        $unlinked = User::factory()->create(['person_id' => null]);

        $response = $this->actingAs($this->roleAdmin())
            ->post(route('role.add.users', ['role' => $staffRole->id]), [
                'users' => [$linked->id, $unlinked->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertFalse($linked->fresh()->hasRole('staff'));
        $this->assertFalse($unlinked->fresh()->hasRole('staff'));
    }

    public function test_user_index_payload_includes_link_status(): void
    {
        $person = $this->staffPerson();
        $linkedUser = User::factory()->create(['person_id' => $person->id]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view all users');

        $response = $this->actingAs($viewer)->get(route('user.index'));

        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('User/Index')
            ->has('users.data', fn (\Inertia\Testing\AssertableInertia $rows) => $rows
                ->each(fn (\Inertia\Testing\AssertableInertia $row) => $row
                    ->has('person_id')
                    ->has('staff_name')
                    ->etc()
                )
            )
        );
    }
}
