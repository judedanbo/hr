<?php

namespace App\Policies;

use App\Models\StaffType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all staff') || $user->can('view staff');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StaffType $staffType): bool
    {
        return $user->can('view staff');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create staff');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StaffType $staffType): bool
    {
        return $user->can('update staff');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StaffType $staffType): bool
    {
        return $user->can('delete staff');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StaffType $staffType): bool
    {
        return $user->can('restore staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StaffType $staffType): bool
    {
        return $user->can('destroy staff');
    }
}
