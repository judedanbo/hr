<?php

namespace App\Http\Controllers;

use App\Exports\PromotionListExport;
use App\Exports\PromotionSummaryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as Excel;

class PromotionExportController extends Controller
{
    public function show(Request $request, Excel $excel)
    {
        return $excel->download(new PromotionListExport($request->rank), 'Promotion List.xlsx', Excel::XLSX);
    }

    public function list(Excel $excel)
    {
        return $excel->download(new PromotionSummaryExport, 'promotion summary.xlsx');
    }
}
