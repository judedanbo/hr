<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Status;
use Inertia\Inertia;

class InstitutionStatusController extends Controller
{
    public function index($institution)
    {
        $statuses = Status::query()
            ->withCount('staff')
            ->with('institution')
            ->where('institution_id', $institution)
            ->get();

        // $institution->load('statuses');
        return $statuses;

        return Inertia::render('Status/Index', [
            'institution' => [
                'id' => $statuses, //->institution
                // ->first()->id,
                // 'name' => $statuses->institution->first()->name,
                // 'abbreviation' => $statuses->institution->first()->abbreviation,
            ],
            'statuses' => $statuses
                ->map(fn ($status) => [
                    'id' => $status->id,
                    'name' => $status->status->label(),
                    'description' => $status->description,
                    'start_date' => $status->start_date,
                    'end_date' => $status->end_date,
                    'staff' => $status->staff_count,
                    // ->map(fn ($person) => [
                    //     'id' => $person->id,
                    //     'person_id' => $person->person->id,
                    //     'name' => $person->person->full_name,
                    //     'staff_number' => $person->staff_number,
                    //     'file_number' => $person->file_number,
                    // ])
                ]),
            'filters' => request()->all('search'),
        ]);

        return $institution;
    }

    public function show(Institution $institution, Status $status)
    {
        $status->load(['staff', 'institution']);

        // return $status;
        return Inertia::render('Status/Show', [
            'institution' => [
                'id' => $status->institution->id,
                'name' => $status->institution->name,
                'abbreviation' => $status->institution->abbreviation,
            ],
            'status' => [
                'id' => $status->id,
                'name' => $status->status->label(),
                'description' => $status->description,
            ],
            'staff' => $status->staff,
            // ->map(fn ($person) => [
            //     'id' => $person->id,
            //     'person_id' => $person->person->id,
            //     'name' => $person->person->full_name,
            //     'staff_number' => $person->staff_number,
            //     'file_number' => $person->file_number,
            // ])
        ]);
        // $status;
    }
}
