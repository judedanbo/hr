<?php

namespace App\Http\Controllers;

use App\Exports\StaffListRawExport;
use Illuminate\Http\Request;
use App\Models\InstitutionPerson;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;

class StaffListController extends Controller
{
    public function __invoke(Excel $excel)
    {
        return $excel->download(new StaffListRawExport, 'staff-list.xlsx');
    }
}
