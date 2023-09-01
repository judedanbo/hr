<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PersonRolesController extends Controller
{
    public function show(Person $person)
    {
        $person->load([
            'institution',
            'dependent',
            'user'
        ]);
        $staff = new \stdClass;
        if ($person->institution->count() > 0) {
            $staff = $person->institution->map(fn ($inst) => [

                'institution_id' => $inst->id,
                'institution_name' => $inst->name,
                'status_id' => $inst->staff->statuses->first()->id,
                'status' => $inst->staff->statuses->first()->status->label(),
            ]);
        }
        // $person->institution->first()?->staff->statuses->map(fn ($status) => [
        // ]);
        // $staff =  $person->institution->count();
        $dependent = $person->dependent ? true : false;
        $user = $person->user ? true : false;

        return compact('staff', 'dependent', 'user');
    }
}