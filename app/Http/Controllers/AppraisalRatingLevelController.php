<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppraisalRatingLevelRequest;
use App\Http\Requests\UpdateAppraisalRatingLevelRequest;
use App\Models\AppraisalRatingLevel;
use Inertia\Inertia;

class AppraisalRatingLevelController extends Controller
{
    public function index()
    {
        return Inertia::render('AppraisalRatingLevel/Index', [
            'levels' => AppraisalRatingLevel::query()
                ->orderBy('value')
                ->get()
                ->map(fn ($level) => $this->transform($level)),
        ]);
    }

    public function store(StoreAppraisalRatingLevelRequest $request)
    {
        AppraisalRatingLevel::create($request->validated());

        return redirect()->route('appraisal-rating-level.index')->with('success', 'Rating level created.');
    }

    public function update(UpdateAppraisalRatingLevelRequest $request, AppraisalRatingLevel $appraisalRatingLevel)
    {
        $appraisalRatingLevel->update($request->validated());

        return redirect()->route('appraisal-rating-level.index')->with('success', 'Rating level updated.');
    }

    public function delete(AppraisalRatingLevel $appraisalRatingLevel)
    {
        $appraisalRatingLevel->delete();

        return redirect()->back()->with('success', 'Rating level deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(AppraisalRatingLevel $level): array
    {
        return [
            'id' => $level->id,
            'value' => $level->value,
            'label' => $level->label,
            'min_score' => (float) $level->min_score,
            'max_score' => (float) $level->max_score,
            'description' => $level->description,
            'color' => $level->color,
        ];
    }
}
