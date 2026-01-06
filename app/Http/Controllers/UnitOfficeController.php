<?php

namespace App\Http\Controllers;

use App\Enums\OfficeTypeEnum;
use App\Http\Requests\AssignOfficeToUnitRequest;
use App\Http\Requests\CreateOfficeForUnitRequest;
use App\Models\District;
use App\Models\Office;
use App\Models\Region;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnitOfficeController extends Controller
{
    /**
     * Assign an existing office to a unit.
     */
    public function store(AssignOfficeToUnitRequest $request, Unit $unit): RedirectResponse
    {
        // End current office assignment if exists
        $unit->offices()->wherePivotNull('end_date')->update(['office_unit.end_date' => now()]);

        // Attach new office
        $unit->offices()->attach($request->office_id, [
            'start_date' => $request->start_date ?? now(),
        ]);

        return redirect()->back()->with('success', 'Office assigned successfully.');
    }

    /**
     * Create a new office and assign to unit.
     */
    public function storeNew(CreateOfficeForUnitRequest $request, Unit $unit): RedirectResponse
    {
        // End current office assignment if exists
        $unit->offices()->wherePivotNull('end_date')->update(['office_unit.end_date' => now()]);

        // Create new office
        $office = Office::create($request->only(['name', 'type', 'district_id']));

        // Attach to unit
        $unit->offices()->attach($office->id, [
            'start_date' => $request->start_date ?? now(),
        ]);

        return redirect()->back()->with('success', 'Office created and assigned successfully.');
    }

    /**
     * Remove/unlink office from unit (NOT delete the office entity).
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        if (Gate::denies('edit unit')) {
            abort(403, 'Unauthorized action.');
        }

        // End current office assignment
        $unit->offices()->wherePivotNull('end_date')->update(['office_unit.end_date' => now()]);

        return redirect()->back()->with('success', 'Office unlinked successfully.');
    }

    /**
     * Get office history for a unit.
     */
    public function history(Unit $unit): JsonResponse
    {
        if (Gate::denies('view unit')) {
            abort(403, 'Unauthorized action.');
        }

        $history = $unit->officeHistory()
            ->with('district.region')
            ->get()
            ->map(fn ($office) => [
                'id' => $office->id,
                'name' => $office->name,
                'type' => $office->type?->label(),
                'district' => $office->district?->name,
                'region' => $office->district?->region?->name,
                'start_date' => $office->pivot->start_date,
                'end_date' => $office->pivot->end_date,
            ]);

        return response()->json($history);
    }

    /**
     * List available offices for selection.
     */
    public function availableOffices(): JsonResponse
    {
        $offices = Office::with('district.region')
            ->orderBy('name')
            ->get()
            ->map(fn ($office) => [
                'value' => $office->id,
                'label' => $office->name,
                'district' => $office->district?->name,
                'region' => $office->district?->region?->name,
                'type' => $office->type?->label(),
            ]);

        return response()->json($offices);
    }

    /**
     * List districts for cascading dropdown.
     */
    public function districtsList(): JsonResponse
    {
        $districts = District::with('region')
            ->orderBy('name')
            ->get()
            ->map(fn ($district) => [
                'value' => $district->id,
                'label' => $district->name,
                'region_id' => $district->region_id,
                'region_name' => $district->region?->name,
            ]);

        return response()->json($districts);
    }

    /**
     * List regions.
     */
    public function regionsList(): JsonResponse
    {
        $regions = Region::orderBy('name')
            ->get()
            ->map(fn ($region) => [
                'value' => $region->id,
                'label' => $region->name,
            ]);

        return response()->json($regions);
    }

    /**
     * List office types from enum.
     */
    public function officeTypes(): JsonResponse
    {
        $types = collect(OfficeTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return response()->json($types);
    }
}
