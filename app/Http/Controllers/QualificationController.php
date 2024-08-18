<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use Inertia\Inertia;

class QualificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Qualification/Index', [
            'qualifications' => Qualification::query()
                ->with(['person.institution'])
                ->orderBy('created_at', 'desc')
                ->whereHas('person', function ($query) {
                    $query->whereHas('institution');
                })
                ->paginate()
                ->withQueryString()
                ->through(function ($qualification) {
                    return [
                        'id' => $qualification->id,
                        'person' => $qualification->person->full_name,
                        'staff_number' => $qualification->person->institution->first()->staff->staff_number,
                        'course' => $qualification->course,
                        'institution' => $qualification->institution,
                        'qualification' => $qualification->qualification,
                        'qualification_number' => $qualification->qualification_number,
                        'level' => $qualification->level,
                        'pk' => $qualification->pk,
                        'year' => $qualification->year,
                        'created_at' => $qualification->created_at,
                    ];
                }),
            'filters' => request()->all('search'),
        ]);
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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQualificationRequest $request)
    {
        Qualification::create($request->validated());

        return redirect()->back()->with('success', 'Qualification added.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Qualification $qualification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Qualification $qualification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQualificationRequest $request, Qualification $qualification)
    {
        if ($request->validated()) {
            $qualification->update($request->validated());

            return redirect()->back()->with('success', 'Qualification updated.');
        }

        return redirect()->back()->with('error', 'Qualification not updated.');
    }

    public function delete($qualification)
    {
        Qualification::find($qualification)->delete();

        return redirect()->back()->with('success', 'Qualification deleted.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Qualification $qualification)
    {
        //
    }
}
