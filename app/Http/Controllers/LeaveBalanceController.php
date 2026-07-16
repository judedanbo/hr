<?php

namespace App\Http\Controllers;

use App\Models\LeaveYear;
use App\Services\CurrentStaffResolver;
use App\Services\LeaveBalanceService;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private CurrentStaffResolver $staffResolver,
        private LeaveBalanceService $balance,
    ) {}

    public function index(): Response
    {
        $staff = $this->staffResolver->resolveOrAbort(request()->user());
        $year = LeaveYear::query()->where('is_active', true)->first();

        return Inertia::render('LeaveBalance/Index', [
            'year' => $year?->year,
            'ledger' => $year ? $this->balance->ledger($staff, $year) : [],
        ]);
    }
}
