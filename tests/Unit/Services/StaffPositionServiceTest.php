<?php

namespace Tests\Unit\Services;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Position;
use App\Models\PositionStaff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPositionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StaffPositionServiceInterface $service;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StaffPositionServiceInterface::class);
        $this->institution = Institution::factory()->create();
    }

    private function staff(): InstitutionPerson
    {
        return InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_assign_creates_an_open_assignment(): void
    {
        $staff = $this->staff();
        $position = Position::factory()->create();

        $result = $this->service->assign($staff, $position->id, ['start_date' => '2024-01-01']);

        $this->assertInstanceOf(PositionStaff::class, $result->assignment);
        $this->assertSame($position->id, $result->assignment->position_id);
        $this->assertSame($staff->id, $result->assignment->staff_id);
        $this->assertNull($result->assignment->end_date);
        $this->assertSame('2024-01-01', $result->assignment->start_date->format('Y-m-d'));
    }

    public function test_assign_defaults_the_start_date_to_today(): void
    {
        $staff = $this->staff();
        $position = Position::factory()->create();

        $result = $this->service->assign($staff, $position->id, []);

        $this->assertSame(Carbon::now()->format('Y-m-d'), $result->assignment->start_date->format('Y-m-d'));
    }

    public function test_assign_closes_the_staff_members_own_open_assignment(): void
    {
        $staff = $this->staff();
        $previous = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->assign($staff, Position::factory()->create()->id, ['start_date' => '2024-01-01']);

        $this->assertSame('2023-12-31', $previous->fresh()->end_date->format('Y-m-d'));
        $this->assertSame(2, $staff->positionAssignments()->count());
    }

    public function test_assign_closes_the_previous_occupant_of_the_position(): void
    {
        $position = Position::factory()->create();
        $incumbent = $this->staff();
        $held = $incumbent->positionAssignments()->create([
            'position_id' => $position->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->assign($this->staff(), $position->id, ['start_date' => '2024-01-01']);

        $this->assertSame('2023-12-31', $held->fresh()->end_date->format('Y-m-d'));
    }

    public function test_assign_does_not_close_an_unrelated_staff_members_assignment(): void
    {
        $other = $this->staff();
        $untouched = $other->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->assign($this->staff(), Position::factory()->create()->id, ['start_date' => '2024-01-01']);

        $this->assertNull($untouched->fresh()->end_date);
    }

    public function test_update_touches_only_the_targeted_assignment(): void
    {
        $staff = $this->staff();
        $newPosition = Position::factory()->create();

        $historical = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2020-01-01',
            'end_date' => '2021-01-01',
        ]);
        $open = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->update($open, [
            'position_id' => $newPosition->id,
            'start_date' => '2023-06-01',
        ]);

        $this->assertSame($newPosition->id, $open->fresh()->position_id);
        $this->assertSame('2023-06-01', $open->fresh()->start_date->format('Y-m-d'));

        $this->assertSame('2021-01-01', $historical->fresh()->end_date->format('Y-m-d'));
        $this->assertSame(2, $staff->positionAssignments()->count());
    }

    /**
     * A backdated assignment must not close the previous row before the day it
     * started.
     */
    public function test_assign_never_ends_a_previous_row_before_it_began(): void
    {
        $staff = $this->staff();
        $previous = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2024-06-01',
        ]);

        $this->service->assign($staff, Position::factory()->create()->id, ['start_date' => '2024-01-01']);

        $previous->refresh();
        $this->assertTrue(
            $previous->end_date->gte($previous->start_date),
            'An assignment may not end before it starts.'
        );
        $this->assertSame('2024-06-01', $previous->end_date->format('Y-m-d'));
    }

    /**
     * `update()` must distinguish "not mentioned" from "cleared"; using isset()
     * silently wiped dates the caller never sent.
     */
    public function test_update_leaves_dates_the_caller_did_not_mention(): void
    {
        $staff = $this->staff();
        $newPosition = Position::factory()->create();

        $assignment = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $this->service->update($assignment, ['position_id' => $newPosition->id]);

        $assignment->refresh();
        $this->assertSame($newPosition->id, $assignment->position_id);
        $this->assertSame('2024-01-01', $assignment->start_date->format('Y-m-d'));
        $this->assertSame('2024-12-31', $assignment->end_date->format('Y-m-d'));
    }

    public function test_update_clears_a_date_that_is_explicitly_null(): void
    {
        $staff = $this->staff();
        $assignment = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $this->service->update($assignment, ['end_date' => null]);

        $this->assertNull($assignment->fresh()->end_date);
    }

    public function test_end_closes_the_assignment_without_removing_it(): void
    {
        $staff = $this->staff();
        $assignment = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->end($assignment, Carbon::parse('2024-05-01'));

        $this->assertSame('2024-05-01', $assignment->fresh()->end_date->format('Y-m-d'));
        $this->assertDatabaseHas('position_staff', ['id' => $assignment->id, 'deleted_at' => null]);
    }

    public function test_delete_soft_deletes_the_assignment(): void
    {
        $staff = $this->staff();
        $assignment = $staff->positionAssignments()->create([
            'position_id' => Position::factory()->create()->id,
            'start_date' => '2023-01-01',
        ]);

        $this->service->delete($assignment);

        $this->assertSoftDeleted('position_staff', ['id' => $assignment->id]);
        $this->assertCount(0, $staff->fresh()->positions);
    }
}
