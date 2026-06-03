<?php

namespace App\Services;

use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use App\Models\Unit;

class ApproverResolver
{
    /**
     * Resolve the staff member who should approve a leave request for the given
     * staff. Walks up the unit hierarchy to the first designated head that is not
     * the requester (self-escalation), honouring an active delegation. Returns
     * null when no head is found (the request then falls to the approver pool).
     */
    public function resolve(InstitutionPerson $staff): ?InstitutionPerson
    {
        $unit = $staff->units()->wherePivotNull('end_date')->first();

        while ($unit instanceof Unit) {
            $headId = $unit->head_staff_id;

            if ($headId && $headId !== $staff->id) {
                $head = InstitutionPerson::find($headId);

                if ($head) {
                    return $this->applyDelegation($head, $staff);
                }
            }

            $unit = $unit->parent;
        }

        return null;
    }

    /**
     * If the resolved head has an active delegation, route to the delegate
     * (unless the delegate is the requester themselves).
     */
    private function applyDelegation(InstitutionPerson $head, InstitutionPerson $staff): InstitutionPerson
    {
        $delegate = ApprovalDelegation::query()
            ->activeFor($head->id)
            ->latest('id')
            ->first()?->delegate;

        if ($delegate && $delegate->id !== $staff->id) {
            return $delegate;
        }

        return $head;
    }
}
