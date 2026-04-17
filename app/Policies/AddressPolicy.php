<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
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
    public function view(User $user, Address $address): bool
    {
        return $user->can('view staff') || $this->ownsAddress($user, $address);
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
    public function update(User $user, Address $address): bool
    {
        return $user->can('update staff') || $this->ownsAddress($user, $address);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Address $address): bool
    {
        return $user->can('update staff') || $this->ownsAddress($user, $address);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Address $address): bool
    {
        return $user->can('restore staff');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Address $address): bool
    {
        return $user->can('destroy staff');
    }

    /**
     * Check if the address belongs to the user's person record.
     * Address uses a morph relationship (addressable_id / addressable_type).
     */
    private function ownsAddress(User $user, Address $address): bool
    {
        return $address->addressable_type === (new Person)->getMorphClass()
            && $address->addressable_id === $user->person_id;
    }
}
