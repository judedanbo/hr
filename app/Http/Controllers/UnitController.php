<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index($institution = null)
    {
        $types = [];
        $first  = new \stdClass();
        $first->value = null;
        $first->label = 'Select Unit Type';
        array_push($types, $first);
        foreach (UnitType::cases() as $type) {
            $temp = new \stdClass();
            $temp->value = $type->value;
            $temp->label = $type->name;
            array_push($types, $temp);
        }
        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                ->departments()
                ->with([
                    'institution',
                    'subs' => function ($query) {
                        $query->withCount([
                            'staff' => function ($query) {
                                $query->whereHas('statuses', function ($query) {
                                    $query->whereNull('end_date');
                                    $query->where('status', 'A');
                                });
                            },
                            'subs'
                        ]);
                    }
                ])
                ->withCount(
                    [
                        'staff' => function ($query) {
                            $query->whereHas('statuses', function ($query) {
                                $query->whereNull('end_date');
                                $query->where('status', 'A');
                            });
                        },
                        'subs'
                    ]
                )
                // ->countSubs()
                ->when(request()->institution, function ($query, $search) {
                    $query->where('institution_id', request()->institution);
                })
                ->when(request()->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(10)
                ->withQueryString()
                ->through(
                    fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'staff' => $unit->staff_count + $unit->subs->sum('staff_count'),
                        'units' => $unit->subs_count,
                        'institution' => $unit->institution ? [
                            'id' => $unit->institution->id,
                            'name' => $unit->institution->name,
                        ] : null,
                        'subs' => $unit->subs->count() > 0 ? [
                            $unit->subs->map(fn ($sub) => [
                                'id' => $sub->id,
                                'name' => $sub->name,
                            ]),
                        ] : null,
                    ]
                ),
            'unit_types' => $types,
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show($unit)
    {

        $unit = Unit::query()
            ->with([
                'institution', 'parent',
                'staff' => function ($query) {
                    $query->with(['person', 'ranks', 'units']);
                    $query->whereHas('statuses', function ($query) {
                        $query->whereNull('end_date');
                        $query->where('status', 'A');
                    });
                },
                'subs' => function ($query) {
                    $query->with(['staff' => function ($query) {
                        $query->with(['person', 'ranks', 'units']);
                        $query->whereHas('statuses', function ($query) {
                            $query->whereNull('end_date');
                            $query->where('status', 'A');
                        });
                    }]);
                    $query->withCount([
                        'staff' => function ($query) {
                            $query->whereHas('statuses', function ($query) {
                                $query->whereNull('end_date');
                                $query->where('status', 'A');
                            });
                        },
                        'subs'
                    ]);
                }
            ])
            // ->when(request()->search, function ($query, $search) {
            //     $query->whereHas('staff', function ($q) use ($search) {
            //         $q->whereHas('person', function ($per) use ($search) {
            //             $terms = explode(' ', $search);
            //             foreach ($terms as $term) {
            //                 $per->where('surname', 'like', "%{$search}%");
            //                 $per->orWhere('first_name', 'like', "%{$term}%");
            //                 $per->orWhere('other_names', 'like', "%{$term}%");
            //                 $per->orWhere('date_of_birth', 'like', "%{$term}%");
            //                 $per->orWhereRaw("monthname(date_of_birth) like '%{$term}%'");
            //             }
            //         });
            //     });
            //     $query->withCount(['staff' => function ($q) use ($search) {
            //         $q->withCount(['person' => function ($per) use ($search) {
            //             $per->where('surname', 'like', "%{$search}%");
            //             $per->orWhere('first_name', 'like', "%{$search}%");
            //             $per->orWhere('other_names', 'like', "%{$search}%");
            //             $per->orWhere('date_of_birth', 'like', "%{$search}%");
            //             $per->orWhereRaw("monthname(date_of_birth) like '%{$search}%'");
            //         }]);
            //     }]);
            // }, function ($query) {
            //     $query->withCount([
            //         'subs',
            //         'staff' => function ($query) {
            //             $query->whereHas('statuses', function ($hasQuery) {
            //                 $hasQuery->whereNull('end_date');
            //                 $hasQuery->where('status', 'A');
            //             });
            //         }
            //     ]);
            // })
            ->withCount([
                'subs',
                'staff' => function ($query) {
                    $query->whereHas('statuses', function ($hasQuery) {
                        $hasQuery->whereNull('end_date');
                        $hasQuery->where('status', 'A');
                    });
                }
            ])
            ->whereId($unit)
            ->first();
        // return $unit->subs;
        // $filtered = $unit->staff->filter(function ($value) {
        //     return $value->person !== null &&  $value->person?->date_of_birth->diffInYears(Carbon::now()) < 60;
        // });
        $sub_staff  = $unit->subs->map(fn ($sub) => $sub->staff)->flatten(1);
        // return $sub_staff;
        $allStaff = $unit->staff->merge($sub_staff)->flatten(1);
        // return $allStaff;
        return Inertia::render('Unit/Show', [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'staff_number' => $unit->subs ? $unit->staff_count + $unit->subs->sum('staff_count') : $unit->staff_count,
                'institution' => $unit->institution ? [
                    'name' => $unit->institution->name,
                    'id' => $unit->institution->id,
                ] : null,
                'parent' => $unit->parent ? [
                    'name' => $unit->parent->name,
                    'id' => $unit->parent->id,
                ] : null,
                // 'subs' => $unit->subs ? $unit->subs->map(fn ($sub) => [
                //     'id' => $sub->id,
                //     'name' => $sub->name,
                //     'subs' => $sub->subs_count,
                //     'staff_count' => $sub->staff_count,
                //     'staff' => $sub->staff ? $sub->staff->map(fn ($stafff) => [
                //         'id' => $stafff->id,
                //         'name' => $stafff->full_name,
                //         'dob' => $stafff->date_of_birth,
                //         'ssn' => $stafff->social_security_number,
                //         'initials' => $stafff->initials
                //     ]) : null,
                // ]) : null,
                'type' => $unit->type->label(),
                'staff' => $allStaff->map(fn ($staff) => [
                    'id' => $staff->person->id,
                    'name' => $staff->person->full_name,
                    'dob' => $staff->person->date_of_birth?->format('d M Y'),
                    'initials' => $staff->person->initials,
                    'hire_date' => $staff->hire_date->format('d M Y'),
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'image' => $staff->person->image,
                    'rank' => $staff->ranks->count() > 0 ? [
                        'id' => $staff->ranks->first()->id,
                        'name' => $staff->ranks->first()->name,
                        'start_date' => $staff->ranks->first()->pivot->start_date->format('d M Y'),
                        'remarks' => $staff->ranks->first()->pivot->remarks,
                    ] : null,
                    'unit' => $staff->units->count() > 0 ? [
                        'id' => $staff->units->first()->id,
                        'name' => $staff->units->first()->name,
                        'start_date' => $staff->units->first()?->pivot->start_date?->format('d M Y'),
                        'start_date_full' => $staff->units->first()->pivot?->start_date,
                        'duration' => $staff->units->first()->pivot->start_date?->diffForHumans(),
                    ] : null,
                ]),
                'subs' => $unit->subs ? $unit->subs->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'subs' => $sub->subs_count,
                    'staff_count' => $sub->staff_count,
                    // 'staff' => $sub->staff ? $sub->staff->map(fn ($stafff) => [
                    //     'id' => $stafff->id,
                    //     'name' => $stafff->full_name,
                    //     'dob' => $stafff->date_of_birth,
                    //     'ssn' => $stafff->social_security_number,
                    //     'initials' => $stafff->initials,
                    // ]) : null,
                ]) : null,
            ],

            'filters' => [
                'dept' => request()->dept,
                'staff' => request()->staff,
            ],
        ]);
    }

    public function store(StoreUnitRequest $request)
    {
        // dd($request->validated());
        $unit = Unit::create($request->validated());

        return redirect()->route('unit.show', $unit->id)->with('success', 'Unit created successfully');
    }

    public function update(UpdateUnitRequest $request)
    {
        // return $request->validated();
        $unit = Unit::whereId($request->id)->first();
        $unit->update($request->validated());
        return redirect()->back()->with('success', 'Unit updated successfully');
    }

    public function delete(Unit $unit)
    {
        $unit->delete();
        return redirect()->back()->with('success', 'Unit deleted successfully');
    }
}