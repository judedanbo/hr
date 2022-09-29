<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function PHPSTORM_META\type;

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
                        $query->orWhereRaw("monthname(date_of_birth) like ?", [$term]);
                    }
                })
                ->with('units', 'dependent')
                ->paginate(10)
                ->through(fn($person) => [
                    'id' => $person->id,
                    'name' => $person->full_name,
                    'gender' => $person ->gender,
                    'dob' => $person ->date_of_birth,
                    'ssn' => $person ->social_security_number,
                    'initials' => $person ->initials,
                    // 'number' => Person::count()
                    'unit' => $person->units->count() > 0 ? [
                        // $person->units->first()
                        // 'id' => $person->units->first()->id,
                        'staff_id' => $person->units->first()->staff->id,
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
        $person = Person::with(['address','contacts','units', 'dependent'])->whereId($person)->first();

        return Inertia::render('Person/Show', [
            'person' => [
                'id' => $person->id,
                'name' => $person->full_name,
                'dob' => $person->date_of_birth,
                'ssn' => $person->social_security_number,
                'initials' => $person->initials
            ],
            'contact_types' => ContactType::select(['id', 'name'])->get(),
            'contacts' => $person->contacts->count() > 0 ? $person->contacts->map(fn($contact)=>[
                'id' => $contact->id,
                'contact' => $contact->contact,
                'contact_type_id' => $contact->contact_type_id,
                'valid_end' => $contact->valid_end,
            ]):null,
            'address' => $person->address->count() > 0 ? [
                'id' => $person->address->first()->id,
                'address_line_1' => $person->address->first()->address_line_1,
                'address_line_2' => $person->address->first()->address_line_2,
                'city' => $person->address->first()->city,
                'region' => $person->address->first()->region,
                'country' => $person->address->first()->country,
                'post_code' => $person->address->first()->post_code,
                'valid_end' => $person->address->first()->valid_end,
            ]:null,
        ]);
    }

    public function addContact(Request $request, Person $person)
    {
        $attribute = $request->validate([
            'contact' => "required|min:7|max:30",
            'contact_type_id' => ['required','exists:contact_types,id'],
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
