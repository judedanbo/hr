<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use Inertia\Inertia;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Person/Index', [
            'people' => Person::query()
                ->when(request()->search, function($query, $search){
                    $terms =  explode(" ", $search);
                    foreach($terms as $term){
                        $query->where('surname', 'like', "%{$term}%");
                        $query->orWhere('other_names', 'like', "%{$term}%");
                        $query->orWhere('date_of_birth', 'like', "%{$term}%");
                        $query->orWhere('social_security_number', 'like', "%{$term}%");
                        $query->orWhereRaw("monthname(date_of_birth) like '%{$term}%'");
                    }
                })
                ->paginate(10)
                ->through(fn($person) => [
                    'id' => $person->id,
                    'name' => $person->full_name,
                    'gender' => $person ->gender,
                    'dob' => $person ->date_of_birth,
                    'ssn' => $person ->social_security_number,
                    'initials' => $person ->initials,
                    // 'number' => Person::count()
                    // 'department' => $person->departments->count() > 0 ? [
                    //     'id' => $person->departments->first()->id,
                    //     'name' => $person->departments->first()->name
                    // ] : null
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Http\Response
     */
    public function show(Person $person)
    {
        return Inertia::render('Person/Show', [
            'person' => [
                'id' => $person->id,
                'name' => $person->full_name,
                'dob' => $person->date_of_birth,
                'ssn' => $person->social_security_number,
                'initials' => $person->initials
            ]
        ]);
    }
}