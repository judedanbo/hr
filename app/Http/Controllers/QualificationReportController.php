<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\JobCategory;
use App\Models\Qualification;
use App\Models\Unit;
use App\Services\QualificationReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QualificationReportController extends Controller
{
    public function __construct(private readonly QualificationReportService $service) {}

    public function index(Request $request): Response
    {
        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        return Inertia::render('Qualification/Reports/Index', [
            'filters' => $filter->toQueryArray(),
            'filterOptions' => $this->filterOptions(),
            'kpis' => $this->kpis($filter),
            'levelDistribution' => $this->service->levelDistribution($filter),
            'byUnit' => $this->service->byUnit($filter),
            'topInstitutions' => $this->service->topInstitutions($filter, 10),
            'trendByYear' => $this->service->trendByYear($filter),
            'staffList' => $this->service->staffList($filter),
        ]);
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        return [
            'units' => Unit::query()->select('id', 'name')->orderBy('name')->get(),
            'levels' => collect(QualificationLevelEnum::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
            'statuses' => collect(QualificationStatusEnum::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
            'genders' => [
                ['value' => 'M', 'label' => 'Male'],
                ['value' => 'F', 'label' => 'Female'],
            ],
            'jobCategories' => JobCategory::query()->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /** @return array<string, int> */
    private function kpis(QualificationReportFilter $filter): array
    {
        return [
            'totalQualifications' => Qualification::query()->approved()->count(),
            'staffCovered' => Qualification::query()->approved()->distinct('person_id')->count('person_id'),
            'pending' => $this->service->pendingApprovalsStats()['count'],
            'withoutQualifications' => $this->service->staffWithoutQualifications($filter)->count(),
        ];
    }
}
