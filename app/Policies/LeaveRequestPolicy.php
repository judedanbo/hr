<?php

namespace App\Policies;

use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveRequestPolicy
{
    use HandlesAuthorization;

    private function owns(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->person?->id !== null
            && $user->person->id === $leaveRequest->staff?->person_id;
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('view all leave requests') || $this->owns($user, $leaveRequest);
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->owns($user, $leaveRequest) && $user->can('update leave request');
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->owns($user, $leaveRequest) && $user->can('cancel leave request');
    }

    /**
     * Who may approve/decline: the pool/override permission holder, the resolved
     * approver, or an active delegate of that approver.
     */
    public function decide(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->can('approve staff leave')) {
            return true;
        }

        if (! $leaveRequest->approver_id || ! $user->person_id) {
            return false;
        }

        $staffIds = InstitutionPerson::query()
            ->where('person_id', $user->person_id)
            ->pluck('id');

        if ($staffIds->contains($leaveRequest->approver_id)) {
            return true;
        }

        return ApprovalDelegation::query()
            ->activeFor($leaveRequest->approver_id)
            ->whereIn('delegate_id', $staffIds)
            ->exists();
    }
}
