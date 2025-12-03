<?php

namespace App\Policies;

use App\Models\Dependent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DependentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all dependents') || $user->can('view dependent');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dependent $dependent): bool
    {
        return $user->can('view dependent')
            || $user->person?->id === $dependent->staff?->person_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create dependent');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dependent $dependent): bool
    {
        return $user->can('update dependent') || $user->can('edit dependent')
            || $user->person?->id === $dependent->staff?->person_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dependent $dependent): bool
    {
        return $user->can('delete dependent');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dependent $dependent): bool
    {
        return $user->can('restore dependent');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dependent $dependent): bool
    {
        return $user->can('destroy dependent');
    }
}
