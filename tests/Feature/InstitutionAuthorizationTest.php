<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Creating, editing and deleting institutions needs the seeded institution
 * permissions; being signed in is not enough.
 */
class InstitutionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_create_an_institution(): void
    {
        $this->actingAs($this->staffUser())
            ->post(route('institution.store'), $this->payload(['name' => 'Rogue Institution']))
            ->assertForbidden();

        $this->assertDatabaseMissing('institutions', ['name' => 'Rogue Institution']);
    }

    public function test_staff_cannot_update_an_institution(): void
    {
        $institution = Institution::factory()->create(['name' => 'Audit Service']);

        $this->actingAs($this->staffUser())
            ->patch(
                route('institution.update', ['institution' => $institution->id]),
                $this->payload(['id' => $institution->id, 'name' => 'Renamed'])
            )
            ->assertForbidden();

        $this->assertSame('Audit Service', $institution->fresh()->name);
    }

    public function test_staff_cannot_delete_an_institution(): void
    {
        $institution = Institution::factory()->create();

        $this->actingAs($this->staffUser())
            ->delete(route('institution.delete', ['institution' => $institution->id]))
            ->assertForbidden();

        $this->assertNotSoftDeleted('institutions', ['id' => $institution->id]);
    }

    public function test_update_staff_does_not_stand_in_for_the_institution_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-user');
        $institution = Institution::factory()->create(['name' => 'Audit Service']);

        $this->assertTrue($admin->can('update staff'));

        $this->actingAs($admin)
            ->post(route('institution.store'), $this->payload(['name' => 'Rogue Institution']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(
                route('institution.update', ['institution' => $institution->id]),
                $this->payload(['id' => $institution->id, 'name' => 'Renamed'])
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('institution.delete', ['institution' => $institution->id]))
            ->assertForbidden();

        $this->assertDatabaseMissing('institutions', ['name' => 'Rogue Institution']);
        $this->assertSame('Audit Service', $institution->fresh()->name);
        $this->assertNotSoftDeleted('institutions', ['id' => $institution->id]);
    }

    public function test_user_with_create_institution_can_create_an_institution(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create institution');

        $this->actingAs($user)
            ->post(route('institution.store'), $this->payload(['name' => 'Internal Audit Agency']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('institutions', ['name' => 'Internal Audit Agency']);
    }

    public function test_user_with_edit_institution_can_update_an_institution(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit institution');
        $institution = Institution::factory()->create(['name' => 'Audit Service']);

        $this->actingAs($user)
            ->patch(
                route('institution.update', ['institution' => $institution->id]),
                $this->payload(['id' => $institution->id, 'name' => 'Ghana Audit Service'])
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Ghana Audit Service', $institution->fresh()->name);
    }

    public function test_user_with_delete_institution_can_delete_an_institution(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('delete institution');
        $institution = Institution::factory()->create();

        $this->actingAs($user)
            ->delete(route('institution.delete', ['institution' => $institution->id]))
            ->assertRedirect(route('institution.index'));

        $this->assertSoftDeleted('institutions', ['id' => $institution->id]);
    }

    /**
     * A signed-in member of staff holding only the staff role.
     */
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Audit Service',
            'abbreviation' => 'GAS',
            'start_date' => '2001-01-01',
        ], $overrides);
    }
}
