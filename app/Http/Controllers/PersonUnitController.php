<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use App\Models\Dependent;
use App\Models\Person;
use App\Models\PersonUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class PersonUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function show($staff)
    {

        $staff =  PersonUnit::with(['person.address', 'person.contacts', 'unit.institution', 'jobs', 'dependents.person'])->whereId($staff)->first();
        return Inertia::render('Staff/Show', [
            'person' => [
                'id' => $staff->person->id,
                'name' => $staff->person->full_name,
                'dob' => $staff->person->date_of_birth,
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'nationality' => $staff->person->nationality
            ],
            'contacts' => $staff->person->contacts->count() > 0 ?  $staff->person->contacts->map(fn($contact)=>[
                'id' => $contact->id,
                'contact' => $contact->contact,
                'contact_type_id' => $contact->contact_type_id,
                'valid_end' => $contact->valid_end,
            ]):null,
            'contact_types' => ContactType::select(['id', 'name'])->get(),

            'address' => $staff->person->address->count() > 0 ? [
                'id' => $staff->person->address->first()->id,
                'address_line_1' => $staff->person->address->first()->address_line_1,
                'address_line_2' => $staff->person->address->first()->address_line_2,
                'city' => $staff->person->address->first()->city,
                'region' => $staff->person->address->first()->region,
                'country' => $staff->person->address->first()->country,
                'post_code' => $staff->person->address->first()->post_code,
                'valid_end' => $staff->person->address->first()->valid_end,
            ]:null,
            'staff' => [
                'staff_id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date,
                'start_date' => $staff->start_date,
                'name' => $staff->person->full_name,
                'email' => strtolower(explode(' ', $staff->person->other_names)[0]) . '.'. strtolower(explode(' ', $staff->person->surname)[0]) . '@audit.gov.gh',
                'other_names' => $staff->person->other_names,
                'dob' => $staff->person->date_of_birth,
                'gender' => $staff->person->gender,
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'current_job' => $staff->jobs[0]->name,
                'current_job_id' => $staff->jobs[0]->id,
                'unit' => $staff->unit? [
                    'id' => $staff->unit->id,
                    'name' => $staff->unit->name,
                    'institution_id' => $staff->unit->institution->id,
                    'institution_name' => $staff->unit->institution->name,
                ] : null,
                'dependents' =>  $staff->dependents? $staff->dependents->map(fn($dep) => [
                    'id' => $dep->id,
                    'person_id' => $dep->person_id,
                    'name' => $dep->person->full_name,
                    'gender' => $dep->person->gender,
                    'dob' => $dep->person->date_of_birth,
                    'relation' => $dep->relation,
                ] ) : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function edit(PersonUnit $personUnit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PersonUnit $personUnit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function destroy(PersonUnit $personUnit)
    {
        //
    }

    /**
     * create a dependent for a staff .
     *
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function createDependent(Request $request, $staff)
    {

        // Validate
        $attribute = $request->validate([
            'surname' => "required|max:30",
            'other_names' => "required|max:60",
            'date_of_birth' => "required|date|before_or_equal:now",
            'gender' => 'required|max:10',//[new Enum(Gender::class)],
            'nationality' => "nullable|max:40",
            'relation' => "required|max:40",
        ]);
        // create person
        DB::transaction(function() use($attribute, $staff){
            $person  = Person::create($attribute);
            Dependent::create([
                'person_id' => $person->id,
                'staff_id' => $staff,
                'relation' => $attribute['relation'],
            ]);
        });

        // make dependent of staff
        return redirect()->back();
    }



    /**
     * delete the staff dependent resource from storage.
     *
     * @param  \App\Models\PersonUnit  $personUnit
     * @return \Illuminate\Http\Response
     */
    public function deleteDependent(PersonUnit $personUnit, $dependent)
    {
        //
    }
}