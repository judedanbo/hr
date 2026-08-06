<?php

namespace App\Contracts\Services;

use App\DataTransferObjects\PositionAssignmentResult;
use App\Models\InstitutionPerson;
use App\Models\PositionStaff;
use Carbon\Carbon;

interface StaffPositionServiceInterface
{
    /**
     * Assign a position to a staff member.
     *
     * Closes the staff member's own open assignment and any other staff
     * member currently holding the position, then opens a new one. Previous
     * rows are kept as history.
     *
     * @param  array{start_date?: string|null, end_date?: string|null}  $data
     */
    public function assign(InstitutionPerson $staff, int $positionId, array $data): PositionAssignmentResult;

    /**
     * Update a single assignment row, leaving every other row untouched.
     *
     * @param  array{position_id?: int, start_date?: string|null, end_date?: string|null}  $data
     */
    public function update(PositionStaff $assignment, array $data): PositionAssignmentResult;

    /**
     * Close an open assignment without removing it from the history.
     */
    public function end(PositionStaff $assignment, ?Carbon $endDate = null): void;

    /**
     * Soft-delete an assignment row.
     */
    public function delete(PositionStaff $assignment): void;
}
