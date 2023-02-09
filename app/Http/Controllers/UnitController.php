<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index($institution = null)
    {
        // return Unit::withCount('staff')->paginate(5);
        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                // ->departments()
                ->with('institution')
                ->withCount('staff')
                // ->countSubs()
                ->when(request()->institution, function ($query, $search) {
                    $query->where('institution_id',  request()->institution);
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
                        'staff' => $unit->staff_count,
                        'institution' => $unit->institution ? [
                            'id' => $unit->institution->id,
                            'name' => $unit->institution->name,
                        ] : null,
                        'subs' => $unit->subs->count() > 0 ? [
                            $unit->subs->map(fn ($sub) => [
                                'id' => $sub->id,
                                'name' => $sub->name
                            ])
                        ] : null,
                    ]
                ),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show($unit)
    {

        $unit = Unit::query()
            // ->when(request()->dept, function ($query, $search) {
            //     $query->with('subs', function ($q) use ($search) {
            //         $q->withCount(['staff']);
            //         $q->where('name', 'like', "%{$search}%");
            //     });
            // }, function ($query) {
            //     $query->with(['subs' => function ($query) {
            //         $query->withCount('staff', 'subs');
            //         $query->with('staff');
            //     }]);
            // })

            ->with([
                'institution', 'parent',
                'staff.person'
            ])
            ->when(request()->staff, function ($query, $search) {
                $query->whereHas('staff', function ($q) use ($search) {
                    $q->whereHas('person', function ($per) use ($search) {
                        $terms = explode(" ", $search);
                        foreach ($terms as $term) {
                            $per->where('surname', 'like', "%{$search}%");
                            $per->orWhere('first_name', 'like', "%{$term}%");
                            $per->orWhere('other_names', 'like', "%{$term}%");
                            $per->orWhere('date_of_birth', 'like', "%{$term}%");
                            $per->orWhereRaw("monthname(date_of_birth) like '%{$term}%'");
                        }
                    });
                });
                $query->withCount(['staff' => function ($q) use ($search) {
                    $q->withCount(['person' => function ($per) use ($search) {
                        $per->where('surname', 'like', "%{$search}%");
                        $per->orWhere('first_name', 'like', "%{$search}%");
                        $per->orWhere('other_names', 'like', "%{$search}%");
                        $per->orWhere('date_of_birth', 'like', "%{$search}%");
                        $per->orWhereRaw("monthname(date_of_birth) like '%{$search}%'");
                    }]);
                }]);
            }, function ($query) {
                $query->withCount('staff');
            })

            ->whereId($unit)
            ->first();

        // $filtered = $unit->staff->filter(function ($value) {
        //     return $value->person !== null &&  $value->person?->date_of_birth->diffInYears(Carbon::now()) < 60;
        // });
        // return $unit;

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
                //     'staff' => $sub->staff ? $sub->staff->map(fn ($stf) => [
                //         'id' => $stf->id,
                //         'name' => $stf->full_name,
                //         'dob' => $stf->date_of_birth,
                //         'ssn' => $stf->social_security_number,
                //         'initials' => $stf->initials
                //     ]) : null,
                // ]) : null,
                'type' => $unit->type->name,
                'staff' => $unit->staff->map(fn ($st) => [
                    'id' => $st->person->id,
                    'name' => $st->person->full_name,
                    'dob' => $st->person->date_of_birth,
                    'initials' => $st->person->initials
                ])
                // 'subs' =>
            ],
            'filters' => [
                'dept' => request()->dept,
                'staff' => request()->staff
            ],
        ]);
    }
}