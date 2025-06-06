<?php

namespace App\Policies;

use App\Models\Separation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeparationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view all separations');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Separation  $separation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Separation $separation)
    {
        return $user->can('view all separations') || $user->id === $separation->person->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create separation');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Separation  $separation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Separation $separation)
    {
        return $user->can('update separation');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Separation  $separation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Separation $separation)
    {
        return $user->can('delete separation');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Separation  $separation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Separation $separation)
    {
        return $user->can('restore separation');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Separation  $separation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Separation $separation)
    {
        return $user->can('force delete separation');
    }
}
