<?php

namespace App\Policies;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all staff');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('view staff') || $user->person?->id === $institutionPerson->person_id;
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
    public function update(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('update staff') || $user->person?->id === $institutionPerson->person_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('delete staff');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('restore staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InstitutionPerson $institutionPerson): bool
    {
        return $user->can('destroy staff');
    }
}
