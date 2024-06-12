<?php

namespace App\Http\Controllers;

use App\Enums\ContactTypeEnum;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Contact;
use App\Models\Person;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Enum;

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
                ->search(request()->search)
                ->with('institution', 'dependent', 'identities')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($person) => [
                    'id' => $person->id,
                    'name' => $person->full_name,
                    'gender' => $person->gender?->label(),
                    'dob' => $person->date_of_birth,
                    // 'ssn' => $person->identities->first()?->id_number,
                    'initials' => $person->initials,
                    'image' => $person->image ? Storage::disk('avatars')->url($person->image) : null,
                    'institution' => $person->institution ? [
                        'id' => $person->institution->first()?->id,
                        'name' => $person->institution->first()?->name,
                        'staff_id' => $person->institution->first()?->staff->id,
                    ] : null,
                    'dependent' => $person->dependent ? [
                        'staff_id' => $person->dependent->staff_id,
                    ] : null,
                ]),
            'contact_types' => [],
            'filters' => ['search' => request()->search],
        ]);
    }

    function store(UpdatePersonRequest $request)
    {
        // return $request->validated();
        if (!$request->hasFile('image')) {
            return response()->json(['error', 'There is no file attached', 400]);
        }
        try {
            $avatar = Storage::disk('avatars')->put('/', $request->image);
            return Person::create($request->validated());
            if (!$avatar) {
                return response()->json(['error', 'the file could not be saved', 500]);
            }
            $person =  $request->validated();
            $person['image'] = $avatar;
            $newPerson  = Person::create($person);
        } catch (Exception $e) {
            return response()->json(['error', "failed to add Person with message " . $e->getMessage(), 500]);
        }
        return redirect()->route('person.show', $newPerson->id)->with('success', 'Person records created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Http\Response
     */
    public function show($person)
    {
        $person = Person::with([
            'address' => function ($query) {
                $query->where('valid_end', null);
            },
            'contacts',
            'user',
            'dependent',
            'dependents',
            'institution',
            'qualifications'
        ])
            ->whereId($person)->first();
        // return $person;
        return Inertia::render('Person/NewShow', [
            'person' => [
                'id' => $person->id,
                'name' => $person->full_name,
                'dob-value' => $person->date_of_birth,
                'dob' => $person->date_of_birth->format('d M Y'),
                'dob_distance' => $person->date_of_birth->diffInYears() . " years old",
                'gender' => $person->gender?->label(),
                'ssn' => $person->social_security_number,
                'initials' => $person->initials,
                'nationality' => $person->nationality?->nationality(),
                'religion' => $person->religion,
                'marital_status' => $person->marital_status?->label(),
                'image' => $person->image ? Storage::disk('avatars')->url($person->image) : null,
                'identities' => $person->identities->count() > 0 ? $person->identities->map(fn ($id) => [
                    'type' => str_replace('_', ' ', $id->id_type->name),
                    'number' => $id->id_number,
                ]) : null,
            ],
            'contacts' => $person->contacts->count() > 0 ? $person->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'contact' => $contact->contact,
                'contact_type_id' => $contact->contact_type_id,
                'valid_end' => $contact->valid_end,
            ]) : null,
            'address' => $person->address->count() > 0 ? [
                'id' => $person->address->first()->id,
                'address_line_1' => $person->address->first()->address_line_1,
                'address_line_2' => $person->address->first()->address_line_2,
                'city' => $person->address->first()->city,
                'region' => $person->address->first()->region,
                'country' => $person->address->first()->country,
                'post_code' => $person->address->first()->post_code,
                'valid_end' => $person->address->first()->valid_end,
            ] : null,
            'staff' => $person->institution->count() > 0 ?  $person->institution->map(fn ($inst) => [
                'status' =>  $inst->staff->statuses?->map(fn ($status) => [
                    'id' => $status->id,
                    'status' => $status->status,
                    'status_display' => $status->status?->name,
                    'description' => $status->description,
                    'start_date' => $status->start_date?->format('Y-m-d'),
                    'start_date_display' => $status->start_date?->format('d M Y'),
                    'end_date' => $status->end_date?->format('Y-m-d'),
                    'end_date_display' => $status->end_date?->format('d M Y'),
                ]),
                'type' =>  $inst->staff->type->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'type' => $type->staff_type,
                        'type_label' => $type->staff_type->label(),
                        'start_date' => $type->start_date?->format('Y-m-d'),
                        'end_date' => $type->end_date?->format('Y-m-d'),
                        'start_date_display' => $type->start_date?->format('d M Y'),
                        'end_date_display' => $type->end_date?->format('d M Y'),
                    ];
                }),
                'units' =>  [
                    'unit_id' => $inst->staff->units?->first()->id,
                    'unit_name' => $inst->staff->units?->first()->name,
                    'status' => $inst->staff->units?->first()->pivot->status?->label(),
                    'status_color' => $inst->staff->units?->first()->pivot->status?->color(),
                    'department' => $inst->staff->units?->first()->parent?->name,
                    'staff_id' => $inst->staff->units?->first()->pivot->staff_id,
                    'start_date' => $inst->staff->units?->first()->pivot->start_date?->format('d M Y'),
                    'end_date' => $inst->staff->units?->first()->pivot->end_date?->format('d M Y'),
                    'remarks' => $inst->staff->units?->first()->pivot->remarks,
                ],

                'ranks' => [ //$inst->staff->ranks->first(),
                    'id' => $inst->staff->ranks?->first()->id,
                    'name' => $inst->staff->ranks?->first()->name,
                    'job_id' => $inst->staff->ranks?->first()->id,
                    'start_date' => $inst->staff->ranks?->first()->start_date?->format('d M Y'),
                    'start_date_distance' => $inst->staff->ranks?->first()->start_date?->diffForHumans(),
                    'end_date' => $inst->staff->ranks?->first()->end_date?->format('d M Y'),
                    'remarks' => $inst->staff->ranks?->first()->remarks,
                ],
                'institution_name' =>  $inst->name,
                'institution_id' =>  $inst->id,
                'staff_id' =>  $inst->staff->id,
                'staff_number' =>  $inst->staff->staff_number,
                'file_number' =>  $inst->staff->file_number,
                'hire_date' =>  $inst->staff->hire_date,
                'hire_date_dis' =>  $inst->staff->hire_date->format('d M Y'),
                'end_date' =>  $inst->staff->end_date,
            ])  : null,
            'dependent' => $person->dependent,
            'dependents' => $person->dependents ? $person->dependents->map(fn ($dep) => [
                'id' => $dep->id,
                'person_id' => $dep->person_id,
                'name' => $dep->person->full_name,
                'gender' => $dep->person->gender?->label(),
                'dob' => $dep->person->date_of_birth,
                'relation' => $dep->relation,
                'staff_id' => $dep->staff_id,
            ]) : null,
        ]);
    }

    public function edit(Person $person)
    {
        return [
            'id' => $person->id,
            'title' => $person->title,
            'first_name' => $person->first_name,
            'other_names' => $person->other_names,
            'main_name' => $person->main_name,
            'surname' => $person->surname,
            'date_of_birth' => $person->date_of_birth?->format('Y-m-d'),
            'marital_status' => $person->marital_status,
            'gender' => $person->gender,
            'nationality' => $person->nationality,
            'religion' => $person->religion,
            'place_of_birth' => $person->place_of_birth,
            'country_of_birth' => $person->country_of_birth,
            'about' => $person->about,
            'image' => $person->image ? Storage::disk('avatars')->url($person->image) : null,

        ];
        // return {
        //     // id => $person->id,
        // }//$person;
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        // return $request->validated();
        $person->update($request->validated());
        return redirect()->route('person.show', $person->id)->with('success', 'Person records updated');
    }

    public function addContact(Request $request, Person $person)
    {
        $attribute = $request->validate([
            'contact_type' => [new Enum(ContactTypeEnum::class)],
            'contact' => 'required|min:7|max:30',
        ]);
        $person->contacts()->create($attribute);
        return redirect()->back();
    }
    public function updateContact(Request $request,  $person, $contact)
    {  
        $attribute = $request->validate([
            'contact_type' => [new Enum(ContactTypeEnum::class)],
            'contact' => 'required|min:7|max:30',
            ]);
        $contact = Contact::find($contact)->update($attribute);
        return redirect()->back()->with('success', 'Contact updated');
    }

    public function addAddress(Request $request, Person $person)
    {
        $attribute = $request->validate([
            'address_line_1' => ['required'],
            'address_line_2' => ['nullable'],
            'city' => ['required'],
            'region' => ['nullable'],
            'country' => ['required'],
            'post_code' => ['nullable'],
        ]);

        $person->address()->where('valid_end', null)->update([
            'valid_end' => now(),
        ]);

        // $staff->ranks()->wherePivot('end_date', null)->update([
        //     'end_date' => Carbon::parse($request->start_date)->subDay(),
        // ]);

        $person->address()->create($attribute);

        return redirect()->back();
    }



    public function deleteAddress(Person $person, $address)
    {
        $person->address()->delete($address);

        return redirect()->back();
    }
}
