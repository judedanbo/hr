<?php

namespace App\Exports;

use App\Models\PersonUnit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
// use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecruitmentSummary implements
    // FromCollection,
    ShouldAutoSize,
    WithHeadings,
    FromQuery,
    WithMapping,
    ShouldQueue
{
    use Exportable;

    function headings(): array
    {
        return [
            'Year of Recruitment',
            'Total Male ',
            'Total Female',
            'Total Recruitment',
        ];
    }
    public function map($recruitment): array
    {
        return [
            $recruitment->year,
            $recruitment->male,
            $recruitment->female,
            $recruitment->total,
        ];
    }
    public function query()
    {
        return PersonUnit::query()
            ->join('people', 'people.id', '=', 'person_unit.person_id')
            ->select(DB::raw(
                "year(person_unit.hire_date) as year,
                SUM(CASE WHEN people.gender = 'Male' THEN 1 ELSE NULL END) as male,
                SUM(CASE WHEN people.gender = 'Female' THEN 1 ELSE NULL END) as female,
                count(*) as total"
            ))
            ->when(request()->retired, function ($query) {
                $query->retired();
            })
            ->when(request()->active, function ($query) {
                $query->active();
            })
            ->groupByRaw('year')
            ->orderBy('year', 'desc')
            ->take(10);
    }
}


// $recruitment = PersonUnit::query()
// ->join('people', 'people.id', '=', 'person_unit.person_id')
// ->select(DB::raw(
//     "year(person_unit.hire_date) as year,
//     SUM(CASE WHEN people.gender = 'Male' THEN 1 ELSE NULL END) as male,
//     SUM(CASE WHEN people.gender = 'Female' THEN 1 ELSE NULL END) as female,
//     count(*) as total"
// ))
// ->when(request()->retired, function ($query) {
//     $query->retired();
// })
// ->when(request()->active, function ($query) {
//     $query->active();
// })
// ->groupByRaw('year')
// ->orderBy('year', 'desc')
// ->take(10)
// ->get();
// // return  $recruitment;
// return Inertia::render('Report/Recruitment/Index', [
// 'recruitment' => $recruitment,
// 'filters' => ['search' => request()->search],
// ]);
