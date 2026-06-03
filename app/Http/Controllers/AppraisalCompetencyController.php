<?php

namespace App\Http\Controllers;

use App\Enums\CompetencyGroupEnum;
use App\Http\Requests\StoreAppraisalCompetencyRequest;
use App\Http\Requests\UpdateAppraisalCompetencyRequest;
use App\Models\AppraisalCompetency;
use App\Models\JobCategory;
use Inertia\Inertia;

class AppraisalCompetencyController extends Controller
{
    public function index()
    {
        return Inertia::render('AppraisalCompetency/Index', [
            'competencies' => AppraisalCompetency::query()
                ->with('jobCategory:id,name')
                ->orderBy('group')
                ->orderBy('name')
                ->paginate(per_page())
                ->withQueryString()
                ->through(fn ($competency) => $this->transform($competency)),
            'groups' => $this->groupOptions(),
            'jobCategories' => JobCategory::query()
                ->orderBy('name')
                ->get(['id as value', 'name as label']),
            'filters' => request()->all('search'),
        ]);
    }

    public function store(StoreAppraisalCompetencyRequest $request)
    {
        AppraisalCompetency::create($request->validated());

        return redirect()->route('appraisal-competency.index')->with('success', 'Competency created.');
    }

    public function update(UpdateAppraisalCompetencyRequest $request, AppraisalCompetency $appraisalCompetency)
    {
        $appraisalCompetency->update($request->validated());

        return redirect()->route('appraisal-competency.index')->with('success', 'Competency updated.');
    }

    public function delete(AppraisalCompetency $appraisalCompetency)
    {
        $appraisalCompetency->delete();

        return redirect()->back()->with('success', 'Competency deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(AppraisalCompetency $competency): array
    {
        return [
            'id' => $competency->id,
            'name' => $competency->name,
            'description' => $competency->description,
            'group' => $competency->group->value,
            'group_label' => $competency->group->label(),
            'group_color' => $competency->group->color(),
            'default_weight' => $competency->default_weight,
            'job_category_id' => $competency->job_category_id,
            'job_category' => $competency->jobCategory?->name,
            'is_active' => $competency->is_active,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function groupOptions(): array
    {
        return collect(CompetencyGroupEnum::cases())
            ->map(fn ($group) => ['value' => $group->value, 'label' => $group->label()])
            ->all();
    }
}
