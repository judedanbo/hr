<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\LeaveReportFilter;
use App\Exports\Leaves\LeaveReportExport;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class LeaveReportController extends Controller
{
    public function __construct(
        private \App\Services\LeaveReportService $service,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $filter = $this->service->applyUnitScope(LeaveReportFilter::fromRequest($request), $request->user());

        return Inertia::render('Leave/Reports/Index', array_merge(
            $this->service->summary($filter),
            [
                'filters' => $filter->toArray(),
                'filterOptions' => $this->filterOptions(),
            ],
        ));
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filter = $this->service->applyUnitScope(LeaveReportFilter::fromRequest($request), $request->user());
        [$title, $headings, $rows, $filename] = $this->dataset($request->input('type', 'balances'), $filter);

        return Excel::download(new LeaveReportExport($title, $headings, $rows), $filename . '.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        $filter = $this->service->applyUnitScope(LeaveReportFilter::fromRequest($request), $request->user());
        [$title, $headings, $rows, $filename] = $this->dataset($request->input('type', 'balances'), $filter);

        return Pdf::loadView('pdf.leaves.report', [
            'title' => $title,
            'subtitle' => 'Leave report',
            'headings' => $headings,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
    }

    /**
     * @return array{0:string, 1:array<int,string>, 2:array<int,array<int,mixed>>, 3:string}
     */
    private function dataset(string $type, LeaveReportFilter $filter): array
    {
        return match ($type) {
            'utilisation' => [
                'Utilisation by type',
                ['Leave Type', 'Assigned', 'Planned', 'Taken', 'Remaining'],
                array_map(fn (array $r): array => [$r['leave_type'], $r['assigned'], $r['planned'], $r['taken'], $r['remaining']], $this->service->summary($filter)['utilisationByType']),
                'leave-utilisation',
            ],
            'absence' => [
                'Absence pattern',
                ['Staff', 'Spells', 'Days', 'Bradford factor'],
                array_map(fn (array $r): array => [$r['staff'], $r['spells'], $r['days'], $r['bradford']], $this->service->summary($filter)['absencePattern']),
                'leave-absence-pattern',
            ],
            default => [
                'Staff balances',
                ['Staff', 'Unit', 'Leave Type', 'Assigned', 'Planned', 'Taken', 'Remaining'],
                array_map(fn (array $r): array => [$r['staff'], $r['unit'], $r['leave_type'], $r['assigned'], $r['planned'], $r['taken'], $r['remaining']], $this->service->staffRows($filter)),
                'leave-staff-balances',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'years' => LeaveYear::query()->orderByDesc('year')->get()
                ->map(fn (LeaveYear $y): array => ['value' => $y->id, 'label' => (string) $y->year]),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn (LeaveType $t): array => ['value' => $t->id, 'label' => $t->name]),
            'units' => Unit::query()->orderBy('name')->get()
                ->map(fn (Unit $u): array => ['value' => $u->id, 'label' => $u->name]),
        ];
    }
}
