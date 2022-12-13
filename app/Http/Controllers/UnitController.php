<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index($institution = null)
    {
        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                ->departments()
                ->with('institution')
                ->countSubs()
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

            ->when(request()->dept, function ($query, $search) {
                $query->with('subs', function ($q) use ($search) {
                    $q->withCount(['staff']);
                    $q->where('name', 'like', "%{$search}%");
                });
            }, function ($query) {
                $query->with(['subs' => function ($query) {
                    $query->withCount('staff', 'subs');
                    $query->with('staff');
                }]);
            })

            ->with(['institution', 'parent', 'staff'])
            ->when(request()->staff, function ($query, $search) {
                $query->with('staff', function ($q) use ($search) {
                    $terms = explode(" ", $search);
                    foreach ($terms as $term) {
                        $q->where('surname', 'like', "%{$search}%");
                        $q->orWhere('other_names', 'like', "%{$term}%");
                        $q->orWhere('date_of_birth', 'like', "%{$term}%");
                        $q->orWhere('social_security_number', 'like', "%{$term}%");
                        $q->orWhereRaw("monthname(date_of_birth) like '%{$term}%'");
                    }
                });
                $query->withCount(['staff' => function ($q) use ($search) {
                    $q->where('surname', 'like', "%{$search}%");
                    $q->orWhere('other_names', 'like', "%{$search}%");
                    $q->orWhere('date_of_birth', 'like', "%{$search}%");
                    $q->orWhere('social_security_number', 'like', "%{$search}%");
                    $q->orWhereRaw("monthname(date_of_birth) like '%{$search}%'");
                }]);
            }, function ($query) {
                $query->withCount('staff');
            })
            ->where('id', $unit)
            ->first();

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
                'subs' => $unit->subs ? $unit->subs->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'subs' => $sub->subs_count,
                    'staff_count' => $sub->staff_count,
                    'staff' => $sub->staff ? $sub->staff->map(fn ($stf) => [
                        'id' => $stf->id,
                        'name' => $stf->full_name,
                        'dob' => $stf->date_of_birth,
                        'ssn' => $stf->social_security_number,
                        'initials' => $stf->initials
                    ]) : null,
                ]) : null,
                'type' => $unit->type->name,
                'staff' => $unit->staff ? $unit->staff->map(fn ($st) => [
                    'id' => $st->id,
                    'name' => $st->full_name,
                    'dob' => $st->date_of_birth,
                    'ssn' => $st->social_security_number,
                    'initials' => $st->initials
                ]) : null
                // 'subs' =>
            ],
            'filters' => [
                'dept' => request()->dept,
                'staff' => request()->staff
            ],
        ]);
    }
}