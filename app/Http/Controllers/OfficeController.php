<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;

class OfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return inertia('Offices/Index', [
            // include staff and units in the office index
            // 'offices' => Office::query()
            //     ->with([
            //         'district.region',
            //         // 'units' => function ($query) {
            //         //     $query->withCount('staff');
            //         // }
            //     ])->get(),
            'offices' => Office::query()
                ->with([
                    'district.region',
                    'units' => function ($query) {
                        $query->withCount('staff');
                    }
                ])
                ->withCount('units')
                // ->withCount(['units as staff_count' => function ($query) {
                //     $query->select(\DB::raw('SUM(staff_count)'));
                // }])
                ->when(request()->search, function ($query) {
                    $query->where('name', 'like', '%' . request()->search . '%');
                })
                ->paginate()
                ->withQueryString()
                ->through(fn($office) => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'district' => $office->district?->name,
                    'region' => $office->district?->region->name,
                    'units_count' => $office->units_count,
                    'staff_count' => $office->units->sum(fn($unit) => $unit->staff_count),
                ]),
            'filters' => [
                'search' => request()->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOfficeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Office $office)
    {
        $office->load([
            'district.region',
            'units' => function ($query) {
                $query->withCount('staff');
            }
        ]);
        return inertia('Offices/Show', [
            'office' => $office ? [
                'id' => $office->id,
                'name' => $office->name,
                'district' => $office->district?->name,
                'region' => $office->district?->region->name,
                'units' => $office->units->map(fn($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'staff_count' => $unit->staff_count,
                ]),
                'units_count' => $office->units->count(),
                'staff_count' => $office->units->sum(fn($unit) => $unit->staff_count),
            ] : null
        ]);
        // Logic to show a specific office by ID
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Office $office)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOfficeRequest $request, Office $office)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Office $office)
    {
        //
    }
}
