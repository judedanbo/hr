<?php

namespace App\Services\Staff;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\DataTransferObjects\PositionAssignmentResult;
use App\Models\InstitutionPerson;
use App\Models\PositionStaff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffPositionService implements StaffPositionServiceInterface
{
    /**
     * Assign a position to a staff member.
     *
     * A staff member holds one position at a time, so the incoming assignment
     * closes their current one. A position is held by one person at a time, so
     * it also closes whoever held it before. Both keep their history rows.
     */
    public function assign(InstitutionPerson $staff, int $positionId, array $data): PositionAssignmentResult
    {
        return DB::transaction(function () use ($staff, $positionId, $data) {
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::now();
            $closedOn = $startDate->copy()->subDay();

            $this->closeOpenAssignmentsFor($staff, $closedOn);
            $this->closeOtherOccupantsOf($positionId, $staff, $closedOn);

            $assignment = PositionStaff::create([
                'staff_id' => $staff->id,
                'position_id' => $positionId,
                'start_date' => $startDate,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            ]);

            return new PositionAssignmentResult($assignment);
        });
    }

    /**
     * Update a single assignment row.
     *
     * Deliberately narrow: sibling rows are never closed or detached here, so
     * correcting a historical record cannot rewrite the rest of the history.
     */
    public function update(PositionStaff $assignment, array $data): PositionAssignmentResult
    {
        return DB::transaction(function () use ($assignment, $data) {
            $assignment->update([
                'position_id' => $data['position_id'] ?? $assignment->position_id,
                'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            ]);

            return new PositionAssignmentResult($assignment->refresh());
        });
    }

    public function end(PositionStaff $assignment, ?Carbon $endDate = null): void
    {
        DB::transaction(function () use ($assignment, $endDate) {
            $assignment->update(['end_date' => $endDate ?? Carbon::now()]);
        });
    }

    public function delete(PositionStaff $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $assignment->delete();
        });
    }

    /**
     * Close every open assignment the staff member currently holds.
     */
    protected function closeOpenAssignmentsFor(InstitutionPerson $staff, Carbon $closedOn): void
    {
        $staff->positionAssignments()
            ->whereNull('end_date')
            ->get()
            ->each(fn (PositionStaff $open) => $open->update(['end_date' => $closedOn]));
    }

    /**
     * Close anyone else still holding the position.
     */
    protected function closeOtherOccupantsOf(int $positionId, InstitutionPerson $staff, Carbon $closedOn): void
    {
        PositionStaff::query()
            ->where('position_id', $positionId)
            ->whereNull('end_date')
            ->where('staff_id', '!=', $staff->id)
            ->get()
            ->each(fn (PositionStaff $held) => $held->update(['end_date' => $closedOn]));
    }
}
