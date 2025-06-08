<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Inertia\Inertia;

class RegionController extends Controller
{

    public function index()
    {
        return Inertia::render('Regions/Index', [
            'regions' => Region::query()
                ->withCount('districts')
                ->with(['districts' => function ($query) {
                    $query->withCount('offices');
                    $query->with(['offices' => function ($query) {
                        $query->withCount('units');
                        $query->with(['units' => function ($query) {
                            $query->withCount('staff');
                        }]);
                    }]);
                }])
                ->when(request()->search, function ($query) {
                    $query->where('name', 'like', '%' . request()->search . '%');
                })
                ->paginate()
                ->withQueryString()
                ->through(fn($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'capital' => $region->capital,
                    'staff_count' => $region->districts
                        ->map(fn($district) => $district->offices->map(fn($office) => $office->units->map(fn($unit) => $unit->staff_count)->sum())->sum())
                        ->sum(),
                    'offices_count' => $region->districts
                        ->map(fn($district) => $district->offices_count)
                        ->sum(),
                    'units_count' => $region->districts
                        ->map(fn($district) => $district->offices->map(fn($office) => $office->units_count)->sum())
                        ->sum(),
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }
    public function show(Region $region)
    {
        $region->load(['districts' => function ($query) {
            $query->withCount('offices');
            $query->with(['offices' => function ($query) {
                $query->withCount('units');
                $query->with(['units' => function ($query) {
                    $query->withCount('staff');
                }]);
            }]);
        }]);
        return Inertia::render('Regions/Show', [
            'region_id' => $region,
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
                'capital' => $region->capital,
                'staff_count' => $region->districts
                    ->flatMap(fn($district) => $district->offices)
                    ->flatMap(fn($office) => $office->units)
                    ->flatMap(fn($unit) => $unit->staff)
                    ->count(),
                'offices_count' => $region->districts
                    ->flatMap(fn($district) => $district->offices)
                    ->count(),
                'units_count' => $region->districts
                    ->flatMap(fn($district) => $district->offices)
                    ->flatMap(fn($office) => $office->units)
                    ->count(),
            ],
            'offices' =>  $region->districts
                ->flatMap(fn($district) => $district->offices)
                ->map(fn($office) => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'units_count' => $office->units_count,
                    'office' => $office,
                    'staff_count' => $office->units
                        ->map(fn($unit) => $unit->staff_count)
                        ->sum(),
                    // $office->staff
                    // ->map(fn($unit) => $unit->staff_count)
                    // ->sum()
                    // : 0,
                ]),
        ]);
        // Logic to show a specific region by ID
    }
}
