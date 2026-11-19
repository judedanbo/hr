<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view staff') || $user->can('view all staff');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view staff') || $user->person_id === $contact->person_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('update staff') || $user->can('create staff');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contact $contact): bool
    {
        return $user->can('update staff') || $user->person_id === $contact->person_id;
    }

    /**
     * Determine whether the user can ask to delete the model.
     *
     * An official email passes here for its owner and for update staff as
     * any contact does, so that they are told why it stays; whether it is
     * actually removed is decided by deleteOfficialEmail.
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('update staff')
            || $user->person_id === $contact->person_id
            || ($contact->isProtectedOrgEmail() && $this->deleteOfficialEmail($user, $contact));
    }

    /**
     * Determine whether the user can remove an official Audit Service email.
     *
     * That address decides where a person's first password is sent, so it is
     * not the owner's to remove, and update staff is not enough. It takes the
     * delete contacts permission, which the seeders give to hr-user.
     */
    public function deleteOfficialEmail(User $user, Contact $contact): bool
    {
        return $user->can('delete contacts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('restore staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->can('destroy staff');
    }
}
