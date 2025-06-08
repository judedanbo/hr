<?php

namespace App\Http\Controllers;

use App\Models\District;

class DistrictController extends Controller
{
    public function index()
    {
        // Logic to list all districts
        return inertia('Districts/Index', [
            'districts' => \App\Models\District::with('region', 'offices.units')->get(),
        ]);
    }

    public function show(District $district)
    {
        // Logic to show a specific district by ID
        return inertia('Districts/Show', [
            'district' => $district->load('region', 'offices.units'),
        ]);
    }
}
