<?php

namespace App\Http\Controllers;

use App\Enums\AppraisalCycleStatusEnum;
use App\Http\Requests\StoreAppraisalCycleRequest;
use App\Http\Requests\UpdateAppraisalCycleRequest;
use App\Models\AppraisalCycle;
use Inertia\Inertia;

class AppraisalCycleController extends Controller
{
    public function index()
    {
        return Inertia::render('AppraisalCycle/Index', [
            'cycles' => AppraisalCycle::query()
                ->withCount('appraisals')
                ->orderByDesc('year')
                ->paginate(per_page())
                ->withQueryString()
                ->through(fn ($cycle) => $this->transform($cycle)),
            'statuses' => $this->statusOptions(),
            'filters' => request()->all('search'),
        ]);
    }

    public function store(StoreAppraisalCycleRequest $request)
    {
        AppraisalCycle::create($request->validated());

        return redirect()->route('appraisal-cycle.index')->with('success', 'Appraisal cycle created.');
    }

    public function show(AppraisalCycle $appraisalCycle)
    {
        $appraisalCycle->loadCount('appraisals');

        return Inertia::render('AppraisalCycle/Show', [
            'cycle' => $this->transform($appraisalCycle),
        ]);
    }

    public function update(UpdateAppraisalCycleRequest $request, AppraisalCycle $appraisalCycle)
    {
        $appraisalCycle->update($request->validated());

        return redirect()->route('appraisal-cycle.index')->with('success', 'Appraisal cycle updated.');
    }

    public function delete(AppraisalCycle $appraisalCycle)
    {
        $appraisalCycle->delete();

        return redirect()->back()->with('success', 'Appraisal cycle deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(AppraisalCycle $cycle): array
    {
        return [
            'id' => $cycle->id,
            'name' => $cycle->name,
            'year' => $cycle->year,
            'objective_window_start' => $cycle->objective_window_start?->format('Y-m-d'),
            'objective_window_end' => $cycle->objective_window_end?->format('Y-m-d'),
            'midyear_window_start' => $cycle->midyear_window_start?->format('Y-m-d'),
            'midyear_window_end' => $cycle->midyear_window_end?->format('Y-m-d'),
            'final_window_start' => $cycle->final_window_start?->format('Y-m-d'),
            'final_window_end' => $cycle->final_window_end?->format('Y-m-d'),
            'objectives_weight' => $cycle->objectives_weight,
            'competencies_weight' => $cycle->competencies_weight,
            'status' => $cycle->status->value,
            'status_label' => $cycle->status->label(),
            'status_color' => $cycle->status->color(),
            'appraisals_count' => $cycle->appraisals_count ?? 0,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function statusOptions(): array
    {
        return collect(AppraisalCycleStatusEnum::cases())
            ->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])
            ->all();
    }
}
