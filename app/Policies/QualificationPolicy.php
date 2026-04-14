<?php

namespace App\Policies;

use App\Models\Qualification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QualificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('veiw all staff qualifications') || $user->can('view staff qualification');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Qualification $qualification): bool
    {
        return $user->can('view staff qualification')
            || $user->person?->id === $qualification->person_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create staff qualification');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Qualification $qualification): bool
    {
        return $user->can('edit staff qualification')
            || $user->person?->id === $qualification->person_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Qualification $qualification): bool
    {
        return $user->can('delete staff qualification');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Qualification $qualification): bool
    {
        return $user->can('restore staff qualification');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Qualification $qualification): bool
    {
        return $user->can('destroy staff qualification');
    }
}
