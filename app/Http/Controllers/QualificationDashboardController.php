<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\QualificationReportFilter;
use App\Services\QualificationReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationDashboardController extends Controller
{
    public function __construct(private readonly QualificationReportService $service) {}

    public function widgets(Request $request): JsonResponse
    {
        $filter = $this->service->applyUnitScope(
            new QualificationReportFilter,
            $request->user(),
        );

        return response()->json([
            'levelDistribution' => $this->service->levelDistribution($filter),
            'byUnit' => $this->service->byUnit($filter),
            'topInstitutions' => $this->service->topInstitutions($filter, 10),
            'trendByYear' => $this->service->trendByYear($filter),
            'pendingApprovals' => $this->service->pendingApprovalsStats(),
            'staffWithoutQualificationsCount' => $this->service->staffWithoutQualifications($filter)->count(),
        ]);
    }
}
