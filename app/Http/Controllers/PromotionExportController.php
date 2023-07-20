<?php

namespace App\Http\Controllers;

use App\Exports\PromotionListExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Excel as Excel;
use Illuminate\Http\Request;

class PromotionExportController extends Controller
{
    public function show(Excel $excel)
    {
        return $excel->download(new PromotionListExport, 'promotion_list.xlsx', Excel::XLSX);
    }
}