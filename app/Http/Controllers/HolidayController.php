<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Models\LeaveYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Holiday/Index', [
            'holidays' => Holiday::query()
                ->with('leaveYear')
                ->orderByDesc('date')
                ->paginate()
                ->withQueryString()
                ->through(fn (Holiday $holiday): array => [
                    'id' => $holiday->id,
                    'leave_year_id' => $holiday->leave_year_id,
                    'year' => $holiday->leaveYear?->year,
                    'date' => $holiday->date?->format('Y-m-d'),
                    'name' => $holiday->name,
                    'is_recurring' => $holiday->is_recurring,
                ]),
            'leaveYears' => LeaveYear::query()->orderByDesc('year')
                ->get()
                ->map(fn (LeaveYear $year): array => ['value' => $year->id, 'label' => (string) $year->year]),
        ]);
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::create($request->validated());

        return redirect()->route('holiday.index')->with('success', 'Holiday created.');
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return redirect()->route('holiday.index')->with('success', 'Holiday updated.');
    }

    public function delete(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()->back()->with('success', 'Holiday deleted.');
    }
}
