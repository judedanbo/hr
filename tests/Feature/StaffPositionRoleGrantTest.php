<?php

namespace Tests\Feature;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionRoleGrant;
use App\Models\PositionStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffPositionRoleGrantTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Institution $institution;

    protected StaffPositionServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StaffPositionServiceInterface::class);
        $this->institution = Institution::factory()->create();

        $this->admin = User::factory()->create([
            'person_id' => $this->makeStaff('ADMIN001')->person_id,
            'password_change_at' => now(),
        ]);
        $this->admin->givePermissionTo([
            'create staff position',
            'update staff position',
            'delete staff position',
        ]);
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
     * A staff member with a linked user account.
     *
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

    private function positionGranting(string ...$roleNames): Position
    {
        $position = Position::factory()->create();
        $position->roles()->sync(Role::whereIn('name', $roleNames)->pluck('id')->all());

        return $position;
    }

    // ===================
    // GRANTING
    // ===================

    public function test_assigning_a_position_grants_its_mapped_roles(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $position = $this->positionGranting('hr-user');

        $result = $this->service->assign($staff, $position->id, ['start_date' => now()->format('Y-m-d')]);

        $this->assertTrue($user->fresh()->hasRole('hr-user'));
        $this->assertSame(['hr-user'], $result->grantedRoles);
        $this->assertNull($result->warning);

        $this->assertDatabaseHas('position_role_grants', [
            'user_id' => $user->id,
            'position_staff_id' => $result->assignment->id,
            'was_preexisting' => false,
            'revoked_at' => null,
        ]);
    }

    public function test_a_position_can_grant_several_roles(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $position = $this->positionGranting('hr-user', 'personel-user');

        $this->service->assign($staff, $position->id, []);

        $this->assertTrue($user->fresh()->hasRole('hr-user'));
        $this->assertTrue($user->fresh()->hasRole('personel-user'));
        $this->assertSame(2, PositionRoleGrant::count());
    }

    public function test_a_position_with_no_mapped_roles_grants_nothing(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');

        $this->service->assign($staff, Position::factory()->create()->id, []);

        $this->assertEmpty($user->fresh()->getRoleNames());
        $this->assertSame(0, PositionRoleGrant::count());
    }

    // ===================
    // REVOKING
    // ===================

    public function test_ending_an_assignment_revokes_the_role(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $position = $this->positionGranting('hr-user');

        $assignment = $this->service->assign($staff, $position->id, [])->assignment;
        $this->assertTrue($user->fresh()->hasRole('hr-user'));

        $this->service->end($assignment);

        $this->assertFalse($user->fresh()->hasRole('hr-user'));
        $this->assertNotNull(PositionRoleGrant::first()->revoked_at);
    }

    public function test_deleting_an_assignment_revokes_the_role(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $position = $this->positionGranting('hr-user');

        $assignment = $this->service->assign($staff, $position->id, [])->assignment;
        $this->service->delete($assignment);

        $this->assertFalse($user->fresh()->hasRole('hr-user'));
        $this->assertNotNull(PositionRoleGrant::first()->revoked_at);
    }

    /**
     * The core provenance guarantee: a role an administrator assigned by hand
     * is never taken away by a position ending.
     */
    public function test_a_manually_assigned_role_is_not_stripped(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $user->assignRole('hr-user');

        $position = $this->positionGranting('hr-user');
        $assignment = $this->service->assign($staff, $position->id, [])->assignment;

        $this->assertTrue(PositionRoleGrant::first()->was_preexisting);

        $this->service->end($assignment);

        $this->assertTrue(
            $user->fresh()->hasRole('hr-user'),
            'A hand-assigned role must survive the position that happened to map it.'
        );
    }

    public function test_a_role_held_through_two_positions_survives_until_both_end(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');

        $first = $this->service->assign($staff, $this->positionGranting('hr-user')->id, [
            'start_date' => now()->subYear()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
        ])->assignment;

        // Assigning again would close the first, so the second assignment is
        // created directly to model concurrent sources of the same role.
        $second = $staff->positionAssignments()->create([
            'position_id' => $this->positionGranting('hr-user')->id,
            'start_date' => now()->subMonth(),
        ]);
        $this->service->syncGrantsForAssignment($second);

        $this->assertSame(2, PositionRoleGrant::count());

        $this->service->end($first);
        $this->assertTrue($user->fresh()->hasRole('hr-user'), 'The second position still confers the role.');

        $this->service->end($second);
        $this->assertFalse($user->fresh()->hasRole('hr-user'));
    }

    public function test_reassignment_swaps_the_roles(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');

        $this->service->assign($staff, $this->positionGranting('hr-user')->id, [
            'start_date' => now()->subYear()->format('Y-m-d'),
        ]);
        $this->assertTrue($user->fresh()->hasRole('hr-user'));

        $this->service->assign($staff, $this->positionGranting('personel-user')->id, [
            'start_date' => now()->format('Y-m-d'),
        ]);

        $this->assertFalse($user->fresh()->hasRole('hr-user'));
        $this->assertTrue($user->fresh()->hasRole('personel-user'));
    }

    /**
     * Revocation runs before granting, so a role common to both positions is
     * retained and re-attributed to the incoming assignment.
     */
    public function test_reassignment_between_positions_sharing_a_role_keeps_it(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');

        $this->service->assign($staff, $this->positionGranting('hr-user')->id, [
            'start_date' => now()->subYear()->format('Y-m-d'),
        ]);

        $incoming = $this->service->assign($staff, $this->positionGranting('hr-user')->id, [
            'start_date' => now()->format('Y-m-d'),
        ])->assignment;

        $this->assertTrue($user->fresh()->hasRole('hr-user'));

        $grant = PositionRoleGrant::where('position_staff_id', $incoming->id)->first();
        $this->assertNotNull($grant);
        $this->assertFalse(
            $grant->was_preexisting,
            'The role came from the outgoing position, not from a manual assignment.'
        );
    }

    // ===================
    // NO USER ACCOUNT
    // ===================

    public function test_assignment_succeeds_with_a_warning_when_there_is_no_user_account(): void
    {
        $staff = $this->makeStaff('STF404');
        $position = $this->positionGranting('hr-user');

        $response = $this->actingAs($this->admin)
            ->post(route('staff.position.store', ['staff' => $staff->id]), [
                'position_id' => $position->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('position_staff', [
            'staff_id' => $staff->id,
            'position_id' => $position->id,
        ]);
        $this->assertSame(0, PositionRoleGrant::count());
    }

    public function test_no_warning_when_the_position_maps_no_roles(): void
    {
        $staff = $this->makeStaff('STF405');

        $this->actingAs($this->admin)
            ->post(route('staff.position.store', ['staff' => $staff->id]), [
                'position_id' => Position::factory()->create()->id,
            ])
            ->assertSessionMissing('warning');
    }

    // ===================
    // HISTORICAL ROWS
    // ===================

    /**
     * Ending an assignment revokes its roles immediately, so a mapping change
     * made the same day must not hand them straight back.
     */
    public function test_a_holder_whose_assignment_just_ended_is_not_regranted(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');
        $position = $this->positionGranting('hr-user');

        $assignment = $this->service->assign($staff, $position->id, [])->assignment;
        $this->service->end($assignment);
        $this->assertFalse($user->fresh()->hasRole('hr-user'));

        $this->service->syncPositionRoles($position, ['hr-user']);

        $this->assertFalse(
            $user->fresh()->hasRole('hr-user'),
            'An assignment that has ended must not be reconciled as a current holder.'
        );
    }

    public function test_an_assignment_that_already_ended_grants_nothing(): void
    {
        [$staff, $user] = $this->makeLinkedStaff('STF001');

        $historical = PositionStaff::create([
            'staff_id' => $staff->id,
            'position_id' => $this->positionGranting('hr-user')->id,
            'start_date' => now()->subYears(3),
            'end_date' => now()->subYears(2),
        ]);

        [$granted, $warning] = $this->service->syncGrantsForAssignment($historical);

        $this->assertSame([], $granted);
        $this->assertNull($warning);
        $this->assertFalse($user->fresh()->hasRole('hr-user'));
    }
}
