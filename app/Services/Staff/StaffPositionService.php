<?php

namespace App\Services\Staff;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\DataTransferObjects\PositionAssignmentResult;
use App\Models\InstitutionPerson;
use App\Models\Position;
use App\Models\PositionRoleGrant;
use App\Models\PositionStaff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

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
            // array_key_exists, not isset: a key that is present and null means
            // "clear this date", whereas an absent key means "leave it alone".
            // Using isset() here silently wiped dates the caller never mentioned.
            $changes = [];

            if (array_key_exists('position_id', $data)) {
                $changes['position_id'] = $data['position_id'];
            }

            foreach (['start_date', 'end_date'] as $field) {
                if (array_key_exists($field, $data)) {
                    $changes[$field] = $data[$field] === null ? null : Carbon::parse($data[$field]);
                }
            }

            $assignment->update($changes);

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
     * Replace a position's role mapping and bring current holders into line.
     *
     * Holders are reconciled immediately rather than on their next
     * reassignment. Leaving a sitting holder without the newly mapped role
     * invites an administrator to assign it by hand, and a hand-assigned role
     * can never be revoked automatically — so deferring would corrupt the
     * provenance model rather than merely postpone the grant. Closed
     * assignments are left alone; their grants were revoked when they ended.
     *
     * @param  array<int, string>  $roleNames
     * @return array{reconciled: int, warnings: array<int, string>}
     */
    public function syncPositionRoles(Position $position, array $roleNames): array
    {
        return DB::transaction(function () use ($position, $roleNames) {
            $roles = Role::whereIn('name', $roleNames)->get();
            $removed = $position->roles->pluck('id')->diff($roles->pluck('id'))->all();

            $position->roles()->sync($roles->pluck('id')->all());
            $position->unsetRelation('roles');

            $warnings = [];
            $holders = PositionStaff::query()
                ->where('position_id', $position->id)
                ->inEffect()
                ->get();

            foreach ($holders as $holder) {
                if ($removed !== []) {
                    $this->revokeGrantsFor($holder, $removed);
                }

                [, $warning] = $this->syncGrantsForAssignment($holder);

                if ($warning) {
                    $warnings[] = $warning;
                }
            }

            return ['reconciled' => $holders->count(), 'warnings' => $warnings];
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
            ->each(fn (PositionStaff $open) => $this->close($open, $closedOn));
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
            ->each(fn (PositionStaff $held) => $this->close($held, $closedOn));
    }

    /**
     * Close an assignment and withdraw what it conferred.
     *
     * The end date is never allowed to precede the row's own start date, which
     * a backdated assignment would otherwise cause.
     */
    protected function close(PositionStaff $assignment, Carbon $closedOn): void
    {
        $startDate = $assignment->start_date;

        $assignment->update([
            'end_date' => $startDate && $startDate->gt($closedOn) ? $startDate : $closedOn,
        ]);

        $this->revokeGrantsFor($assignment);
    }
}
