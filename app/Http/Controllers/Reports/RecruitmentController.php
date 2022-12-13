<?php

namespace App\Http\Controllers\Reports;

use App\Exports\RecruitmentExport;
use App\Exports\RecruitmentSummary;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\PersonUnit;
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
        $recruitment = PersonUnit::query()
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
        $recruitment = PersonUnit::query()
            ->join('people', 'people.id', '=', 'person_unit.person_id')
            ->select(DB::raw(
                "year(person_unit.hire_date) as year,
                SUM(CASE WHEN people.gender = 'Male' THEN 1 ELSE NULL END) as male,
                SUM(CASE WHEN people.gender = 'Female' THEN 1 ELSE NULL END) as female,
                count(*) as total"
            ))
            ->when(request()->retired, function ($query) {
                $query->whereRaw("(DATEDIFF(NOW(), PEOPLE.DATE_OF_BIRTH)/365) > 60");
            })
            ->when(request()->active, function ($query) {
                $query->whereRaw("(DATEDIFF(NOW(), PEOPLE.DATE_OF_BIRTH)/365) < 60");
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
        $staff = PersonUnit::query()
            ->with(['jobs', 'person'])
            ->join('people', function ($join) {
                $join->on('people.id', '=', 'person_unit.person_id');
            })
            ->join('job_staff', function ($join) {
                $join->on('job_staff.staff_id', '=', 'person_unit.id');
            })
            ->join('jobs', function ($join) {
                $join->on('job_staff.job_id', '=', 'jobs.id');
            })
            ->when(request()->active, function ($query) {
                $query->active();
            })
            ->when(request()->retired, function ($query) {
                $query->retired();
            })
            ->when(request()->ranks, function ($query) {
                $ranks = explode('|', request()->ranks);
                foreach ($ranks as $rank) {
                    $query->orWhere('jobs.name', $rank);
                }
                // $query->retired();
            })
            ->paginate(10)
            ->withQueryString()
            ->through(
                fn ($staff) => [
                    'staff_id' => $staff->id,
                    'person_id' => $staff->person_id,
                    'title' => $staff->title,
                    'surname' => $staff->surname,
                    'other_names' => $staff->other_names,
                    'first_name' => $staff->first_name,
                    'gender' => $staff->gender,
                    'date_of_birth' => $staff->date_of_birth,
                    'hire_date' => $staff->hire_date,
                    'years_employed' => $staff->years_employed,
                    'staff_number' => $staff->staff_number,
                    'old_staff_number' => $staff->old_staff_number,
                    'status' => $staff->status,
                    'current_job' => $staff->jobs ? [
                        'id' => $staff->job_id,
                        'institution_id' => $staff->institution_id,
                        'name' => $staff->name,
                        'start_date' => $staff->start_date,
                    ] : null
                ]
            );
        // return $staff;
        // dd(request()->ranks);
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