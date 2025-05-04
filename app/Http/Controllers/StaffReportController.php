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
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Excel;

class StaffReportController extends Controller
{
    public function allRetirements(Excel $excel)
    {
        return $excel->download(new AllSeparatedExport, 'all-retired-staff.xlsx');
    }

    public function export(Excel $excel)
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
        activity()
            ->causedBy(auth()->user())
            ->event('download')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('downloaded staff position');
        return $excel->download(new StaffPositionExport, 'staff-position.xlsx');
    }

    public function details(Excel $excel)
    {
        return $excel->download(new StaffDetailsExport, 'staff-details.xlsx');
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
