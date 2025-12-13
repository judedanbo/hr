<?php

namespace App\Http\Controllers;

use App\Exports\AllSeparatedExport;
use App\Exports\PendingTransferExport;
use App\Exports\PositionsExport;
use App\Exports\RetiredStaffExport;
use App\Exports\SeparatedDeceasedExport;
use App\Exports\SeparatedDismissedExport;
use App\Exports\SeparatedLeaveWithoutPayExport;
use App\Exports\SeparatedLeaveWithPayExport;
use App\Exports\SeparatedResignationExport;
use App\Exports\SeparatedSuspendedExport;
use App\Exports\SeparatedTerminationExport;
use App\Exports\SeparatedVacatedPostExport;
use App\Exports\SeparatedVoluntaryExport;
use App\Exports\StaffDetailsExport;
use App\Exports\StaffPositionExport;
use App\Exports\StaffToRetireExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel;

class StaffReportController extends Controller
{
    public function allRetirements(Excel $excel)
    {
        return $excel->download(new AllSeparatedExport, 'all-retired-staff.xlsx');
    }

    public function export(Request $request, Excel $excel)
    {
        if (Gate::denies('view staff position')) {
            activity()
                ->causedBy(auth()->user())
                ->event('download')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted download staff position');

            return redirect()->back()->with('error', 'You are not authorized to download this file');
        }

        $filters = $request->only([
            'search',
            'rank_id',
            'job_category_id',
            'unit_id',
            'department_id',
            'gender',
            'status',
            'hire_date_from',
            'hire_date_to',
            'age_from',
            'age_to',
        ]);

        activity()
            ->causedBy(auth()->user())
            ->event('download')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'filters' => $filters,
            ])
            ->log('downloaded staff position');

        return $excel->download(new StaffPositionExport($filters), 'staff-position.xlsx');
    }

    public function details(Request $request, Excel $excel)
    {
        $filters = $request->only([
            'search',
            'rank_id',
            'job_category_id',
            'unit_id',
            'department_id',
            'gender',
            'status',
            'hire_date_from',
            'hire_date_to',
            'age_from',
            'age_to',
        ]);

        return $excel->download(new StaffDetailsExport($filters), 'staff-details.xlsx');
    }

    public function retirement(Excel $excel)
    {
        return $excel->download(new StaffToRetireExport, 'staff-to-retire.xlsx');
    }

    public function pending(Excel $excel)
    {
        return $excel->download(new PendingTransferExport, 'staff-pending-transfer.xlsx');
    }

    public function retirements(Excel $excel)
    {
        return $excel->download(new RetiredStaffExport, 'retired-staff.xlsx');
    }

    public function leaveWithPay(Excel $excel)
    {
        return $excel->download(new SeparatedLeaveWithPayExport, 'leave-with-pay.xlsx');
    }

    public function leaveWithoutPay(Excel $excel)
    {
        return $excel->download(new SeparatedLeaveWithoutPayExport, 'leave-without-pay.xlsx');
    }

    public function deceased(Excel $excel)
    {
        return $excel->download(new SeparatedDeceasedExport, 'deceased-staff.xlsx');
    }

    public function terminated(Excel $excel)
    {
        return $excel->download(new SeparatedTerminationExport, 'terminated-staff.xlsx');
    }

    public function resignation(Excel $excel)
    {
        return $excel->download(new SeparatedResignationExport, 'resignation-staff.xlsx');
    }

    public function suspended(Excel $excel)
    {
        return $excel->download(new SeparatedSuspendedExport, 'suspended.xls');
    }

    public function volRetirement(Excel $excel)
    {
        return $excel->download(new SeparatedVoluntaryExport, 'voluntary-retirement.xls');
    }

    public function dismissed(Excel $excel)
    {
        return $excel->download(new SeparatedDismissedExport, 'dismissed.xls');
    }

    public function vacatedPost(Excel $excel)
    {
        return $excel->download(new SeparatedVacatedPostExport, 'vacation-of-post.xls');
    }

    public function positions(Excel $excel)
    {
        return $excel->download(new PositionsExport, 'positions.xls');
    }
}
