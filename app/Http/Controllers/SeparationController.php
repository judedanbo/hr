<?php

namespace App\Http\Controllers;

use App\Enums\Identity;
use App\Models\InstitutionPerson;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SeparationController extends Controller
{
    public function index()
    {
        $separated = InstitutionPerson::query()
            ->retired()
            // ->where('status')
            ->with(['person' => function ($query) {
                $query->with(['contacts', 'identities']);
            }, 'statuses', 'notes'])
            ->currentUnit()
            ->currentRank()
            ->search(request()->search)
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($staff) => [
                'id' => $staff->person_id,
                'note' => [
                    'date' => $staff->notes->first()?->note_date,
                    'note' => $staff->notes->first()?->note,
                    'type' => $staff->notes->first()?->note_type->label(),
                ],
                'statuses' => $staff->statuses->map(function ($status) {
                    return [
                        'stat' => $status,
                        'status' => $status->status->label(),
                        'start_date' => $status->start_date?->format('d M Y'),
                        'end_date' => $status->end_date?->format('d M Y'),
                        'remarks' => $status->remarks,
                        'description' => $status->description,
                    ];
                }),
                'file_number' => $staff->file_number,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'hire_date' => $staff->hire_date?->format('d M Y'),
                'hire_date_distance' => $staff->hire_date?->diffForHumans(),
                'initials' => $staff->person->initials,
                'name' => $staff->person->full_name,
                'gender' => $staff->person->gender?->label(),
                'dob' => $staff->person->date_of_birth?->format('d M Y'),
                'image' => $staff->person->image ? Storage::disk('avatars')->url($staff->person->image) : null,
                'dob_distance' => $staff->person->date_of_birth?->diffInYears() . ' years old',
                'retirement_date' => $staff->person->date_of_birth?->addYears(60)->format('d M Y'),
                'retirement_date_distance' => $staff->person->date_of_birth?->addYears(60)->diffForHumans(),
                'ghana_card' => $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
                'contact' => $staff->person->contacts->count() > 0 ? $staff->person->contacts->map(function ($item) {
                    return $item->contact;
                })->implode(', ') : '',
                'current_rank' => $staff->currentRank ? [
                    'id' => $staff->currentRank?->id,
                    'name' => $staff->currentRank?->job?->name,
                    'job_id' => $staff->currentRank->name,
                    'start_date' => $staff->currentRank->start_date?->format('d M Y'),
                    'start_date_distance' => $staff->currentRank->start_date?->diffForHumans(),
                    'end_date' => $staff->currentRank->end_date?->format('d M Y'),
                    'remarks' => $staff->currentRank->remarks,
                ] : null,

            ]);

        // dd($separated);
        return Inertia::render('Separation/Index', [
            'separated' => $separated,
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show()
    {
        return Inertia::render('Separation/Show', []);
    }
}
