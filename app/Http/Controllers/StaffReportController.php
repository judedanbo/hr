<?php

namespace App\Http\Controllers;

use App\Exports\AllSeparatedExport;
use App\Exports\PendingTransferExport;
use App\Exports\RetiredStaffExport;
use App\Exports\SeparatedDeceasedExport;
use App\Exports\SeparatedDismissedExport;
use App\Exports\SeparatedLeaveWithoutPayExport;
use App\Exports\SeparatedLeaveWithPayExport;
use App\Exports\SeparatedSuspendedExport;
use App\Exports\SeparatedTerminationExport;
use App\Exports\SeparatedVoluntaryExport;
use App\Exports\StaffPositionExport;
use App\Exports\StaffDetailsExport;
use App\Exports\StaffToRetireExport;
use Maatwebsite\Excel\Excel;

class StaffReportController extends Controller
{
    function allRetirements(Excel $excel)
    {
        return $excel->download(new AllSeparatedExport, 'all-retired-staff.xlsx');
    }

    function export(Excel $excel)
    {
        return $excel->download(new StaffPositionExport, 'staff-position.xlsx');
    }
    function details(Excel $excel)
    {
        return $excel->download(new StaffDetailsExport, 'staff-details.xlsx');
    }
    function retirement(Excel $excel)
    {
        return $excel->download(new StaffToRetireExport, 'staff-to-retire.xlsx');
    }
    function pending(Excel $excel)
    {
        return $excel->download(new PendingTransferExport, 'staff-pending-transfer.xlsx');
    }

    function retirements(Excel $excel)
    {
        return $excel->download(new RetiredStaffExport, 'retired-staff.xlsx');
    }

    function leaveWithPay(Excel $excel)
    {
        return $excel->download(new SeparatedLeaveWithPayExport, 'leave-with-pay.xlsx');
    }
    function leaveWithoutPay(Excel $excel)
    {
        return $excel->download(new SeparatedLeaveWithoutPayExport, 'leave-without-pay.xlsx');
    }
    function deceased(Excel $excel)
    {
        return $excel->download(new SeparatedDeceasedExport, 'deceased-staff.xlsx');
    }
    function terminated(Excel $excel)
    {
        return $excel->download(new SeparatedTerminationExport, 'terminated-staff.xlsx');
    }
    function resignation(Excel $excel)
    {
        return $excel->download(new SeparatedTerminationExport, 'resignation-staff.xlsx');
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
        return $excel->download(new SeparatedVoluntaryExport, 'vacation-of-post.xls');
    }
}
