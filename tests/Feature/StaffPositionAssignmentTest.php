<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPositionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected InstitutionPerson $staff;

    protected Position $currentPosition;

    protected Position $newPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();

        $this->currentPosition = Position::factory()->create(['name' => 'Regional Auditor']);
        $this->newPosition = Position::factory()->create(['name' => 'Director of Audit']);

        $this->staff = $this->makeStaff('STF001');

        $this->staff->positionAssignments()->create([
            'position_id' => $this->currentPosition->id,
            'start_date' => now()->subYears(2),
        ]);

        $this->user = User::factory()->create([
            'person_id' => $this->makeStaff('ADMIN001')->person_id,
            'password_change_at' => now(),
        ]);
        $this->user->givePermissionTo([
            'view all staff',
            'view all staff positions',
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

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // The staff show page is scoped to active staff.
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $this->institution->id,
        ]);

        return $staff;
    }

    private function openAssignment(InstitutionPerson $staff): ?PositionStaff
    {
        return $staff->positionAssignments()->whereNull('end_date')->first();
    }

    // ===================
    // AUTHORIZATION
    // ===================

    public function test_assign_position_requires_authentication(): void
    {
        $this->post(route('staff.position.store', ['staff' => $this->staff->id]), [
            'position_id' => $this->newPosition->id,
        ])->assertRedirect('/login');
    }

    public function test_assign_position_requires_permission(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);

        $this->actingAs($user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
            ])
            ->assertForbidden();
    }

    public function test_delete_position_requires_permission(): void
    {
        $assignment = $this->openAssignment($this->staff);
        $user = User::factory()->create(['password_change_at' => now()]);

        $this->actingAs($user)
            ->delete(route('staff.position.delete', [
                'staff' => $this->staff->id,
                'staffPosition' => $assignment->id,
            ]))
            ->assertForbidden();
    }

    // ===================
    // ASSIGNMENT
    // ===================

    public function test_can_assign_a_position(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('position_staff', [
            'staff_id' => $this->staff->id,
            'position_id' => $this->newPosition->id,
            'end_date' => null,
        ]);
    }

    /**
     * Regression: updatePosition used to syncWithPivotValues, which detached
     * every other row and wiped the staff member's history.
     */
    public function test_assigning_a_position_closes_the_previous_one_and_keeps_it(): void
    {
        $startDate = now();

        $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
                'start_date' => $startDate->format('Y-m-d'),
            ]);

        $previous = PositionStaff::where('staff_id', $this->staff->id)
            ->where('position_id', $this->currentPosition->id)
            ->first();

        $this->assertNotNull($previous, 'The previous assignment must be kept as history.');
        $this->assertSame(
            $startDate->copy()->subDay()->format('Y-m-d'),
            $previous->end_date->format('Y-m-d')
        );

        $this->assertSame(2, PositionStaff::where('staff_id', $this->staff->id)->count());
        $this->assertSame($this->newPosition->id, $this->openAssignment($this->staff)->position_id);
    }

    public function test_assigning_a_position_closes_the_previous_occupant(): void
    {
        $otherStaff = $this->makeStaff('STF002');
        $otherStaff->positionAssignments()->create([
            'position_id' => $this->newPosition->id,
            'start_date' => now()->subYear(),
        ]);

        $startDate = now();

        $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
                'start_date' => $startDate->format('Y-m-d'),
            ]);

        $previousHolder = PositionStaff::where('staff_id', $otherStaff->id)->first();

        $this->assertNotNull($previousHolder, 'The previous occupant keeps their history row.');
        $this->assertSame(
            $startDate->copy()->subDay()->format('Y-m-d'),
            $previousHolder->end_date->format('Y-m-d')
        );
        $this->assertNull($this->openAssignment($otherStaff));
    }

    /**
     * A user holding only the read and create permissions must be able to load
     * the staff page and change the position from it.
     */
    public function test_user_with_position_permissions_can_change_position_from_staff_page(): void
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->givePermissionTo([
            'view staff',
            'view all staff',
            'view all staff positions',
            'create staff position',
        ]);

        $this->actingAs($user)
            ->get(route('staff.show', ['staff' => $this->staff->id]))
            ->assertSuccessful();

        $this->actingAs($user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
                'start_date' => now()->format('Y-m-d'),
            ])
            ->assertSessionHas('success');
    }

    // ===================
    // VALIDATION
    // ===================

    public function test_position_id_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [])
            ->assertSessionHasErrors('position_id');
    }

    public function test_position_must_exist(): void
    {
        $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => 99999,
            ])
            ->assertSessionHasErrors('position_id');
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('staff.position.store', ['staff' => $this->staff->id]), [
                'position_id' => $this->newPosition->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('end_date');
    }

    // ===================
    // UPDATE
    // ===================

    public function test_updating_an_assignment_leaves_other_rows_untouched(): void
    {
        $closed = $this->staff->positionAssignments()->create([
            'position_id' => $this->newPosition->id,
            'start_date' => now()->subYears(5),
            'end_date' => now()->subYears(4),
        ]);
        $open = $this->openAssignment($this->staff);

        $this->actingAs($this->user)
            ->patch(route('staff.position.update', [
                'staff' => $this->staff->id,
                'staffPosition' => $open->id,
            ]), [
                'position_id' => $this->newPosition->id,
                'start_date' => now()->subYear()->format('Y-m-d'),
            ])
            ->assertSessionHas('success');

        $untouched = $closed->fresh();
        $this->assertSame(
            $closed->end_date->format('Y-m-d'),
            $untouched->end_date->format('Y-m-d')
        );
        $this->assertSame($closed->position_id, $untouched->position_id);
        $this->assertSame(2, PositionStaff::where('staff_id', $this->staff->id)->count());
    }

    public function test_update_is_rejected_when_it_would_leave_two_open_assignments(): void
    {
        $closed = $this->staff->positionAssignments()->create([
            'position_id' => $this->newPosition->id,
            'start_date' => now()->subYears(5),
            'end_date' => now()->subYears(4),
        ]);

        $this->actingAs($this->user)
            ->patch(route('staff.position.update', [
                'staff' => $this->staff->id,
                'staffPosition' => $closed->id,
            ]), [
                'position_id' => $this->newPosition->id,
                'start_date' => now()->subYears(5)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_cannot_update_an_assignment_belonging_to_another_staff_member(): void
    {
        $otherStaff = $this->makeStaff('STF003');
        $assignment = $otherStaff->positionAssignments()->create([
            'position_id' => $this->newPosition->id,
            'start_date' => now()->subYear(),
        ]);

        $this->actingAs($this->user)
            ->patch(route('staff.position.update', [
                'staff' => $this->staff->id,
                'staffPosition' => $assignment->id,
            ]), [
                'position_id' => $this->newPosition->id,
            ])
            ->assertNotFound();
    }

    // ===================
    // DELETE
    // ===================

    /**
     * Regression: the delete route did not declare the assignment parameter, so
     * the controller's required position_id never arrived and the request
     * failed validation instead of deleting.
     */
    public function test_can_delete_an_assignment(): void
    {
        $assignment = $this->openAssignment($this->staff);

        $response = $this->actingAs($this->user)
            ->delete(route('staff.position.delete', [
                'staff' => $this->staff->id,
                'staffPosition' => $assignment->id,
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('position_staff', ['id' => $assignment->id]);
        $this->assertCount(0, $this->staff->fresh()->positions);
    }

    public function test_cannot_delete_an_assignment_belonging_to_another_staff_member(): void
    {
        $otherStaff = $this->makeStaff('STF004');
        $assignment = $otherStaff->positionAssignments()->create([
            'position_id' => $this->newPosition->id,
            'start_date' => now()->subYear(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('staff.position.delete', [
                'staff' => $this->staff->id,
                'staffPosition' => $assignment->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('position_staff', [
            'id' => $assignment->id,
            'deleted_at' => null,
        ]);
    }
}
