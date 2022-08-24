<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function index()
    {
        return Inertia::render('Institution/Index', [
            'institutions' => Institution::query()
                ->when(request()->search, function($query, $search){
                    $terms =  explode(" ", $search);
                        $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(10)
                ->through(fn($institution) => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show(Institution $institution)
    {
        return Inertia::render('Institution/Show', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
            ]
        ]);
    }
}