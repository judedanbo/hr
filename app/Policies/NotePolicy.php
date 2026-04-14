<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view staff notes') || $user->can('view all staff');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        return $user->can('view staff notes') || $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create staff notes');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        return $user->can('edit staff notes') || $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        return $user->can('edit staff notes') || $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note): bool
    {
        return $user->can('edit staff notes');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note): bool
    {
        return $user->can('edit staff notes');
    }
}
