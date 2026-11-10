<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all people');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Person $person): bool
    {
        return $user->can('view person') || $user->person_id === $person->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('view create person');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Also governs the records that hang off a person and have no policy of
     * their own to ask: identities, and adding or removing an address.
     */
    public function update(User $user, Person $person): bool
    {
        return $user->can('update staff')
            || $user->can('view update person')
            || $this->isOwnRecord($user, $person);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Person $person): bool
    {
        return $user->can('view delete person');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Person $person): bool
    {
        return $user->can('view restore person');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Person $person): bool
    {
        return $user->can('view destroy person');
    }

    /**
     * A user with no linked person owns nothing, so a missing link must never
     * compare equal to anything.
     */
    private function isOwnRecord(User $user, Person $person): bool
    {
        return $user->person_id !== null
            && (int) $user->person_id === (int) $person->getKey();
    }
}
