<?php

namespace App\Http\Controllers;

use App\Enums\AppraisalStatusEnum;
use App\Exports\AppraisalsExport;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AppraisalReportController extends Controller
{
    public function index(Request $request)
    {
        $cycleId = $request->integer('cycle_id') ?: null;

        $base = Appraisal::query()->when($cycleId, fn ($query) => $query->where('appraisal_cycle_id', $cycleId));

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusDistribution = collect(AppraisalStatusEnum::cases())
            ->map(fn ($status) => [
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ])
            ->values();

        $bandDistribution = (clone $base)
            ->whereNotNull('overall_band')
            ->selectRaw('overall_band, COUNT(*) as total')
            ->groupBy('overall_band')
            ->get()
            ->map(fn ($row) => ['label' => $row->overall_band, 'count' => (int) $row->total])
            ->values();

        $byUnit = (clone $base)
            ->with('unit:id,name')
            ->get()
            ->groupBy(fn ($appraisal) => $appraisal->unit?->name ?? 'Unassigned')
            ->map(fn ($group, $unit) => [
                'unit' => $unit,
                'total' => $group->count(),
                'completed' => $group->where('status', AppraisalStatusEnum::Completed)->count(),
            ])
            ->values();

        return Inertia::render('Appraisal/Report/Index', [
            'cycles' => AppraisalCycle::query()->orderByDesc('year')->get(['id as value', 'name as label']),
            'filters' => ['cycle_id' => $cycleId],
            'summary' => [
                'total' => (clone $base)->count(),
                'completed' => (clone $base)->where('status', AppraisalStatusEnum::Completed)->count(),
            ],
            'statusDistribution' => $statusDistribution,
            'bandDistribution' => $bandDistribution,
            'byUnit' => $byUnit,
        ]);
    }

    public function export(Request $request)
    {
        $cycleId = $request->integer('cycle_id') ?: null;

        activity()
            ->causedBy($request->user())
            ->event('download')
            ->withProperties(['cycle_id' => $cycleId])
            ->log('downloaded appraisals export');

        return Excel::download(new AppraisalsExport($cycleId), 'appraisals.xlsx');
    }

    public function pdf(Request $request, Appraisal $appraisal)
    {
        abort_unless(
            Gate::allows('export appraisals') || $appraisal->isOwnedBy($request->user()),
            403,
        );

        $appraisal->load(['staff.person', 'cycle', 'unit', 'appraiser.person', 'reviewer.person', 'objectives', 'competencyRatings.competency']);

        return Pdf::loadView('pdf.appraisals.form', [
            'appraisal' => $appraisal,
            'user' => $request->user(),
        ])->setPaper('a4')->download("appraisal-{$appraisal->id}.pdf");
    }
}
