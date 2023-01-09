<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use App\Models\InstitutionPerson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionPersonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $staff = InstitutionPerson::query()
            ->when(request()->search, function ($query, $search) {
                $query->Where('staff_number', 'like', "%{$search}%");
                $query->orWhere('file_number', 'like', "%{$search}%");
                $query->orWhere('old_staff_number', 'like', "%{$search}%");
                $query->orWhere('email', 'like', "%{$search}%");
                $query->orWhereYear('hire_date', $search);
                $query->orWhereRaw("monthname(hire_date) like ?", ['%' . $search . '%']);
                $query->orWhere('people.surname', 'like', "%{$search}%");
                $query->orWhere('people.first_name', 'like', "%{$search}%");
                $query->orWhere('people.other_names', 'like', "%{$search}%");
                // $query->orWhere('status', 'like', "%{$search}%");
            })
            ->join('people', function ($join) {
                $join->on('institution_person.person_id', '=', 'people.id');
            })
            ->with([
                'person.identities',
                'ranks' => function ($query) {
                    $query->whereDate('end_date', '>=', now());
                    $query->orWhereDate('end_date', null);
                },
                'units' => function ($query) {
                    $query->whereDate('end_date', '>=', now());
                    $query->orWhereDate('end_date', '>=', now());
                }
            ])
            ->active()
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'email' => $staff->email,
                'status' => $staff->status,
                'hire_date' => $staff->hire_date,
                'initials' => $staff->person->initials,
                'name' => $staff->person->full_name,
                'gender' => $staff->person->gender->name,
                'status' => $staff->status,
                'dob' => $staff->person->date_of_birth,
                'identities' => $staff->person->identities,
                'current_rank' => $staff->ranks->count() ? [
                    'id' => $staff->ranks->first()->id,
                    'name' => $staff->ranks->first()->name,
                    'start_date' => $staff->ranks->first()->pivot->start_date,
                    'end_date' => $staff->ranks->first()->pivot->end_date,
                ] : null,
                'current_unit' => $staff->units->count() ? [
                    'id' => $staff->units->first()->id,
                    'name' => $staff->units->first()->name,
                    'start_date' => $staff->units->first()->pivot->start_date,
                    'end_date' => $staff->units->first()->pivot->end_date,
                ] : null,
            ]);
        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'filters' => ['search' => request()->search]
        ]);
        return $staff;
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
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function show($staff)
    {
        $staff =  InstitutionPerson::query()
            ->with(
                [
                    'person.address', 'person.contacts',
                    'units.institution',
                    'ranks',
                    'dependents.person'
                ]
            )
            ->whereId($staff)
            ->first();
        // return $staff;
        return Inertia::render('Staff/Show', [
            'person' => [
                'id' => $staff->person->id,
                'name' => $staff->person->full_name,
                'dob' => $staff->person->date_of_birth,
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'nationality' => $staff->person->nationality->name,
                'religion' => $staff->person->religion,
                'marital_status' => $staff->person->marital_status->name,
                'identities' => $staff->person->identities->count() > 0 ? $staff->person->identities->map(fn ($id) => [
                    'type' => str_replace('_', ' ', $id->id_type->name,),
                    'number' => $id->id_number,
                ]) : null
            ],
            // 'identification' => $staff->identities,
            'contacts' => $staff->person->contacts->count() > 0 ?  $staff->person->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'contact' => $contact->contact,
                'contact_type_id' => $contact->contact_type_id,
                'valid_end' => $contact->valid_end,
            ]) : null,
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
            ] : null,
            'staff' => [
                'staff_id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date,
                'start_date' => $staff->start_date,
                'name' => $staff->person->full_name,
                'email' => strtolower(explode(' ', $staff->person->other_names)[0]) . '.' . strtolower(explode(' ', $staff->person->surname)[0]) . '@audit.gov.gh',
                'other_names' => $staff->person->other_names,
                'dob' => $staff->person->date_of_birth,
                'gender' => $staff->person->gender->name,
                // 'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'religion' => $staff->person->religion,
                'marital_status' => $staff->person->marital_status,
                'ranks' => $staff->ranks->map(fn ($rank) => [
                    'id' => $rank->id,
                    'name' => $rank->name,
                    'start_date' => $rank->pivot->start_date,
                    'end_date' => $rank->pivot->end_date,
                ]),

                'units' => $staff->units->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'start_date' => $unit->pivot->start_date,
                    'end_date' => $unit->pivot->end_date,
                ]),
                // ? [
                //     'id' => $staff->unit->id,
                //     'name' => $staff->unit->name,
                //     'institution_id' => $staff->unit->institution->id,
                //     'institution_name' => $staff->unit->institution->name,
                // ] : null,
                'dependents' =>  $staff->dependents ? $staff->dependents->map(fn ($dep) => [
                    'id' => $dep->id,
                    'person_id' => $dep->person_id,
                    'name' => $dep->person->full_name,
                    'gender' => $dep->person->gender,
                    'dob' => $dep->person->date_of_birth,
                    'relation' => $dep->relation,
                ]) : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function edit(InstitutionPerson $institutionPerson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InstitutionPerson $institutionPerson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function dtroy(InstitutionPerson $institutionPerson)
    {
        //
    }
}