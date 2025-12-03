<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PromotionServiceInterface;
use App\Contracts\Services\StaffManagementServiceInterface;
use App\Http\Requests\StaffAdvancedSearchRequest;
use App\Http\Requests\StoreInstitutionPersonRequest;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\StoreStaffPositionRequest;
use App\Http\Requests\UpdateStaffPositionRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\InstitutionPerson;
use App\Models\Position;
use App\Transformers\Staff\StaffDetailTransformer;
use App\Transformers\Staff\StaffListTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InstitutionPersonController extends Controller
{
    public function __construct(
        protected StaffManagementServiceInterface $staffManagementService,
        protected PromotionServiceInterface $promotionService,
        protected StaffDetailTransformer $detailTransformer,
        protected StaffListTransformer $listTransformer
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(StaffAdvancedSearchRequest $request)
    {
        if (Gate::denies('view all staff')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view all staff')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view all staff');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view all staff');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('view all staff')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed all staff');

        $staff = InstitutionPerson::query()
            ->active()
            ->with('person')
            ->currentUnit()
            ->currentRank()
            ->when($request->rank_id, fn ($q, $rankId) => $q->filterByRank($rankId))
            ->when($request->job_category_id, fn ($q, $categoryId) => $q->filterByJobCategory($categoryId))
            ->when($request->unit_id, fn ($q, $unitId) => $q->filterByUnit($unitId))
            ->when($request->department_id, fn ($q, $deptId) => $q->filterByDepartment($deptId))
            ->when($request->gender, fn ($q, $gender) => $q->filterByGender($gender))
            ->when($request->status, fn ($q, $status) => $q->filterByStatus($status))
            ->when($request->hire_date_from && $request->hire_date_to,
                fn ($q) => $q->filterByHireDateRange($request->hire_date_from, $request->hire_date_to))
            ->when($request->hire_date_from && ! $request->hire_date_to,
                fn ($q) => $q->filterByHireDateFrom($request->hire_date_from))
            ->when($request->hire_date_to && ! $request->hire_date_from,
                fn ($q) => $q->filterByHireDateTo($request->hire_date_to))
            ->when($request->age_from && $request->age_to,
                fn ($q) => $q->filterByAgeRange($request->age_from, $request->age_to))
            ->when($request->age_from && ! $request->age_to,
                fn ($q) => $q->filterByAgeFrom($request->age_from))
            ->when($request->age_to && ! $request->age_from,
                fn ($q) => $q->filterByAgeTo($request->age_to))
            ->search($request->search)
            ->paginate(10)
            ->withQueryString()
            ->through($this->listTransformer->transformForPagination());

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'filters' => $request->only([
                'search',
                'rank_id',
                'job_category_id',
                'unit_id',
                'department_id',
                'gender',
                'status',
                'hire_date_from',
                'hire_date_to',
                'age_from',
                'age_to',
            ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (request()->user()->cannot('create staff')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to add a new staff');
        }

        return Inertia::render('Staff/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstitutionPersonRequest $request)
    {
        if (request()->user()->cannot('create staff')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to add a new staff');
        }

        try {
            $staff = $this->staffManagementService->create($request->staffData);

            return redirect()->route('staff.show', ['staff' => $staff->id])
                ->with('success', 'Staff created successfully');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'institution' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InstitutionPerson  $institutionPerson
     * @return \Illuminate\Http\Response
     */
    public function show($staffId)
    {
        if (request()->user()->cannot('view staff')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view staff details');
        }
        if (request()->user()->isStaff()) {
            if (request()->user()->person->institution->first()->staff->id != $staffId) {
                return redirect()->route('dashboard')->with('error', 'You do not have permission to view details of this staff');
            }
        }
        $staff = InstitutionPerson::query()
            ->with(
                [
                    'person' => function ($query) {
                        $query->with([
                            'address' => function ($query) {
                                $query->whereNull('valid_end');
                            },
                            'contacts',
                            'identities' => function ($query) {
                                $query->withTrashed();
                            },
                            'qualifications',
                        ]);
                    },
                    // 'units.institution',
                    // 'units.parent',
                    'units' => function ($query) {
                        $query->with(['institution', 'parent']);
                    },
                    'ranks',
                    'dependents.person',
                    'statuses',
                    'notes.documents',
                    'positions' => function ($query) {
                        $query->withTrashed();
                    },
                ]
            )
            ->active()
            ->whereId($staffId)
            ->first();
        if (! $staff) {
            return redirect()->route('person.show', ['person' => $staffId])->with('error', 'Staff not found');
        }

        // dd($request->session()->all());
        // request()->session()->reflash();
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
                'maiden_name' => $staff->person->maiden_name,
                'dob-value' => $staff->person->date_of_birth,
                'dob' => $staff->person->date_of_birth?->format('d M Y'),
                'age' => $staff->person->age . ' years old',
                'gender' => $staff->person->gender?->label(),
                'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'nationality' => $staff->person->nationality?->nationality(),
                'religion' => $staff->person->religion,
                'marital_status' => $staff->person->marital_status?->label(),
                'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
                'identities' => $staff->person->identities->count() > 0 ? $staff->person->identities->map(fn ($id) => [
                    'id' => $id->id,
                    'id_type' => $id->id_type,
                    'id_type_display' => $id->id_type->label(),
                    'id_number' => $id->id_number,
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
                'hire_date' => $staff->hire_date?->format('d M Y'),
                'hire_date_display' => $staff->hire_date?->diffForHumans(),
                'retirement_date' => $staff->person->date_of_birth?->addYears(60)->format('d M Y'),
                'retirement_date_display' => $staff->person->date_of_birth?->addYears(60)->diffForHumans(),
                'start_date' => $staff->start_date?->format('d M Y'),
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
                'positions' => $staff->positions?->map(fn ($position) => [
                    'id' => $position->id,
                    'name' => $position->name,
                    'start_date' => $position->pivot->start_date,
                    'end_date' => $position->pivot->end_date,
                    'start_date_display' => $position->pivot->start_date ? Carbon::parse($position->pivot->start_date)->format('d M Y') : null,
                    'end_date_display' => $position->pivot->end_date ? Carbon::parse($position->pivot->end_date)->format('d M Y') : null,
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
                    'url' => $note->documents->count() > 0 ? $note->documents->map(fn ($doc) => [
                        'document_type' => $doc->document_type,
                        'document_title' => $doc->document_title,
                        // 'document_status' => $doc->document_status,
                        // 'document_number' => $doc->document_number,
                        'file_name' => $doc->file_name,
                        'file_type' => $doc->file_type,
                    ]) : null,

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
                    'initials' => $dep->person->initials,
                    'title' => $dep->person->title,
                    'name' => $dep->person->full_name,
                    'surname' => $dep->person->surname,
                    'first_name' => $dep->person->first_name,
                    'other_names' => $dep->person->other_names,
                    'maiden_name' => $dep->person->maiden_name,
                    'nationality' => $dep->person->nationality?->label(),
                    'nationality_form' => $dep->person->nationality,
                    'marital_status' => $dep->person->marital_status,
                    'image' => $dep->person->image ? '/storage/' . $dep->person->image : null,
                    'religion' => $dep->person->religion,
                    'gender' => $dep->person->gender?->label(),
                    'gender_form' => $dep->person->gender,
                    'date_of_birth' => $dep->person->date_of_birth?->format('Y-m-d'),
                    'age' => $dep->person->age . ' years old',
                    'relation' => $dep->relation,
                    'staff_id' => $staff->id,
                    // $staff->person->image ? '/' . $staff->person->image :  null,
                    'image' => $dep->person->image ? '/storage/' . $dep->person->image : null,
                    'contacts' => $dep->person->contacts->map(fn ($contact) => [
                        'id' => $contact->id,
                        'type' => $contact->contact_type?->label(),
                        'contact' => $contact->contact,
                    ]),
                ]) : null,
            ],
        ]);
    }

    public function promote(Request $request, InstitutionPerson $staff)
    {
        if (request()->user()->cannot('create staff promotion')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to add a new promotion');
        }

        $request->validate([
            'rank_id' => ['required', 'exists:jobs,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->promotionService->promote($staff, $request->rank_id, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Staff promoted successfully');
    }

    public function promotions(InstitutionPerson $staff)
    {
        if (request()->user()->cannot('view staff promotion')) {
            return ['error', 'You do not have permission to view promotions'];
        }
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
                'distance' => (int) $rank->pivot->start_date->diffInYears(),
            ])
                : null,
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
        if (request()->user()->cannot('update staff')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to edit this staff\'s details');
        }
        $staff->load('person');

        return [
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
                'maiden_name' => $staff->person->maiden_name,
                'date_of_birth' => $staff->person->date_of_birth?->format('Y-m-d'),
                'place_of_birth' => $staff->person->place_of_birth,
                'country_of_birth' => $staff->person->country_of_birth,
                'gender' => $staff->person->gender,
                'marital_status' => $staff->person->marital_status,
                'religion' => $staff->person->religion,
                'nationality' => $staff->person->nationality,
                'ethnicity' => $staff->person->ethnicity,
                'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
                'about' => $staff->person->about,
            ],
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStaffRequest $request, InstitutionPerson $staff)
    {
        if (request()->user()->cannot('update staff')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to edit this staff\'s details');
        }

        $validated = $request->validated();
        $this->staffManagementService->update($staff, $validated['staffData']);

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
        if (request()->user()->cannot('create staff notes')) {
            return redirect()->back()->with('error', 'You do not have permission to add a new note');
        }
        // dd($request->all());
        // dd()
        // $files = [];
        // // if ($request->hasFile('document')) {
        // foreach ($request->document as $file) {
        //     // dd($file);
        //     // if($file->file)
        //     $files[] = Storage::disk('documents')->put('notes', $file['file']);; //$file->store('notes');
        //     // $files[] = $file->store('notes');
        // }
        // }
        // dd($files);
        //  Storage::disk('documents')->put('notes', $request->image);

        $validated = $request->validated();

        // foreach ($validated)

        $validated['created_by'] = auth()->user()->id;
        $staff->writeNote($validated);

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    public function assignPosition(StoreStaffPositionRequest $request, InstitutionPerson $staff)
    {
        if (request()->user()->cannot('create staff position')) {
            return redirect()->back()->with('error', 'You do not have permission to add a new position');
        }
        $validated = $request->validated();
        DB::transaction(function () use ($staff, $validated) {
            Position::withTrashed()
                ->find($validated['position_id'])
                ->staff()->wherePivot('end_date', null)
                ->each(function ($st) use ($validated) {
                    $st->positions()->updateExistingPivot($validated['position_id'], ['end_date' => Carbon::now()]);
                });
            $staff->positions()->attach($validated['position_id'], [
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]);
        });

        return redirect()->back()->with('success', 'Position assigned successfully.');
    }

    public function updatePosition(UpdateStaffPositionRequest $request, InstitutionPerson $staff)
    {
        if (request()->user()->cannot('update staff position')) {
            return redirect()->back()->with('error', 'You do not have permission to update this position');
        }
        $validated = $request->validated();
        $staff->positions()->syncWithPivotValues($validated['position_id'], [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Position updated successfully.');
    }

    public function deletePosition(InstitutionPerson $staff)
    {
        if (request()->user()->cannot('delete staff position')) {
            return redirect()->back()->with('error', 'You do not have permission to delete this position');
        }
        $validated = request()->validate([
            'position_id' => ['required', 'exists:positions,id'],
        ]);
        $staff->positions()->detach(request('position_id'));

        return redirect()->back()->with('success', 'Position updated successfully.');
    }
}
