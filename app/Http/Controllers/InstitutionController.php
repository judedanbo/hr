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
                ->withCount('departments', 'divisions', 'units')
                ->paginate(10)
                ->through(fn($institution) => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'departments' =>$institution->departments_count,
                    'divisions' => $institution->divisions_count,
                    'units' => $institution->units_count,

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
                    ->with(['departments' => function ($deptQuery)  use($finalSub){
                        $deptQuery->joinSub($finalSub, 'final_sub', function($finalJoin){
                            $finalJoin->on('final_sub.id', '=' , 'units.id');
                        });
                    } ])
                    ->withCount('departments','divisions', 'units')
                ->first();

        return Inertia::render('Institution/Show', [
            'institution' => $institution != null? [
                'id' => $institution->id,
                'name' => $institution->name,
                'departments' => $institution->department_count,
                'divisions' => $institution->divisions_count,
                'units' => $institution->units_count
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
}
