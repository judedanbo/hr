<?php

namespace App\Http\Controllers;

use App\Enums\ContactType as EnumsContactType;
use App\Models\ContactType;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use Illuminate\Http\Request;
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
        // return Person::with('institution')->first();
        return Inertia::render('Person/Index', [
            'people' => Person::query()
                ->when(request()->search, function ($query, $search) {
                    $terms =  explode(" ", $search);
                    foreach ($terms as $term) {
                        $query->where('surname', 'like', "%{$term}%");
                        $query->orWhere('first_name', 'like', "%{$term}%");
                        $query->orWhere('other_names', 'like', "%{$term}%");
                        $query->orWhere('date_of_birth', 'like', "%{$term}%");
                        // $query->orWhere('social_security_number', 'like', "%{$term}%");
                        $query->orWhereRaw("monthname(date_of_birth) like ?", [$term]);
                    }
                })
                ->with('institution', 'dependent', 'identities')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($person) => [
                    'id' => $person->id,
                    'name' => $person->full_name,
                    'gender' => $person->gender->name,
                    'dob' => $person->date_of_birth,
                    'ssn' => $person->identities->first()?->id_number,
                    'initials' => $person->initials,
                    // 'number' => Person::count()
                    'institution' => $person->institution ? [
                        'id' =>  $person->institution->first()->id,
                        'name' =>  $person->institution->first()->name,
                        'status' =>  $person->institution->first()->staff->statuses->first()->status->name,
                        // $person->units->first()
                        // 'id' => $person->units->first()->id,
                        'staff_id' => $person->institution->first()->staff->id,
                        // 'name' => $person->units->first()->staff
                    ] : null,
                    'dependent' => $person->dependent ? [
                        'staff_id' => $person->dependent->staff_id,
                        // 'institution' => $person->dependent->institution_id,
                        // 'name' => $person->dependent->name
                    ] : null,
                ]),
            'contact_types' => [],
            'filters' => ['search' => request()->search],
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Http\Response
     */
    public function show($person)
    {
        $person = Person::with(['address', 'contacts', 'dependent'])->whereId($person)->first();

        return Inertia::render('Person/Show', [
            'person' => [
                'id' => $person->id,
                'name' => $person->full_name,
                'dob' => $person->date_of_birth,
                // 'ssn' => $person->social_security_number,
                'initials' => $person->initials
            ],
            'contact_types' => EnumsContactType::cases(),
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
        ]);
    }

    public function addContact(Request $request, Person $person)
    {
        $attribute = $request->validate([
            'contact' => "required|min:7|max:30",
            'contact_type_id' => ['required', 'exists:contact_types,id'],
        ]);

        $person->contacts()->create($attribute);
        return redirect()->back();
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

        $person->address()->create($attribute);
        return redirect()->back();
    }

    public function deleteAddress(Person $person, $address)
    {
        $person->address()->delete($address);
        return redirect()->back();
    }
}