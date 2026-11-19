<?php

namespace App\Policies;

use App\Models\Dependent;
use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can list people.
     *
     * The same permission that opens the Staff list, since the people list
     * names the same individuals.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view all staff') || $user->can('view all people');
    }

    /**
     * Determine whether the user can read a person's record.
     *
     * The rule of the Staff page: anyone's with view all staff, otherwise
     * only your own. A dependant's record belongs to the member of staff it
     * is attached to, so they may read it too.
     */
    public function view(User $user, Person $person): bool
    {
        return $user->can('view all staff')
            || $user->can('view person')
            || $this->isOwnRecord($user, $person)
            || $this->isOwnDependant($user, $person);
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
     * Covers a person's core details and identity numbers. Both are managed
     * by HR, so owning the record is not enough: a date of birth sets the
     * retirement date, and My Profile shows these read-only.
     */
    public function update(User $user, Person $person): bool
    {
        return $user->can('update staff') || $user->can('view update person');
    }

    /**
     * Determine whether the user can add or remove the person's addresses.
     *
     * Unlike core details, an address is self-service: staff keep their own
     * up to date from My Profile.
     */
    public function manageAddresses(User $user, Person $person): bool
    {
        return $this->update($user, $person) || $this->isOwnRecord($user, $person);
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

    /**
     * Whether the person is recorded as a dependant of the user's own staff
     * record.
     */
    private function isOwnDependant(User $user, Person $person): bool
    {
        if ($user->person_id === null) {
            return false;
        }

        return Dependent::query()
            ->where('person_id', $person->getKey())
            ->whereHas('staff', fn (Builder $staff) => $staff->where('person_id', $user->person_id))
            ->exists();
    }
}
