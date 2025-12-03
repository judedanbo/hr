<?php

namespace App\Policies;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all staff');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('view staff') || $user->person?->id === $institutionPerson->person_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return $user->can('create staff');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('update staff') || $user->person?->id === $institutionPerson->person_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('delete staff');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('restore staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('force delete staff');
    }
}
