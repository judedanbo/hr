<?php

namespace App\Http\Controllers;

use App\Exports\StaffPositionExport;
use App\Exports\StaffDetailsExport;
use App\Exports\StaffToRetireExport;
use Maatwebsite\Excel\Excel;

class StaffReportController extends Controller
{
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
}
