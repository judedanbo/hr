<?php

namespace App\Http\Controllers;

use App\Enums\ContactTypeEnum;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Position;
use Carbon\Carbon;
use Inertia\Inertia;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Positions/Index', [
            'positions' => Position::query()
                ->with(['staff' => function ($query) {
                    $query->with(['person' => function ($query) {
                        $query->with(['contacts' => function ($query) {
                            $query->where('contact_type', ContactTypeEnum::PHONE);
                        }]);
                    }]);
                    $query->wherePivotNull('end_date');
                }])
                ->orderBy('name')
                ->paginate()
                ->withQueryString()
                ->through(fn ($position) => [
                    'id' => $position->id,
                    'name' => $position->name,
                    'current_staff' => $position->staff?->first()?->person->full_name ?? 'vacant',
                    'contacts' => $position->staff?->first()?->person->contacts?->map(function ($contact) {
                        return [
                            'id' => $contact->id,
                            'contact' => $contact->contact
                        ];
                    }) ?? 'not available',
                ]),
            'filters' => request()->all('search', 'trashed')
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
     * @param  \App\Http\Requests\StorePositionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePositionRequest $request)
    {
        Position::create($request->validated());
        return redirect()->route('position.index')->with('success', 'Position created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function show(Position $position)
    {
        $position = $position->load(["staff" => function ($query) {
            $query->with([
                'ranks' => function ($query) {
                    $query->wherePivotNull('end_date');
                },
                'units' => function ($query) {
                    $query->wherePivotNull('end_date');
                },
                'person' => function ($query) {
                    $query->with(['contacts' => function ($query) {
                        $query->where('contact_type', ContactTypeEnum::PHONE);
                    }]);
                }
            ]);
            $query->orderBy('start_date', 'desc');
        }]);
        return Inertia::render('Positions/Show', [
            'position' => [
                'id' => $position->id,
                'name' => $position->name,
                'staff' => $position->staff->map(function ($staff) {
                    return [
                        'id' => $staff->id,
                        'person' => [
                            'id' => $staff->person->id,
                            'full_name' => $staff->person->full_name,
                            'image' => $staff->person->image,
                            'initials' => $staff->person->initials,
                            'contacts' => $staff->person->contacts->map(function ($contact) {
                                return [
                                    'id' => $contact->id,
                                    'contact' => $contact->contact,
                                ];
                            }),
                        ],
                        'start_date' => $staff->pivot?->start_date ? Carbon::parse($staff->pivot->start_date)->format('d M, Y') : null,
                        'end_date' => $staff->pivot?->end_date ? Carbon::parse($staff->pivot->end_date)->format('d M, Y') : null,
                        'current_rank' => $staff->ranks->first()?->name ?? 'no rank',
                        'current_rank_date' => $staff->ranks->first()?->pivot->start_date ?? 'no rank',
                        'current_rank_date_display' => $staff->ranks->first()?->pivot?->start_date?->format('d M Y') ?? '',
                        'current_unit' => $staff->units->first()?->name ?? 'no unit',
                        'current_unit_date' => $staff->units->first()?->pivot?->start_date ?? '',
                        'current_unit_date_display' => $staff->units->first()?->pivot?->start_date?->format('d M Y') ?? '',
                    ];
                }),

            ],
            'filters' => ['search' => request()->search],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function edit(Position $position)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePositionRequest  $request
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position->update($request->validated());
        return redirect()->route('position.index')->with('success', 'Position updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function delete(Position $position)
    {
        $position->delete();
        return redirect()->route('position.index')->with('success', 'Position deleted.');
    }

    public function list()
    {
        return Position::select('id as value', 'name as label')->get();
    }
    public function stat()
    {
        return Position::select('id as value', 'name as label')->get();
    }
}
