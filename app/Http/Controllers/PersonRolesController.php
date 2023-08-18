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
            'dependent', 'user'
        ]);
        // return $person->institution->status;
        $staff =  $person->institution->count();
        $dependent = $person->dependent ? true : false;
        $user = $person->user ? true : false;

        return compact('staff', 'dependent', 'user');
    }
}