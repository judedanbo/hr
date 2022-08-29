<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index()
    {
        return Inertia::render('Unit/Index', [
            'units' => Unit::query()
                ->departments()
                ->with('institution')
                ->countSubs()
                ->when(request()->search, function($query, $search){
                    $query-> where('name', 'like', "%{$search}%");
                })
                ->paginate(10)
                ->through(fn ($unit) => [
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
        $unit = Unit::with('institution', 'parent', 'subs.subs')
        ->where('id', $unit)
        ->first();

        // dd($unit);

        return Inertia::render('Unit/Show', [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'institution' => $unit->institution ? [
                    'name' => $unit->institution->name,
                    'id' => $unit->institution->id,
                ] : null,
                'parent' => $unit->parent ? [
                    'name' => $unit->parent->name,
                    'id' => $unit->parent->id,
                ] : null,
                'subs' => $unit->subs ? $unit->subs->map(fn($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'subs' => $sub->subs->count()

                ]): null,
                'type' => $unit->type->name,
                // 'subs' =>
            ]
        ]);
    }
}