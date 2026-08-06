<?php

namespace App\Services\Staff;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\DataTransferObjects\PositionAssignmentResult;
use App\Models\InstitutionPerson;
use App\Models\PositionRoleGrant;
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

            // Revocation runs before this, so a role shared by the outgoing and
            // incoming positions is re-granted here rather than looking like one
            // the holder already had.
            [$granted, $warning] = $this->syncGrantsForAssignment($assignment);

            return new PositionAssignmentResult($assignment, $granted, $warning);
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

            $assignment->refresh();

            // The position or the effective dates may both have moved, so the
            // grants are rebuilt from scratch rather than diffed.
            $this->revokeGrantsFor($assignment);
            [$granted, $warning] = $this->syncGrantsForAssignment($assignment);

            return new PositionAssignmentResult($assignment, $granted, $warning);
        });
    }

    public function end(PositionStaff $assignment, ?Carbon $endDate = null): void
    {
        DB::transaction(function () use ($assignment, $endDate) {
            $assignment->update(['end_date' => $endDate ?? Carbon::now()]);
            $this->revokeGrantsFor($assignment);
        });
    }

    public function delete(PositionStaff $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $this->revokeGrantsFor($assignment);
            $assignment->delete();
        });
    }

    /**
     * Grant the roles mapped to an assignment's position to the holder's user
     * account, recording where each role came from.
     *
     * @return array{0: array<int, string>, 1: string|null} granted role names, warning
     */
    public function syncGrantsForAssignment(PositionStaff $assignment): array
    {
        if (! $assignment->isInEffect()) {
            return [[], null];
        }

        $roles = $assignment->position?->roles ?? collect();

        if ($roles->isEmpty()) {
            return [[], null];
        }

        $user = $assignment->staff?->person?->user;

        if (! $user) {
            return [[], sprintf(
                '%s is not linked to a user account, so the role(s) mapped to this position were not granted.',
                $assignment->staff?->person?->full_name ?? 'This staff member'
            )];
        }

        $granted = [];

        foreach ($roles as $role) {
            $alreadyHeld = $user->hasRole($role);

            $viaOtherAssignment = PositionRoleGrant::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('position_staff_id', '!=', $assignment->id)
                ->exists();

            PositionRoleGrant::updateOrCreate(
                [
                    'position_staff_id' => $assignment->id,
                    'role_id' => $role->id,
                ],
                [
                    'user_id' => $user->id,
                    'position_id' => $assignment->position_id,
                    // A role the holder already had for reasons of its own must
                    // survive this assignment ending.
                    'was_preexisting' => $alreadyHeld && ! $viaOtherAssignment,
                    'granted_by' => auth()->id(),
                    'granted_at' => Carbon::now(),
                    'revoked_at' => null,
                ]
            );

            if (! $alreadyHeld) {
                $user->assignRole($role);
                $granted[] = $role->name;
            }
        }

        return [$granted, null];
    }

    /**
     * Withdraw the roles an assignment conferred.
     *
     * A role is only taken off the user when this assignment is the reason
     * they had it and no other assignment still confers it.
     *
     * @param  array<int, int>|null  $onlyRoleIds  Limit to these roles
     */
    public function revokeGrantsFor(PositionStaff $assignment, ?array $onlyRoleIds = null): void
    {
        $grants = $assignment->roleGrants()
            ->whereNull('revoked_at')
            ->when($onlyRoleIds !== null, fn ($query) => $query->whereIn('role_id', $onlyRoleIds))
            ->get();

        foreach ($grants as $grant) {
            $grant->update(['revoked_at' => Carbon::now()]);

            if ($grant->was_preexisting) {
                continue;
            }

            $stillGranted = PositionRoleGrant::query()
                ->active()
                ->where('user_id', $grant->user_id)
                ->where('role_id', $grant->role_id)
                ->whereKeyNot($grant->getKey())
                ->exists();

            if (! $stillGranted) {
                $grant->user?->removeRole($grant->role);
            }
        }
    }

    /**
     * Close every open assignment the staff member currently holds.
     */
    protected function closeOpenAssignmentsFor(InstitutionPerson $staff, Carbon $closedOn): void
    {
        $staff->positionAssignments()
            ->whereNull('end_date')
            ->get()
            ->each(function (PositionStaff $open) use ($closedOn) {
                $open->update(['end_date' => $closedOn]);
                $this->revokeGrantsFor($open);
            });
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
            ->each(function (PositionStaff $held) use ($closedOn) {
                $held->update(['end_date' => $closedOn]);
                $this->revokeGrantsFor($held);
            });
    }
}
