<?php

namespace App\Http\Controllers\Reports;

use App\Exports\AprilPromotionsExport;
use App\Http\Controllers\Controller;
use App\Models\JobStaff;
use Inertia\Inertia;

class AprilPromotionsController extends Controller
{
    public function __construct(int $year = null)
    {
        if (! $year) {
            $this->year = (int) date('Y');
        }
        $this->year = $year;
    }

    public function index()
    {

        // JobStaff::query()
        //     ->with(['job','staff'])
        //     ->selectRaw('Year(start_date) year, job_id, count(case when month(start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
        //     ->groupByRaw('year, job_id')
        //     ->orderByRaw('year desc')
        //     // ->havingRaw("year <= " . $this->year - 3 )
        //     ->where('remarks', '<>' ,'1st Appointment')
        //     // ->whereYear('start_date','<=', $this->year - 3)
        //     ->get()

        //    return Inertia::render()
        // return $excel->download(new AprilPromotionsExport($year), `April Promotions-${year}.xlsx`);

        return InstitutionPerson::query()
            ->active()
            ->whereHas('ranks', function ($query) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', '<=', $this->year - 3);
                $query->whereNotIn('job_id', [16, 35, 49, 65, 71]);
                $query->whereMonth('start_date', '<=', '07');
            })
            ->with(['person', 'institution', 'units', 'ranks' => function ($query) {
                $query->whereNull('end_date');
                $query->whereYear('start_date', '<=', $this->year - 3);
            }])
            ->orderBy(
                JobStaff::select('start_date')
                    ->whereColumn('staff_id', 'institution_person.id')
                    ->orderBy('start_date', 'desc')
                    ->limit(1)
            )
            ->get();
    }
}
