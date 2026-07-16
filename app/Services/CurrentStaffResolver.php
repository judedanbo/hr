<?php

namespace App\Services;

use App\Models\InstitutionPerson;
use App\Models\User;

class CurrentStaffResolver
{
    /**
     * Resolve the active staff record (InstitutionPerson) for a user, or null.
     */
    public function resolve(?User $user): ?InstitutionPerson
    {
        if (! $user?->person_id) {
            return null;
        }

        return InstitutionPerson::query()
            ->active()
            ->where('person_id', $user->person_id)
            ->first();
    }

    /**
     * Resolve the active staff record or abort with 403.
     */
    public function resolveOrAbort(?User $user): InstitutionPerson
    {
        $staff = $this->resolve($user);

        abort_unless($staff, 403, 'Your account is not linked to an active staff record.');

        return $staff;
    }
}
