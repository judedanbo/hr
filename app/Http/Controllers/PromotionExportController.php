<?php

namespace App\Http\Controllers;

use App\Exports\PromotionListExport;
use App\Exports\PromotionSummaryExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Excel as Excel;
use Illuminate\Http\Request;

class PromotionExportController extends Controller
{
    public function show(Request $request, Excel $excel)
    {
        // dd($request->rank);
        return $excel->download(new PromotionListExport($request->rank), 'Promotion List.xlsx', Excel::XLSX);
    }
    function list(Excel $excel)
    {
        return $excel->download(new PromotionSummaryExport, 'promotion summary.xlsx');
    }
}
