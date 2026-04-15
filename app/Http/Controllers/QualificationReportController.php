<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\JobCategory;
use App\Models\Person;
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
            'topQualifications' => $this->service->topQualifications($filter, 10),
            'levelByGender' => $this->service->levelByGender($filter),
            'trendByYear' => $this->service->trendByYear($filter),
            'staffList' => $this->service->staffList($filter),
        ]);
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:list,by_unit,by_level,gaps',
        ]);

        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        [$export, $filename] = match ($validated['type']) {
            'list' => [
                new \App\Exports\Qualifications\QualificationListExport($filter),
                'qualifications-list.xlsx',
            ],
            'by_unit' => [
                new \App\Exports\Qualifications\QualificationByUnitExport($filter),
                'qualifications-by-unit.xlsx',
            ],
            'by_level' => [
                new \App\Exports\Qualifications\QualificationByLevelExport($filter),
                'qualifications-by-level.xlsx',
            ],
            'gaps' => [
                new \App\Exports\Qualifications\StaffWithoutQualificationsExport($filter),
                'staff-without-qualifications.xlsx',
            ],
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        $allUnits = Unit::query()
            ->whereNull('end_date')
            ->select('id', 'name', 'unit_id', 'type')
            ->orderBy('name')
            ->get();

        $parents = $allUnits->keyBy('id');
        $resolveDepartment = function ($unit) use ($parents) {
            $current = $unit;
            $seen = [];
            while ($current && ! in_array($current->id, $seen, true)) {
                if ($current->type === \App\Enums\UnitType::DEPARTMENT) {
                    return $current->id;
                }
                $seen[] = $current->id;
                $current = $current->unit_id ? $parents->get($current->unit_id) : null;
            }

            return null;
        };

        $departments = $allUnits
            ->where('type', \App\Enums\UnitType::DEPARTMENT)
            ->values()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->all();

        $units = $allUnits
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'department_id' => $resolveDepartment($u),
            ])
            ->values()
            ->all();

        return [
            'departments' => $departments,
            'units' => $units,
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

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $validated = $request->validate([
            'type' => 'required|in:list,by_unit,by_level,gaps',
        ]);

        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        $payload = match ($validated['type']) {
            'list' => [
                'view' => 'pdf.qualifications.list',
                'data' => [
                    'rows' => $this->listRowsForPdf($filter),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-list.pdf',
            ],
            'by_unit' => [
                'view' => 'pdf.qualifications.by_unit',
                'data' => [
                    'byUnit' => $this->service->byUnit($filter),
                    'levels' => QualificationLevelEnum::orderedByRank(),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-by-unit.pdf',
                'orientation' => 'landscape',
            ],
            'by_level' => [
                'view' => 'pdf.qualifications.by_level',
                'data' => [
                    'distribution' => $this->service->levelDistribution($filter),
                    'levels' => QualificationLevelEnum::orderedByRank(),
                    'totalStaff' => InstitutionPerson::query()->whereNull('end_date')->count() ?: 1,
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-by-level.pdf',
            ],
            'gaps' => [
                'view' => 'pdf.qualifications.gaps',
                'data' => [
                    'staff' => $this->service->staffWithoutQualifications($filter),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'staff-without-qualifications.pdf',
            ],
        };

        return \Barryvdh\DomPDF\Facade\Pdf::loadView($payload['view'], $payload['data'])
            ->setPaper('a4', $payload['orientation'] ?? 'portrait')
            ->download($payload['filename']);
    }

    public function staffProfilePdf(Person $person, Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        $owns = $user?->person_id === $person->id;

        if (! $owns && ! $user?->can('qualifications.reports.export')) {
            abort(403);
        }

        $qualifications = $person->qualifications()->orderByDesc('year')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.qualifications.staff_profile', [
            'person' => $person,
            'qualifications' => $qualifications,
            'user' => $user,
        ])->setPaper('a4')->download("qualifications-{$person->id}.pdf");
    }

    /**
     * Fetch qualifications + their staff_number for the PDF list view.
     * Uses a left join to include staff_number without multiple round-trips.
     */
    private function listRowsForPdf(QualificationReportFilter $filter): \Illuminate\Support\Collection
    {
        return $this->service
            ->applyFilter(Qualification::query(), $filter)
            ->leftJoin('institution_person', 'qualifications.person_id', '=', 'institution_person.person_id')
            ->with('person')
            ->orderByDesc('qualifications.year')
            ->select('qualifications.*', 'institution_person.staff_number')
            ->limit(5000)
            ->get();
    }

    private function filterSummary(QualificationReportFilter $filter): string
    {
        $parts = [];

        if ($filter->departmentId) {
            $name = Unit::whereKey($filter->departmentId)->value('name');
            $parts[] = 'Department: ' . ($name ?? $filter->departmentId);
        }
        if ($filter->unitId) {
            $name = Unit::whereKey($filter->unitId)->value('name');
            $parts[] = 'Unit: ' . ($name ?? $filter->unitId);
        }
        if ($filter->level) {
            $case = QualificationLevelEnum::tryFrom($filter->level);
            $parts[] = 'Level: ' . ($case?->label() ?? $filter->level);
        }
        if ($filter->status) {
            $case = QualificationStatusEnum::tryFrom($filter->status);
            $parts[] = 'Status: ' . ($case?->label() ?? $filter->status);
        }
        if ($filter->gender) {
            $parts[] = 'Gender: ' . match ($filter->gender) {
                'M' => 'Male',
                'F' => 'Female',
                default => $filter->gender,
            };
        }
        if ($filter->jobCategoryId) {
            $name = class_exists(JobCategory::class)
                ? JobCategory::whereKey($filter->jobCategoryId)->value('name')
                : null;
            $parts[] = 'Rank Category: ' . ($name ?? $filter->jobCategoryId);
        }
        if ($filter->yearFrom) {
            $parts[] = 'Year from: ' . $filter->yearFrom;
        }
        if ($filter->yearTo) {
            $parts[] = 'Year to: ' . $filter->yearTo;
        }
        if ($filter->institution) {
            $parts[] = 'Institution: ' . $filter->institution;
        }
        if ($filter->course) {
            $parts[] = 'Course: ' . $filter->course;
        }

        return $parts ? implode(' · ', $parts) : 'none';
    }
}
