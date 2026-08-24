<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionRoleGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionRoleMappingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Institution $institution;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->position = Position::factory()->create(['name' => 'Director of Audit']);

        $this->admin = User::factory()->create(['password_change_at' => now()]);
        $this->admin->givePermissionTo(['view position roles', 'manage position roles']);
    }

    private function makeStaff(string $staffNumber): InstitutionPerson
    {
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => $staffNumber,
            'hire_date' => now()->subYears(5),
        ]);

        return InstitutionPerson::where('person_id', $person->id)->first();
    }

    /**
     * @return array{0: InstitutionPerson, 1: User}
     */
    private function makeLinkedStaff(string $staffNumber): array
    {
        $staff = $this->makeStaff($staffNumber);
        $user = User::factory()->create([
            'person_id' => $staff->person_id,
            'password_change_at' => now(),
        ]);

        return [$staff, $user];
    }

    private function sync(array $roles)
    {
        return $this->actingAs($this->admin)
            ->put(route('position.roles.sync', ['position' => $this->position->id]), [
                'roles' => $roles,
            ]);
    }

    // ===================
    // AUTHORIZATION
    // ===================

    public function test_sync_requires_permission(): void
    {
        $this->actingAs(User::factory()->create(['password_change_at' => now()]))
            ->put(route('position.roles.sync', ['position' => $this->position->id]), ['roles' => []])
            ->assertForbidden();
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs(User::factory()->create(['password_change_at' => now()]))
            ->get(route('position.roles.index', ['position' => $this->position->id]))
            ->assertForbidden();
    }

    public function test_index_returns_the_mapping_and_the_assignable_roles(): void
    {
        $this->position->roles()->sync(Role::where('name', 'hr-user')->pluck('id')->all());

        $response = $this->actingAs($this->admin)
            ->getJson(route('position.roles.index', ['position' => $this->position->id]));

        $response->assertOk();
        $response->assertJsonPath('roles.0', 'hr-user');

        $available = collect($response->json('available'))->pluck('value');
        $this->assertFalse($available->contains('staff'));
        $this->assertFalse($available->contains('super-administrator'));
        $this->assertTrue($available->contains('hr-user'));
    }

    // ===================
    // MAPPING
    // ===================

    public function test_can_map_roles_to_a_position(): void
    {
        $this->sync(['hr-user', 'personel-user'])->assertSessionHas('success');

        $this->assertSame(
            ['hr-user', 'personel-user'],
            $this->position->fresh()->roles->pluck('name')->sort()->values()->all()
        );
    }

    public function test_syncing_replaces_the_previous_mapping(): void
    {
        $this->sync(['hr-user']);
        $this->sync(['personel-user']);

        $this->assertSame(['personel-user'], $this->position->fresh()->roles->pluck('name')->all());
    }

    public function test_an_empty_array_clears_the_mapping(): void
    {
        $this->sync(['hr-user']);
        $this->sync([])->assertSessionHas('success');

        $this->assertCount(0, $this->position->fresh()->roles);
    }

    // ===================
    // VALIDATION
    // ===================

    public function test_the_staff_role_cannot_be_granted_through_a_position(): void
    {
        $this->sync(['staff'])->assertSessionHasErrors('roles.0');

        $this->assertCount(0, $this->position->fresh()->roles);
    }

    public function test_the_super_administrator_role_cannot_be_granted_through_a_position(): void
    {
        $this->sync(['super-administrator'])->assertSessionHasErrors('roles.0');
    }

    public function test_unknown_roles_are_rejected(): void
    {
        $this->sync(['not-a-role'])->assertSessionHasErrors('roles.0');
    }

    public function test_roles_must_be_present(): void
    {
        $this->actingAs($this->admin)
            ->put(route('position.roles.sync', ['position' => $this->position->id]), [])
            ->assertSessionHasErrors('roles');
    }

    // ===================
    // RECONCILIATION
    // ===================

    public function test_adding_a_role_grants_it_to_the_current_holder(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $staff->positionAssignments()->create([
            'position_id' => $this->position->id,
            'start_date' => now()->subYear(),
        ]);

        $this->sync(['hr-user']);

        $this->assertTrue($user->fresh()->hasRole('hr-user'));
        $this->assertDatabaseHas('position_role_grants', [
            'user_id' => $user->id,
            'position_id' => $this->position->id,
            'revoked_at' => null,
        ]);
    }

    public function test_removing_a_role_revokes_it_from_the_current_holder(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $staff->positionAssignments()->create([
            'position_id' => $this->position->id,
            'start_date' => now()->subYear(),
        ]);

        $this->sync(['hr-user']);
        $this->assertTrue($user->fresh()->hasRole('hr-user'));

        $this->sync([]);

        $this->assertFalse($user->fresh()->hasRole('hr-user'));
        $this->assertNotNull(PositionRoleGrant::first()->revoked_at);
    }

    public function test_reconciliation_does_not_strip_a_manually_assigned_role(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $user->assignRole('hr-user');
        $staff->positionAssignments()->create([
            'position_id' => $this->position->id,
            'start_date' => now()->subYear(),
        ]);

        $this->sync(['hr-user']);
        $this->sync([]);

        $this->assertTrue(
            $user->fresh()->hasRole('hr-user'),
            'Removing the mapping must not take away a role the administrator assigned by hand.'
        );
    }

    public function test_past_holders_are_not_reconciled(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $staff->positionAssignments()->create([
            'position_id' => $this->position->id,
            'start_date' => now()->subYears(3),
            'end_date' => now()->subYears(2),
        ]);

        $this->sync(['hr-user']);

        $this->assertFalse($user->fresh()->hasRole('hr-user'));
        $this->assertSame(0, PositionRoleGrant::count());
    }

    public function test_a_holder_without_a_user_account_produces_a_warning_not_an_error(): void
    {
        $staff = $this->makeStaff('STF404');
        $staff->positionAssignments()->create([
            'position_id' => $this->position->id,
            'start_date' => now()->subYear(),
        ]);

        $response = $this->sync(['hr-user']);

        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');
        $this->assertSame(['hr-user'], $this->position->fresh()->roles->pluck('name')->all());
    }
}
