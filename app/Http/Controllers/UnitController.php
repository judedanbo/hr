<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index($institution = null)
    {
        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                ->departments()
                ->with([
                    'institution',
                    'subs' => function ($query) {
                        $query->whereHas('staff', function ($query) {
                            $query->active();
                        });
                        $query->withCount([
                            'staff' => function ($query) {
                                $query->active();
                            },
                            // 'subs'
                        ]);
                    }
                ])
                ->withCount(
                    [
                        'staff' => function ($query) {
                            $query->active();
                        },
                        'subs' => function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                            });
                        }
                    ]
                )
                ->when(request()->institution, function ($query, $search) {
                    $query->where('institution_id', request()->institution);
                })
                ->searchUnit(request()->search)
                ->paginate(10)
                ->withQueryString()
                ->through(
                    fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'short_name' => $unit->short_name,
                        'staff' => $unit->staff_count + $unit->subs->sum('staff_count'),
                        'units' => $unit->subs_count,
                        'institution' => $unit->institution ? [
                            'id' => $unit->institution->id,
                            'name' => $unit->institution->name,
                        ] : null,
                    ]
                ),
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
                    $query->active();
                    $query->search(request()->search);
                },
                // 'subs'
                'subs' => function ($query) {
                    $query->when(request()->search, function ($query) {
                        $query->where('name', 'like', '%' . request()->search . '%');
                    });
                    $query->where(function ($query) {
                        $query->whereHas('staff', function ($query) {
                            $query->active();
                            $query->search(request()->search);
                        });
                        $query->orWhereHas('subs', function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                                $query->search(request()->search);
                            });
                        });
                    });
                    $query->with([
                        'subs' => function ($query) {
                            $query->where(function ($query) {
                                $query->whereHas('staff', function ($query) {
                                    $query->active();
                                    $query->search(request()->search);
                                });
                                $query->orWhereHas('subs', function ($query) {
                                    $query->whereHas('staff', function ($query) {
                                        $query->active();
                                        $query->search(request()->search);
                                    });
                                });
                            });
                            $query->withCount([
                                'staff' => function ($query) {
                                    $query->active();
                                },
                            ]);
                        },
                        'staff' => function ($query) {
                            $query->with(['person', 'ranks', 'units']);
                            $query->active();
                            $query->search(request()->search);
                        }
                    ]);

                    $query->withCount([
                        'staff' => function ($query) {
                            $query->active();
                            $query->search(request()->search);
                        },
                        'subs' => function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                            });
                        },
                    ]);
                }
            ])
            ->withCount([

                'subs' => function ($query) {
                    $query->where(function ($query) {
                        $query->whereHas('staff', function ($query) {
                            $query->active();
                            $query->search(request()->search);
                        });
                        $query->orWhereHas('subs', function ($query) {
                            $query->whereHas('staff', function ($query) {
                                $query->active();
                                $query->search(request()->search);
                            });
                        });
                    });
                },
                'staff' => function ($query) {
                    $query->active();
                    $query->search(request()->search);
                }
            ])
            ->whereId($unit)
            ->firstOrFail();
        // return $unit;
        // $filtered = $unit->staff->filter(function ($value) {
        //     return $value->person !== null &&  $value->person?->date_of_birth->diffInYears(Carbon::now()) < 60;
        // });
        $sub_staff  = $unit?->subs?->map(fn ($sub) => $sub->staff)->flatten(1);
        // return $sub_staff;
        $allStaff = $unit?->staff->merge($sub_staff)->flatten(1);
        // return $allStaff;
        return Inertia::render('Unit/Show', [
            'unit' => [
                'id' => $unit?->id,
                'name' => $unit?->name,
                'staff_number' => $unit?->subs ? $unit?->staff_count + $unit?->subs->sum('staff_count') : $unit?->staff_count,
                'subs_number' => $unit?->subs_count,
                'institution' => $unit?->institution ? [
                    'name' => $unit?->institution->name,
                    'id' => $unit?->institution->id,
                ] : null,
                'parent' => $unit?->parent ? [
                    'name' => $unit?->parent->name,
                    'id' => $unit?->parent->id,
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
                'type' => $unit?->type->label(),
                'staff' => $allStaff?->map(fn ($staff) => [
                    'id' => $staff->person->id,
                    'name' => $staff->person->full_name,
                    'dob' => $staff->person->date_of_birth?->format('d M Y'),
                    'initials' => $staff->person->initials,
                    'hire_date' => $staff->hire_date?->format('d M Y'),
                    'staff_number' => $staff->staff_number,
                    'file_number' => $staff->file_number,
                    'image' => $staff->person->image ? Storage::disk('avatars')->url($staff->person->image) : null,
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
                        'start_date_full' => $staff->units->first()->pivot?->start_date?->format('d M Y'),
                        'duration' => $staff->units->first()->pivot->start_date?->diffForHumans(),
                    ] : null,
                ]),
                'subs' => $unit?->subs ? $unit->subs->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'subs' => $sub->subs_count,
                    'staff_count' => $sub->staff_count + $sub->subs->sum('staff_count'),
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

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());
        return redirect()->back()->with('success', 'Unit updated successfully');
    }

    public function delete(Unit $unit)
    {
        $unit->delete();
        return redirect()->back()->with('success', 'Unit deleted successfully');
    }
    public function details(Unit $unit)
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'short_name' => $unit->short_name,
            'type' => $unit->type,
            'institution_id' => $unit->institution_id,
            'unit_id' => $unit->unit_id,
            'start_date' => $unit->start_date?->format('Y-m-d'),
            'end_date' => $unit->end_date?->format('Y-m-d'),

        ];
        // return $unit->only(['id', 'name', 'short_name', 'type', 'institution_id', 'unit_id', 'start_date', 'end_date']);
    }

    public function addSub(Request $request, Unit $unit)
    {
        $newSub = $request->only(
            ['name', 'short_name', 'type', 'unit_id', 'start_date', 'end_date']
        );
        $newSub['institution_id'] = $unit->institution_id;

        $unit->subs()->create($newSub);

        // $unit->subs()->attach($request->sub_id, ['start_date' => $request->start_date]);
        return redirect()->back()->with('success', 'Sub unit added successfully');
    }

    // public function list(){
    //     return 'unit list';
    //     // return Unit::department()->get()->map(fn ($unit) => [
    //     //     'value' => $unit->id,
    //     //     'label' => $unit->name,
    //     // ]);
    // }
}
