<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all units') || $user->can('view unit');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Office $office): bool
    {
        return $user->can('view unit');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create units');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Office $office): bool
    {
        return $user->can('update units');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Office $office): bool
    {
        return $user->can('delete units');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Office $office): bool
    {
        return $user->can('restore units');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Office $office): bool
    {
        return $user->can('destroy units');
    }
}
