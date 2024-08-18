<?php

namespace App\Http\Controllers;

use App\Models\Person;

class PersonRolesController extends Controller
{
    public function show(Person $person)
    {
        // return $person?->user;
        $person->load([
            'institution',
            'dependent',
            'user',
        ]);
        $staff = new \stdClass;
        if ($person->institution->count() > 0) {
            $staff = $person->institution->map(fn ($inst) => [

                'institution_id' => $inst->id,
                'institution_name' => $inst->name,
                'status_id' => $inst->staff->statuses->first()?->id,
                'status' => $inst->staff->statuses->first()?->status->label(),
            ]);
        }
        // $person->institution->first()?->staff->statuses->map(fn ($status) => [
        // ]);
        // $staff =  $person->institution->count();
        $dependent = $person->dependent ? true : false;
        $user = $person->user ? true : false;

        return compact('staff', 'dependent', 'user');
    }

    public function dependent(Person $person)
    {
        $person->load([
            'dependent.staff.person',
        ]);

        return [
            'staff_id' => [$person->dependent?->staff?->id],
            'staff_number' => [$person->dependent?->staff?->staff_number],
            'file_number' => [$person->dependent?->staff?->file_number],
            'staff_name' => [$person->dependent?->staff?->person->full_name],
        ];
    }
}
