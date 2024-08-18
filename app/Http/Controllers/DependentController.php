<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDependentRequest;
use App\Http\Requests\UpdateDependentRequest;
use App\Models\Dependent;
use App\Models\Person;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DependentController extends Controller
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
        return Inertia::render(
            'Dependents/Create',
            []

        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDependentRequest $request)
    {
        $person = $request->validated();
        if ($request->hasFile('image')) {
            $avatar = Storage::disk('avatars')->put('/', $request->image);
            $person['image'] = $avatar;
        }

        $newPerson = Person::create($person);
        Dependent::create([
            'staff_id' => $request->validated()['staff_id'],
            'person_id' => $newPerson->id,
            'relation' => $request->validated()['relation'],
        ]);

        return redirect()->back()->with('success', 'Dependent created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Dependent $dependent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Dependent $dependent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDependentRequest $request, Dependent $dependent)
    {
        // return $dependent->person;
        $path = '';
        $avatar = null;
        if ($request->hasFile('image')) {

            $avatar = Storage::disk('avatars')->put('/', $request->image);
        }
        $dependent->person()->update([
            'title' => $request->title,
            'surname' => $request->surname,
            'first_name' => $request->first_name,
            'other_names' => $request->other_names,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'religion' => $request->religion,
            'nationality' => $request->nationality,
            'image' => $avatar,
        ]);
        $dependent->update($request->validated());

        return redirect()->back()->with('success', 'Dependent updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dependent $dependent)
    {
        $dependent->delete();

        return redirect()->back();
    }
}
