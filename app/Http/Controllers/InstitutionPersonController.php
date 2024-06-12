<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Enums\ContactTypeEnum;
use App\Http\Requests\StoreInstitutionPersonRequest;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobStaff;
use App\Models\Person;
use App\Models\StaffUnit;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->active()
            ->with('person')
            ->withCurrentUnit()
            ->withCurrentRank()
            ->search(request()->search)
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->id,
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date?->format('d M Y'),
                'hire_date_distance' => $staff->hire_date?->diffForHumans(),
                'initials' => $staff->person->initials,
                'name' => $staff->person->full_name,
                'gender' => $staff->person->gender->label(),
                'dob' =>  $staff->person->date_of_birth?->format('d M Y'),
                'image' => $staff->person->image ? Storage::disk('avatars')->url($staff->person->image) : null,
                'dob_distance' =>  $staff->person->date_of_birth?->diffInYears() . " years old",
                'retirement_date' => $staff->person->date_of_birth?->addYears(60)->format('d M Y'),
                'retirement_date_distance' => $staff->person->date_of_birth?->addYears(60)->diffForHumans(),
                'current_rank' => $staff->currentRank ? [
                    'id' => $staff->currentRank?->id,
                    'name' => $staff->currentRank?->job?->name,
                    'job_id' => $staff->currentRank->name,
                    'start_date' => $staff->currentRank->start_date?->format('d M Y'),
                    'start_date_distance' => $staff->currentRank->start_date?->diffForHumans(),
                    'end_date' => $staff->currentRank->end_date?->format('d M Y'),
                    'remarks' => $staff->currentRank->remarks,
                ] : null,
                'current_unit' => $staff->currentUnit ? [
                    'id' => $staff->currentUnit->unit_id,
                    'rank' => $staff->currentUnit,
                    'name' => $staff->currentUnit->unit?->name,
                    'start_date' => $staff->currentUnit->start_date?->format('d M Y'),
                    'start_date_distance' => $staff->currentUnit->start_date?->diffForHumans(),
                    'end_date' => $staff->currentUnit->end_date?->format('d M Y'),
                ] : null,
            ]);

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'filters' => ['search' => request()->search],
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
    public function store(StoreInstitutionPersonRequest $request)
    {
        // return $request->staffData;
        $staff = null;
        $transaction  = DB::transaction(function () use ($request, $staff) {
            $person = Person::create($request->staffData['bio']);

            $institution = Institution::find(1);

            $person->institution()->attach($institution->id, $request->staffData['employment']);
            $staff = InstitutionPerson::where('person_id', $person->id)->first();
            $person->address()->create($request->staffData['address']);
            $person->contacts()->create($request->staffData['contact']);
            $person->qualifications()->create($request->staffData['qualifications']);

            $staff->statuses()->create([
                'status' => 'A',
                'description' => 'Active',
                'institution_id' => $institution->id,
                'start_date' => Carbon::now(),
            ]);
            // dd($request->staffData);
            $rank = $request->staffData['rank'];
            $rank['job_id'] = $request->staffData['rank']['rank_id'];
            unset($rank['rank_id']);
            $staff->ranks()->attach($rank['job_id'], $rank);
            if (array_key_exists('unit_id', $request->staffData['unit'])) {
                $staff->units()->attach($request->staffData['unit']['unit_id'], $request->staffData['unit']);
            }
            return $staff;
        });

        if ($transaction === null || $transaction['id'] === null) {
            return redirect()->route('staff.index')->with('failed', 'could not create staff. please try again on contact administrator');
        }
        // return $transaction;
        return redirect()->route('staff.show', ['staff' => $transaction['id']])->with('success', "Staff created successfully");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function show($staffId)
    {
        $staff = InstitutionPerson::query()
            ->with(
                [
                    'person' => function ($query) {
                        $query->with(['address' => function ($query) {
                            $query->whereNull('valid_end');
                        }]);
                    }, 'person.contacts', 'person.qualifications',
                    // 'units.institution',
                    // 'units.parent',
                    'units' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                        $query->with(['institution', 'parent']);
                    },
                    'ranks',
                    'dependents.person',
                    'statuses',
                    'notes'
                ]
            )
            ->active()
            ->whereId($staffId)
            ->first();
        if (!$staff) {
            return redirect()->route('person.show', ['person' => $staffId])->with('error', 'Staff not found');
        }
        return Inertia::render('Staff/NewShow', [
            'user' => [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'person_id' => auth()->user()->person_id,
            ],
            'person' => [
                'id' => $staff->person->id,
                'name' => $staff->person->full_name,
                'dob-value' => $staff->person->date_of_birth,
                'dob' => $staff->person->date_of_birth?->format('d M Y'),
                'dob_distance' => $staff->person->date_of_birth?->diffInYears() . " years old",
                'gender' => $staff->person->gender?->label(),
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'nationality' => $staff->person->nationality?->nationality(),
                'religion' => $staff->person->religion,
                'marital_status' => $staff->person->marital_status?->label(),
                'image' => $staff->person->image ? Storage::disk('avatars')->url($staff->person->image) : null,
                'identities' => $staff->person->identities->count() > 0 ? $staff->person->identities->map(fn ($id) => [
                    'type' => str_replace('_', ' ', $id->id_type->name),
                    'number' => $id->id_number,
                ]) : null,
            ],
            'qualifications' => $staff->person->qualifications->count() > 0 ? $staff->person->qualifications->map(fn ($qualification) => [
                'id' => $qualification->id,
                'person_id' => $qualification->person_id,
                'course' => $qualification->course,
                'institution' => $qualification->institution,
                'qualification' => $qualification->qualification,
                'qualification_number' => $qualification->qualification_number,
                'level' => $qualification->level,
                'year' => $qualification->year,
                'documents' => $qualification->documents->count() > 0 ? $qualification->documents->map(fn ($document) => [
                    'document_type' => $document->document_type,
                    'document_title' => $document->document_title,
                    'document_status' => $document->document_status,
                    'document_number' => $document->document_number,
                    'file_name' => $document->file_name,
                    'file_type' => $document->file_type,
                ]) : null,
            ]) : [],
            'contacts' => $staff->person->contacts->count() > 0 ? $staff->person->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'contact' => $contact->contact,
                'contact_type' => $contact->contact_type,
                'contact_type_dis' => $contact->contact_type->label(),
                'valid_end' => $contact->valid_end,
            ]) : null,

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
                'institution_id' => $staff->institution_id,
                'staff_number' => $staff->staff_number,
                'file_number' => $staff->file_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date,
                'retirement_date' => $staff->person->date_of_birth?->addYears(60),
                'start_date' => $staff->start_date,
                'statuses' => $staff->statuses?->map(fn ($status) => [
                    'id' => $status->id,
                    'status' => $status->status,
                    'status_display' => $status->status?->name,
                    'description' => $status->description,
                    'start_date' => $status->start_date?->format('Y-m-d'),
                    'start_date_display' => $status->start_date?->format('d M Y'),
                    'end_date' => $status->end_date?->format('Y-m-d'),
                    'end_date_display' => $status->end_date?->format('d M Y'),
                ]),
                'staff_type' => $staff->type?->map(fn ($type) => [
                    'id' => $type->id,
                    'type' => $type->staff_type,
                    'type_label' => $type->staff_type->label(),
                    'start_date' => $type->start_date?->format('Y-m-d'),
                    'start_date_display' => $type->start_date?->format('d M Y'),
                    'end_date' => $type->end_date?->format('Y-m-d'),
                    'end_date_display' => $type->end_date?->format('d M Y'),
                ]),
                'ranks' => $staff->ranks->map(fn ($rank) => [
                    'id' => $rank->id,
                    'name' => $rank->name,
                    'staff_name' => $staff->person->full_name,
                    'staff_id' => $rank->pivot->staff_id,
                    'rank_id' => $rank->pivot->job_id,
                    'start_date' => $rank->pivot->start_date?->format('d M Y'),
                    'start_date_unix' => $rank->pivot->start_date?->format('Y-m-d'),
                    'end_date' => $rank->pivot->end_date?->format('d M Y'),
                    'end_date_unix' => $rank->pivot->end_date?->format('Y-m-d'),
                    'remarks' => $rank->pivot->remarks,
                    'distance' => $rank->pivot->start_date?->diffForHumans(),
                ]),
                'notes' => $staff->notes->count() > 0 ? $staff->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'note_date' => $note->note_date->diffForHumans(),
                    'note_date_time' => $note->note_date,
                    'note_type' => $note->note_type,
                    'created_by' => $note->created_by,
                    'url' => $note->url,

                ]) : null,
                'units' => $staff->units->map(fn ($unit) => [
                    // 'unit' => $unit,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'status' => $unit->pivot->status?->label(),
                    'status_color' => $unit->pivot->status?->color(),
                    'department' => $unit->parent?->name,
                    'department_short_name' => $unit->parent?->short_name,
                    'staff_id' => $unit->pivot->staff_id,
                    'start_date' => $unit->pivot->start_date?->format('d M Y'),
                    'start_date_unix' => $unit->pivot->start_date?->format('Y-m-d'),
                    'end_date' => $unit->pivot->end_date?->format('d M Y'),
                    'end_date_unix' => $unit->pivot->end_date?->format('Y-m-d'),
                    'distance' => $unit->pivot->start_date?->diffForHumans(),
                    'remarks' => $unit->pivot->remarks,
                    'old_data' => $unit->pivot->old_data,
                ]),
                'dependents' => $staff->dependents ? $staff->dependents->map(fn ($dep) => [
                    'id' => $dep->id,
                    'person_id' => $dep->person_id,
                    'title' => $dep->person->title,
                    'name' => $dep->person->full_name,
                    'surname' => $dep->person->surname,
                    'first_name' => $dep->person->first_name,
                    'other_names' => $dep->person->other_names,
                    'nationality' => $dep->person->nationality?->label(),
                    'nationality_form' => $dep->person->nationality,
                    'marital_status' => $dep->person->marital_status,
                    'religion' => $dep->person->religion,
                    'gender' => $dep->person->gender?->label(),
                    'gender_form' => $dep->person->gender,
                    'date_of_birth' => $dep->person->date_of_birth?->format('Y-m-d'),
                    'relation' => $dep->relation,
                    'staff_id' => $staff->id,
                    'image' => $dep->person->image ? Storage::disk('avatars')->url($dep->person->image) : null,

                ]) : null,
            ],
        ]);
    }



    public function promote(Request $request, InstitutionPerson $staff)
    {
        $request->validate([
            'rank_id' => ['required', 'exists:jobs,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $staff->load('ranks');

        $staff->ranks()->wherePivot('end_date', null)->update([
            'end_date' => Carbon::parse($request->start_date)->subDay(),
        ]);

        $staff->ranks()->attach($request->rank_id, [
            'start_date' => $request->start_date,
            // 'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promoted successfully');
    }

    function promotions(InstitutionPerson $staff)
    {
        $staff->load('ranks', 'person');
        return [
            'staff_id' => $staff->id,
            'full_name' => $staff->person->full_name,
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'retirement_date' => $staff->person->date_of_birth?->addYears(60)->format('d M Y'),
            'start_date' => $staff->start_date,
            'promotions' => $staff->ranks ? $staff->ranks->map(fn ($rank) => [
                'id' => $rank->id,
                'name' => $rank->name,
                'start_date' => $rank->pivot->start_date?->format('d M Y'),
                'end_date' => $rank->pivot->end_date?->format('d M Y'),
                'remarks' => $rank->pivot->remarks,
                'distance' => $rank->pivot->start_date->diffInYears()
            ])
                : null
        ];
    }
    // public function transfer(Request $request, InstitutionPerson $staff)
    // {
    //     $request->validate([
    //         'unit_id' => ['required', 'exists:units,id'],
    //         'start_date' => ['required', 'date'],
    //         'end_date' => ['nullable', 'date', 'after:start_date'],
    //         'remarks' => ['nullable', 'string'],
    //     ]);

    //     $staff->load('units');


    //     $staff->units()->wherePivot('end_date', null)->update([
    //         'staff_unit.end_date' => Carbon::parse($request->start_date)->subDay(),
    //     ]);

    //     $staff->units()->attach($request->unit_id, [
    //         'start_date' => $request->start_date,
    //         // 'end_date' => $request->end_date,
    //         'remarks' => $request->remarks,
    //     ]);

    //     return redirect()->back()->with('success', 'Staff promoted successfully');
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(InstitutionPerson $staff)
    {
        $staff->load('person');
        return  [
            'id' => $staff->id,
            'file_number' => $staff->file_number,
            'staff_number' => $staff->staff_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('Y-m-d'),
            'end_date' => $staff->end_date?->format('Y-m-d'),
            'person' => [
                'id' => $staff->person->id,
                'title' => $staff->person->title,
                'surname' => $staff->person->surname,
                'first_name' => $staff->person->first_name,
                'other_names' => $staff->person->other_names,
                'date_of_birth' => $staff->person->date_of_birth?->format('Y-m-d'),
                'place_of_birth' => $staff->person->place_of_birth,
                'country_of_birth' => $staff->person->country_of_birth,
                'gender' => $staff->person->gender,
                'marital_status' => $staff->person->marital_status,
                'religion' => $staff->person->religion,
                'nationality' => $staff->person->nationality,
                'ethnicity' => $staff->person->ethnicity,
                'image' => Storage::disk('avatars')->url($staff->person->image),
                'about' => $staff->person->about,
            ]
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStaffRequest $request, InstitutionPerson $staff)
    {
        // return $request->validated();
        $validated = $request->validated();
        $personalInformation = $validated['staffData']['personalInformation'];
        $employmentInformation = $validated['staffData']['employmentInformation'];
        // return $personalInformation;
        $staff->person->update($personalInformation);
        $staff->update($employmentInformation);

        return back()->with('success', 'Staff updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(InstitutionPerson $institutionPerson)
    {
        //
    }

    public function writeNote(StoreNoteRequest $request, InstitutionPerson $staff)
    {
        $validated = $request->validated();

        $validated['created_by'] = auth()->user()->id;
        $staff->writeNote($validated);
        return redirect()->back()->with('success', 'Note added successfully.');
    }
}
