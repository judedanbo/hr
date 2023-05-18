<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use App\Models\InstitutionPerson;
use App\Models\Institution;
use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StorePersonRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
            ->with([
                'person',
                'ranks' => function ($query) {
                    $query->whereNull('end_date');
                },
                'units' => function ($query) {
                    $query->whereNull('units.end_date');
                },

            ])
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'A');
            })
            ->when(request()->search, function ($query, $search) {
                $query->where(function ($whereQry) use ($search) {
                    $whereQry->where('staff_number', 'like', "%{$search}%");
                    $whereQry->orWhere('file_number', 'like', "%{$search}%");
                    $whereQry->orWhere('old_staff_number', 'like', "%{$search}%");
                    $whereQry->orWhereYear('hire_date', $search);
                    $whereQry->orWhereRaw("monthname(hire_date) like ?", ['%' . $search . '%']);
                    $whereQry->orWhereHas('person', function ($perQuery) use ($search) {
                        $perQuery->where('first_name', 'like', "%{$search}%");
                        $perQuery->orWhere('other_names', 'like', "%{$search}%");
                        $perQuery->orWhere('surname', 'like', "%{$search}%");
                        $perQuery->orWhere('date_of_birth', 'like', "%{$search}%");
                    });
                    $whereQry->orWhereHas('ranks', function ($rankQuery) use ($search) {
                        $rankQuery->where('name', 'like', "%{$search}%");
                    });
                    // $whereQry->orWhere('jobs.name', 'like', "%{$search}%");
                });
            })
            ->active()
            // ->withCount()
            ->paginate()
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date,
                'initials' => $staff->person->initials,
                'name' => $staff->person->full_name,
                'gender' => $staff->person->gender->name,
                'status' => $staff->statuses->first()->status->name,
                'dob' => $staff->person->date_of_birth,
                'current_rank' =>  $staff->ranks->count() ? [
                    'id' => $staff->ranks->first()->id,
                    'name' => $staff->ranks->first()->name,
                    'job_id' => $staff->ranks->first()->pivot->job_id,
                    'start_date' => $staff->ranks->first()->pivot->start_date,
                    'end_date' => $staff->ranks->first()->pivot->end_date,
                    'remarks' => $staff->ranks->first()->pivot->remarks
                ] : null,
                'current_unit' => $staff->units->count() ? [
                    'id' => $staff->units->first()->id,
                    'name' => $staff->units->first()->name,
                    'start_date' => $staff->units->first()->pivot->unit_id,
                    'start_date' => $staff->units->first()->pivot->start_date,
                    'end_date' => $staff->units->first()->pivot->end_date,
                ] : null,
            ]);

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'filters' => ['search' => request()->search]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Staff/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(StorePersonRequest $request)
    public function store(StorePersonRequest $request)
    {
        // dd($request->validated());
        DB::transaction(function () use($request) {
            $person = Person::create($request->personalInformation);
            
            $institution =  Institution::find(1);

            // $institution->staff()->attach($person->id, $request->employmentInformation);

             $person->institution()->attach( $institution->id, $request->employmentInformation);
             $staff = InstitutionPerson::where('person_id', $person->id)->first();
             $person->contacts()->create($request->contactInformation);
            $staff->statuses()->create([
                'status' => 'A',
                 'description' => 'Active',
                 'start_date' => Carbon::now() ,
            ]);
        });

        return redirect()->route('staff.index')->with('success', 'Staff created successfully');
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
            ->whereHas('statuses', function ($query) {
                $query->where('status', 'A');
            })
            ->active()
            ->whereId($staff)
            ->firstOrFail();
        // return $staff;
        return Inertia::render('Staff/Show', [
            'person' => [
                'id' => $staff->person->id,
                'name' => $staff->person->full_name,
                'dob' => $staff->person->date_of_birth,
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'nationality' => $staff->person->nationality?->name,
                'religion' => $staff->person->religion,
                'marital_status' => $staff->person->marital_status?->name,
                'identities' => $staff->person->identities->count() > 0 ? $staff->person->identities->map(fn ($id) => [
                    'type' => str_replace('_', ' ', $id->id_type->name,),
                    'number' => $id->id_number,
                ]) : null
            ],
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
                'file_number' => $staff->file_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date,
                'start_date' => $staff->start_date,
                'statuses' => $staff->statuses?->map(fn ($status) => [
                    'id' => $status->id,
                    'status' => $status->status->name,
                    'description' => $status->description,
                    'start_date' => $status->start_date,
                    'end_date' => $status->end_date,
                ]),
                'ranks' => $staff->ranks->map(fn ($rank) => [
                    'id' => $rank->id,
                    'name' => $rank->name,
                    'start_date' => $rank->pivot->start_date,
                    'end_date' => $rank->pivot->end_date,
                    'remarks' => $rank->pivot->remarks,
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
    public function destroy(InstitutionPerson $institutionPerson)
    {
        //
    }
}