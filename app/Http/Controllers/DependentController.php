<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDependentRequest;
use App\Http\Requests\UpdateDependentRequest;
use App\Models\Dependent;
use App\Models\Person;
use Exception;
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
            $path = $request->file('image')->store('public/images');
            $person['image'] = $request->file('image')->hashName();
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
        //
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