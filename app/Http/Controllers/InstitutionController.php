<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function index()
    {
        return Inertia::render('Institution/Index', [
            'institutions' => Institution::query()
                ->when(request()->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                    $query->orWhere('abbreviation', 'like', "%{$search}%");
                })
                ->withCount('departments', 'divisions', 'units', 'staff')
                ->whereNull('end_date')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($institution) => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'start_date' => $institution->start_date,
                    'end_date' => $institution->end_date,
                    'abbreviation' => $institution->abbreviation,
                    'status' => $institution->status,
                    'departments' => $institution->departments_count,
                    'divisions' => $institution->divisions_count,
                    'units' => $institution->units_count,
                    'staff' => $institution->staff_count,

                ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function department($institution)
    {
        $institution = Institution::query()
            ->where('id', $institution)
            ->when(request()->searchDep, function ($query, $searchValue) {
                $query->with(['departments', function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%");
                }]);
            }, function ($query) {
                $query->with('departments');
            })
            ->get();
        $institution = $institution->first();

        return Inertia::render('Institution/Dept', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
            ],
            'departments' => $institution->departments != null && $institution->departments->count() > 0 ?
                $institution->departments->map(fn ($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])
                : null,
            'filters' => ['searchDep' => request()->searchDep],
        ]);
    }

    public function show($institution)
    {
        $institution = Institution::query()
            ->where('id', $institution)
            ->withCount([
                'departments',
                'divisions',
                'units',
                'staff',
            ])
            ->first();

        $departments = Unit::query()
            ->with(['subs' => function ($query) {

                $query->withCount('subs');
                $query->withCount('staff');
            }])
            ->withCount('subs', 'staff')
            ->where('units.type', 'DEP')
            ->get();
        // return $departments;
        return Inertia::render('Institution/Show', [
            'institution' => $institution != null ? [
                'id' => $institution->id,
                'name' => $institution->name,
                'start_date' => $institution->start_date,
                'end_date' => $institution->end_date,
                'departments' => $institution->departments_count,
                'divisions' => $institution->divisions_count,
                'units' => $institution->units_count,
                'staff' => $institution->staff_count,
            ] : null,
            // 'departments' => [],
            'departments' => $departments != null && $departments->count() > 0 ?
                $departments->map(fn ($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'type' => $department->type,
                    'start_date' => $department->start_date,
                    'end_date' => $department->end_date,
                    'unit_id' => $department->unit_id,
                    'units' => $department->subs_count, // + $department->subs->sum('subs_count'),
                    'staff' => $department->staff_count + $department->subs->sum('staff_count'),
                    // 'units' => $department->subs->sum('subs_count'),
                ])
                : null,
            'filters' => ['search' => request()->search],
        ]);
    }

    public function store(StoreInstitutionRequest $request)
    {
        $institution = Institution::create($request->validated());

        return redirect()->route('institution.show', ['institution' => $institution->id]);
    }

    public function update(UpdateInstitutionRequest $request)
    {
        // return $request;
        Institution::whereId($request->id)->update($request->validated());

        return redirect()->route('institution.show', ['institution' => $request->id]);
    }

    public function delete(Institution $institution)
    {
        // return $institution;
        $institution->delete();

        return redirect()->route('institution.index');
    }

    public function staffs($institution)
    {
        $people = DB::table('people')
            ->select('id')
            ->where('surname', 'like', '%' . request()->search . '%')
            ->orWhere('first_name', 'like', '%' . request()->search . '%')
            ->orWhere('other_names', 'like', '%' . request()->search . '%')
            ->orWhere('date_of_birth', 'like', '%' . request()->search . '%')
            ->orWhereRaw('monthname(date_of_birth) like ?', [request()->search]);
        $institution = Institution::query()
            ->where('id', $institution)
            ->when(request()->search, function ($query, $searchValue) use ($people) {
                $query->with(
                    [
                        'staff' => function ($q) use ($searchValue, $people) {
                            $q->where('institution_person.staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('institution_person.old_staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('institution_person.hire_date', 'like', "%{$searchValue}%");
                            $q->orWhereRaw('monthname(institution_person.hire_date) like ?', [$searchValue]);

                            $q->orWhereIn('institution_person.person_id', $people);

                            $q->with(['units', 'person', 'ranks']);
                            $q->paginate();
                        },
                    ]
                );
                $query->withCount(
                    [
                        'staff' => function ($q) use ($searchValue, $people) {
                            $q->where('institution_person.staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('institution_person.old_staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('institution_person.hire_date', 'like', "%{$searchValue}%");
                            $q->orWhereRaw('monthname(institution_person.hire_date) like ?', [$searchValue]);

                            $q->orWhereIn('institution_person.person_id', $people);
                        },
                    ]
                );
                $query->paginate();
            }, function ($query) {
                $query->with(['staff' => function ($q) {
                    $q->with(['person', 'units', 'ranks']);
                    $q->paginate();
                }]);
                $query->withCount('staff');
            })
            // ->withCount('staff')
            ->first();

        // dd($institution);

        return Inertia::render('Institution/Staffs', [
            'staff' => $institution->staff ? $institution->staff->map(fn ($stf) => [
                'staff_id' => $stf->id,
                'staff_number' => $stf->staff_number,
                'old_staff_number' => $stf->old_staff_number,
                'hire_date' => $stf->hire_date,
                'name' => $stf->person->full_name,
                'email' => strtolower(explode(' ', $stf->person->first_name)[0]) . '.' . strtolower(explode(' ', $stf->person->surname)[0]) . '@audit.gov.gh',
                'other_names' => $stf->person->other_names,
                'dob' => $stf->person->date_of_birth,
                // 'ssn' => $stf->person->social_security_number,
                'initials' => $stf->person->initials,
                'current_job' => $stf->ranks[0]->name,
                'current_job_id' => $stf->ranks[0]->id,
                'units' => $stf->units?->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ]), //? [
                //     'id' => $stf->unit->id,
                //     'name' => $stf->unit->name
                // ] : null
            ]) : null,
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'staff' => $institution->staff_count,
            ],
            'filters' => [
                'search' => request()->search,
                'page' => request()->page,
            ],
        ]);
    }

    public function staff($institution, $staff)
    {

        $institution = Institution::query()
            ->where('id', $institution)
            ->with(['staff' => function ($query) use ($staff) {
                $query->where('institution_person.id', $staff);
                $query->with(['person.address', 'unit', 'jobs', 'dependents.person']);
            }])
            ->first();

        //  dd($institution->staff);
        $staff = $institution ? $institution->staff->first() : null;

        return Inertia::render('Institution/Sta', [
            'person' => [
                'id' => $staff->person->id,
                'name' => $staff->person->full_name,
                'dob' => $staff->person->date_of_birth,
                'gender' => $staff->person->gender,
                // 'ssn' => $staff->person->social_security_number,
                'initials' => $staff->person->initials,
                'address' => $staff->person->address->count() ? [
                    'id' => $staff->person->address->first()->id,
                    'address_line_1' => $staff->person->address->first()->address_line_1,
                    'address_line_2' => $staff->person->address->first()->address_line_2,
                    'city' => $staff->person->address->first()->city,
                    'region' => $staff->person->address->first()->region,
                    'country' => $staff->person->address->first()->country,
                    'post_code' => $staff->person->address->first()->post_code,
                    'valid_end' => $staff->person->address->first()->valid_end,
                ] : null,
            ],
            'staff' => $institution->staff ?
                [
                    'staff_id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'old_staff_number' => $staff->old_staff_number,
                    'hire_date' => $staff->hire_date,

                    'email' => strtolower(explode(' ', $staff->person->other_names)[0]) . '.' . strtolower(explode(' ', $staff->person->surname)[0]) . '@audit.gov.gh',

                    'jobs' => $staff->jobs->count() > 0 ? $staff->jobs->map(fn ($job) => [
                        'id' => $job->id,
                        'name' => $job->name,

                        'end_date' => $job->pivot->end_date,
                    ]) : null,

                    'unit' => $staff->unit ? [
                        'id' => $staff->unit->id,
                        'name' => $staff->unit->name,
                    ] : null,
                    'dependents' => $staff->dependents ? $staff->dependents->map(fn ($dep) => [
                        'id' => $dep->id,
                        'person_id' => $dep->person_id,
                        'name' => $dep->person->full_name,
                        'gender' => $dep->person->gender,
                        'dob' => $dep->person->date_of_birth,
                        'relation' => $dep->relation,
                    ]) : null,
                ] : null,
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
            ],
            'filters' => [
                'search' => request()->search,
            ],
        ]);
    }

    public function jobs($institution)
    {
        $institution = Institution::with(['jobs' => function ($query) {
            $query->when(request()->search, function ($q) {
                $q->where('name', 'like', '%' . request()->search . '%');
            });
            $query->withCount('staff');
        }])
            ->where('id', $institution)
            ->first();

        return Inertia::render('Institution/Jobs', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
            ],
            'jobs' => $institution->jobs->map(fn ($job) => [
                'id' => $job->id,
                'name' => $job->name,
                'staff' => $job->staff_count,
            ]),
            'filters' => ['search' => request()->search],
        ]);
    }
}