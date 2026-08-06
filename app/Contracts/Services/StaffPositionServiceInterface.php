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

    /**
     * Grant the roles mapped to the assignment's position to its holder.
     *
     * @return array{0: array<int, string>, 1: string|null} granted role names, warning
     */
    public function syncGrantsForAssignment(PositionStaff $assignment): array;

    /**
     * Withdraw the roles an assignment conferred, leaving hand-assigned roles
     * and roles still conferred by another assignment in place.
     *
     * @param  array<int, int>|null  $onlyRoleIds
     */
    public function revokeGrantsFor(PositionStaff $assignment, ?array $onlyRoleIds = null): void;
}
