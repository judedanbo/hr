<?php

namespace App\Policies;

use App\Models\Dependent;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * A dependent hangs off a member of staff, so changing one follows the same
 * rule as that person's contacts and addresses: the member of staff it belongs
 * to, or a holder of update staff.
 *
 * The create/edit/update/delete dependent permissions are deliberately not
 * accepted here for other people's dependents. StaffUserSeeder and
 * DependentSeeder hand them to the staff role as self-service permissions, so
 * treating them as a licence over anyone's dependents would open these actions
 * to every member of staff in a database those seeders have touched.
 */
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
            || $this->isOwnStaffRecord($user, $dependent->staff);
    }

    /**
     * Determine whether the user can create a dependent for the given staff member.
     */
    public function create(User $user, ?InstitutionPerson $staff = null): bool
    {
        return $user->can('update staff')
            || $this->isOwnStaffRecord($user, $staff);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dependent $dependent): bool
    {
        return $user->can('update staff')
            || $this->isOwnStaffRecord($user, $dependent->staff);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dependent $dependent): bool
    {
        return $user->can('update staff')
            || $this->isOwnStaffRecord($user, $dependent->staff);
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

    /**
     * Whether the staff record belongs to the user's own person.
     *
     * A user with no linked person owns nothing, so a missing link on either
     * side must never compare equal.
     */
    private function isOwnStaffRecord(User $user, ?InstitutionPerson $staff): bool
    {
        return $user->person_id !== null
            && $staff !== null
            && (int) $user->person_id === (int) $staff->person_id;
    }
}
