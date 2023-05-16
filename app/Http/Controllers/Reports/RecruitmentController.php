<?php

namespace App\Http\Controllers\Reports;

use App\Exports\RecruitmentExport;
use App\Exports\RecruitmentSummary;
use App\Http\Controllers\Controller;
use App\Models\InstitutionPerson;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel as Excel;

class RecruitmentController extends Controller
{
    public function index()
    {
        return Inertia::render('Report/Index');
    }

    public function recruitment()
    {
        $recruitment = InstitutionPerson::query()
            ->join('people', 'people.id', '=', 'institution_person.person_id')
            ->select(DB::raw(
                "year(institution_person.hire_date) as year,
                SUM(CASE WHEN people.gender = 'M' THEN 1 ELSE NULL END) as male,
                SUM(CASE WHEN people.gender = 'F' THEN 1 ELSE NULL END) as female,
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
            ->take(10)
            ->get();
        // return  $recruitment;
        return Inertia::render('Report/Recruitment/Index', [
            'recruitment' => $recruitment,
            'filters' => ['search' => request()->search],
        ]);
    }
    public function recruitmentChart()
    {
        $recruitment = InstitutionPerson::query()
            ->join('people', 'people.id', '=', 'institution_person.person_id')
            ->select(DB::raw(
                "year(institution_person.hire_date) as year,
                SUM(CASE WHEN people.gender = 'M' THEN 1 ELSE NULL END) as male,
                SUM(CASE WHEN people.gender = 'F' THEN 1 ELSE NULL END) as female,
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
            ->get();


        return Inertia::render('Report/Recruitment/History', [
            'recruitment' =>  $recruitment,
            'filters' => ['search' => request()->search],
        ]);
    }

    function detail()
    {
        $staff = InstitutionPerson::query()
            ->with(['person', 'ranks', 'units'])
            ->when(request()->active, function ($query) {
                $query->whereHas('person', function ($q) {
                    $q->whereDate('date_of_birth', '>', Carbon::now()->subYears(60));
                });
                // $query->where('people.');
            })
            ->when(request()->retired, function ($query) {
                $query->whereHas('person', function ($q) {
                    $q->whereDate('date_of_birth', '<', Carbon::now()->subYears(60));
                });
            })
            ->when(request()->ranks, function ($query) {
                $ranks = explode('|', request()->ranks);
                foreach ($ranks as $rank) {
                    $query->orWhere('jobs.name', $rank);
                }
            })
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($staff) => [
                'staff_id' => $staff->id,
                'person_id' => $staff->person_id,
                'date_of_birth' => $staff->person->date_of_birth,
                'name' => $staff->person->full_name,
                'gender' => $staff->person->gender->name,
                'hire_date' => $staff->hire_date,
                'years_employed' => $staff->years_employed,
                'staff_number' => $staff->staff_number,
                'old_staff_number' => $staff->old_staff_number,
                'status' => $staff->status,
                'current_rank' => $staff->ranks->count() > 0 ? [
                    'id' => $staff->ranks->first()->id,
                    'name' => $staff->ranks->first()->name,
                    'start_date' => $staff->ranks()->first()->pivot->start_date,
                ] : null,
                'current_unit' => $staff->units->count() > 0 ? [
                    'id' => $staff->units->first()->id,
                    'name' => $staff->units->first()->name,
                    'start_date' => $staff->units->first()->pivot->start_date,
                ] : null
            ]);

        return Inertia::render('Report/Recruitment/Details', [
            'staff' => $staff,
            'jobs' => Job::select('id', 'name')->orderBy('name')->get(),
            'filters' => [
                'search' => request()->search,
                'ranks' => request()->ranks,
                'active' => request()->active,
                'retired' => request()->retired,
            ],
        ]);
    }

    public function exportAll(Excel $excel)
    {
        return $excel->download(new RecruitmentExport, 'all_recruitment.xlsx');
        // return $excel->store(new RecruitmentExport, 'all_recruitment.pdf', Excel::DOMPDF);
    }

    public function exportSummary(Excel $excel)
    {
        return $excel->download(new RecruitmentSummary, 'recruitment_summary.xlsx');
        // return $excel->store(new RecruitmentExport, 'all_recruitment.pdf', Excel::DOMPDF);
    }
}