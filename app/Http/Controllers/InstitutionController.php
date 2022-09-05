<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function index()
    {
        // return Institution::query()->where('id', 21)->with('divisions')->get();
        return Inertia::render('Institution/Index', [
            'institutions' => Institution::query()
                ->when(request()->search, function($query, $search){
                    $query->where('name', 'like', "%{$search}%");
                })
                ->withCount('departments', 'divisions', 'units', 'staff')
                ->paginate(10)
                ->through(fn($institution) => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'departments' =>$institution->departments_count,
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
            ->where('id',$institution)
            ->when(request()->searchDep, function($query, $searchValue){
                $query->with(['departments', function($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%");
                }]);
            }, function($query) {
                $query->with('departments');
            } )
            ->get();
        $institution = $institution->first();
        // dd($institution);
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
        // return Institution::with('staff')->where('id', $institution)->get();
        // $institution = Institution::query()
        //     ->where('id', $institution)
        //     ->when(request()->search, function($query, $search){
        //         $query->with(['departments' =>  function($q) use ($search){
        //             $q->where('name', 'like', "%{$search}%");
        //             // $q->withCount('divisions');
        //         }, ]);

        //     }, function($query){
        //         $query->with('departments');
        //     })
        //     ->with('departments.subs')
        //     ->withCount('departments', 'divisions', 'units')
        //     ->first();
            // dd($institution);
        $divisionUnits = DB::table('units as finalUnits')
                ->join('units as divisions', 'divisions.id', '=', 'finalUnits.unit_id')
                ->select('divisions.id', 'divisions.unit_id', DB::raw('count(finalUnits.id) as units_count') )
                ->where('finalUnits.type', 3)
                ->groupBy('divisions.id')
               ;

        $finalSub =  DB::table('units as departments')
                // ->where('id',$institution )
                ->select('departments.institution_id','departments.id', 'departments.name', DB::raw('count(division_units.units_count) as total_divisions, sum(division_units.units_count) as total_units'))
                ->joinSub($divisionUnits, 'division_units', function ($join) {
                    $join->on('division_units.unit_id', '=', 'departments.id');
                })
                ->groupBy('departments.id', 'departments.name');
            // return $finalSub->get();

               $institution =  Institution::query()
                    ->where('institutions.id', $institution)
                    // ->with('departments')
                    // ->joinSub($finalSub, 'final_sub', function($finalJoin) {
                    //     $finalJoin->on('final_sub.institution_id', '=' , 'institutions.id');
                    // })
                    ->when(request()->search, function($query, $search) use($finalSub){
                        $query->with(['departments' =>  function($q) use ($search, $finalSub){
                            $q->joinSub(
                                $finalSub, 'final_sub', function($finalJoin){
                                    $finalJoin->on('final_sub.id', '=' , 'units.id');
                                }
                            );
                            $q->where('units.name', 'like', "%{$search}%");

                        } ]);
                    }, function($query) use($finalSub) {
                        $query->with(
                            [
                                'departments' => function ($deptQuery)  use($finalSub){
                                $deptQuery->joinSub(
                                    $finalSub, 'final_sub', function($finalJoin){
                                        $finalJoin->on('final_sub.id', '=' , 'units.id');
                                    }
                                );
                                }
                            ]
                        );
                    })
                    ->withCount('departments','divisions', 'units', 'staff')
                ->first();

        return Inertia::render('Institution/Show', [
            'institution' => $institution != null? [
                'id' => $institution->id,
                'name' => $institution->name,
                'departments' => $institution->departments_count,
                'divisions' => $institution->divisions_count,
                'units' => $institution->units_count,
                'staff' => $institution->staff_count
            ] : null,
            'departments' => $institution->departments != null && $institution->departments->count() > 0 ?
                $institution->departments->map(fn ($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'divisions' => $department->total_divisions,
                    'units' => $department->total_units,
                ])
             : null,
            'filters' => ['search' => request()->search],
        ]);
    }

    public function staff($institution)
    {
        $people = DB::table('people')
            ->select('id')
            ->where('surname', 'like', "%".request()->search."%")
           ->orWhere('other_names', 'like', "%". request()->search ."%")
           ->orWhere('date_of_birth', 'like', "%". request()->search ."%")
           ->orWhere('social_security_number', 'like', "%". request()->search ."%")
           ->orWhereRaw("monthname(date_of_birth) like ?",[request()->search]);
        $institution = Institution::query()
            ->where('id',$institution)
            ->when(request()->search, function($query, $searchValue) use ($people){
                $query->with(
                    [
                        'staff' => function($q) use ($searchValue, $people) {
                            $q->where('person_unit.staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('person_unit.old_staff_number', 'like', "%{$searchValue}%");
                            $q->orWhere('person_unit.hire_date', 'like', "%{$searchValue}%");
                            $q->orWhereRaw("monthname(person_unit.hire_date) like ?",[$searchValue]);
                            $q->orWhereRaw("monthname(person_unit.start_date) like ?",[$searchValue]);
                            $q->orWhereIn('person_unit.person_id', $people);

                            $q->with(['unit', 'person']);
                            $q->paginate();
                        }
                    ]
                );
                $query->withCount(['staff' => function($q) use ($searchValue, $people){
                        $q->where('person_unit.staff_number', 'like', "%{$searchValue}%");
                        $q->orWhere('person_unit.old_staff_number', 'like', "%{$searchValue}%");
                        $q->orWhere('person_unit.hire_date', 'like', "%{$searchValue}%");
                        $q->orWhereRaw("monthname(person_unit.hire_date) like ?",[$searchValue]);
                        $q->orWhereRaw("monthname(person_unit.start_date) like ?",[$searchValue]);
                        $q->orWhereIn('person_unit.person_id', $people);
                        }
                    ]
                    );
                $query->paginate();

            }, function($query) {
                $query->with(['staff' => function($q) {
                    $q->with(['person', 'unit']);
                    $q->paginate();
                }]);
                $query->withCount('staff');
            } )
            // ->withCount('staff')
            ->first();

        //  dd($institution->total);

        return Inertia::render('Institution/Staff', [
            'staff' =>  $institution->staff ? $institution->staff->map(fn($stf) =>
            [
                'staff_id' => $stf->id,
                'staff_number' => $stf->staff_number,
                'old_staff_number' => $stf->old_staff_number,
                'hire_date' => $stf->hire_date,
                'start_date' => $stf->start_date,
                'name' => $stf->person->full_name,
                'email' => strtolower(explode(' ', $stf->person->other_names)[0]) . '.'. strtolower(explode(' ', $stf->person->surname)[0]) . '@audit.gov.gh',
                'other_names' => $stf->person->other_names,
                'dob' => $stf->person->date_of_birth,
                'ssn' => $stf->person->social_security_number,
                'initials' => $stf->person->initials,
                'unit' => $stf->unit? [
                    'id' => $stf->unit->id,
                    'name' => $stf->unit->name
                ] : null
            ]): null,
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
}