<?php

namespace App\Http\Controllers;

use App\Exports\PromotionListExport;
use App\Exports\PromotionSummaryExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Excel as Excel;
use Illuminate\Http\Request;

class PromotionExportController extends Controller
{
    public function show(Excel $excel)
    {
        return $excel->download(new PromotionListExport, 'Promotion List.xlsx', Excel::XLSX);
    }
    function list(Excel $excel)
    {
        return $excel->download(new PromotionSummaryExport, 'promotion summary.xlsx');
    }
}
