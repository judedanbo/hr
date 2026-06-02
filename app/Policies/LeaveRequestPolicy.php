<?php

namespace App\Policies;

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
}
